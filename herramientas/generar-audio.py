#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generador de audio del curso holaprivet.

Preparacion (una vez):
    pip install edge-tts

Uso normal:
    python generar-audio.py --contenido ./ContenidoWeb --salida ./audio

Otros modos:
    --contar          solo cuenta lo que generaria (no necesita edge-tts)
    --prueba          genera solo 12 archivos, para oir las voces
    --sin-fragmentos  omite los fragmentos sueltos de las explicaciones
                      (la parte mas numerosa); replicas, frases, vocabulario
                      y cuentos si se generan

Que hace: recorre los archivos del curso, extrae cada texto espanol audible
—replicas de las escenas, narracion completa de los cuentos, frases,
vocabulario y los fragmentos marcados dentro de las explicaciones— y fabrica
un MP3 por texto con las voces neuronales gratuitas de Microsoft (edge-tts).

Cada archivo se llama por el sha1 de su texto normalizado: exactamente el
mismo calculo que hace la web (app/Support/Refs.php). Es reanudable: lo ya
generado se salta, asi que se puede cortar y relanzar sin miedo.

La carpeta de salida se sube tal cual a la RAIZ del subdominio (junto a css/
y js/), donde los despliegues no la tocan:
    audio/ab/ab12...mp3      fragmentos, por hash
    audio/cuentos/<id>.mp3   narracion de cada cuento
"""

import argparse
import asyncio
import glob
import hashlib
import os
import re
import sys

CYR = re.compile(r'[\u0400-\u04FF]')
LAT = re.compile(r'[A-Za-z\u00C0-\u024F]')

# ---------------------------------------------------------------- las voces
# es-ES = espanol de Espana. Si una voz no estuviera disponible el dia de
# manana, se usa la de reserva automaticamente.

NARRADORA = ('es-ES-ElviraNeural', '-8%')     # vocabulario, frases, cuentos

VOCES = {
    'katya':       ('es-ES-XimenaNeural', '+0%'),
    'doña carmen': ('es-ES-ElviraNeural', '-15%'),
    'miguel':      ('es-ES-AlvaroNeural', '+0%'),
    'nico':        ('es-ES-AlvaroNeural', '+8%'),
    'megafonía':   ('es-ES-AlvaroNeural', '-5%'),
}

RESERVA = {'es-ES-XimenaNeural': 'es-ES-ElviraNeural'}

FEMENINOS = {'médica', 'funcionaria', 'farmacéutica', 'entrevistadora',
             'dependienta', 'casera', 'cajera', 'recepcionista'}


def voz_de(personaje):
    p = (personaje or '').strip().lower()
    if p in VOCES:
        return VOCES[p]
    if p in FEMENINOS or p.endswith('a'):
        return ('es-ES-ElviraNeural', '+3%')
    return ('es-ES-AlvaroNeural', '+3%')


# ------------------------------------------------- extraccion (como la web)

def normalizar(t):
    """Identica a app/Support/Refs.php: si cambia alli, cambiar aqui."""
    t = re.sub(r'<[^>]+>', '', t)
    t = t.replace('*', '').replace('`', '')
    t = re.sub(r'\s+', ' ', t)
    return t.strip()


def hash_de(t):
    return hashlib.sha1(normalizar(t).encode('utf-8')).hexdigest()


def texto_para_voz(t):
    """Lo que se ENVIA a la voz (el hash sigue siendo el del texto original).

    «nuevo / nueva» se leia «nuevo barra diagonal nueva»: la barra se
    convierte en punto, que la voz interpreta como una pausa natural.
    """
    t = re.sub(r'\s*/\s*', '. ', t)
    t = re.sub(r'\s+', ' ', t).strip()
    return t


def separar(bruto):
    m = re.match(r'^---\n(.*?)\n---\n?(.*)$', bruto, re.S)
    if not m:
        return {}, bruto
    cab = {}
    for l in m.group(1).split('\n'):
        mm = re.match(r'^(\w[\w]*):\s*(.*)$', l)
        if mm:
            cab[mm.group(1)] = mm.group(2).strip().strip('"')
    return cab, m.group(2)


def secciones(cuerpo):
    out, actual = [], None
    for l in cuerpo.split('\n'):
        m = re.match(r'^##\s+(\d+)\.\s+(.+?)\s*$', l)
        if m:
            if actual:
                out.append(actual)
            titulo = m.group(2)
            es = titulo.split(' — ')[0].split(' – ')[0].strip()
            actual = {'kind': tipo(es), 'cuerpo': []}
            continue
        if actual:
            actual['cuerpo'].append(l)
    if actual:
        out.append(actual)
    for s in out:
        s['body'] = '\n'.join(s['cuerpo']).strip()
    return out


MAPA = [('objetivos', 'objetivos'), ('antes de empezar', 'apoyo'),
        ('antes de leer', 'apoyo'), ('la escena', 'escena'),
        ('el cuento', 'cuento'), ('traducción', 'traduccion'),
        ('frases para llevar', 'frases'), ('frases útiles', 'frases'),
        ('ejercicios', 'ejercicios'), ('solucionario', 'soluciones'),
        ('soluciones', 'soluciones'), ('has entendido', 'preguntas'),
        ('enlaces', 'enlaces')]


def tipo(titulo):
    t = titulo.lower()
    for aguja, k in MAPA:
        if aguja in t:
            return k
    return 'texto'


def limpiar(t):
    t = re.sub(r'\*\*(.+?)\*\*', r'\1', t)
    t = re.sub(r'\*(.+?)\*', r'\1', t)
    t = re.sub(r'`(.+?)`', r'\1', t)
    t = re.sub(r'\[(.+?)\]\(.+?\)', r'\1', t)
    return t.strip()


def replicas(md):
    """Las replicas de una escena, con su personaje."""
    out, pers = [], None
    for l in md.split('\n'):
        t = l.strip()
        if not t.startswith('>'):
            continue
        c = re.sub(r'^>\s?', '', t).strip()
        if not c:
            continue
        m = re.match(r'^\*\*(.+?)\*\*\s*(?:\*\((.+?)\)\*)?$', c)
        if m:
            pers = m.group(1).strip()
            continue
        texto = limpiar(re.sub(r'\s*\*\((.+?)\)\*\s*$', '', c))
        if texto:
            out.append((pers, texto))
    return out


def prosa(md):
    """Parrafos del cuento (igual que el analizador de la web)."""
    out = []
    for bloque in re.split(r'\n\s*\n', md):
        bloque = bloque.strip()
        if not bloque or bloque == '---':
            continue
        texto = ' '.join(l.strip().lstrip('> ').strip()
                         for l in bloque.split('\n'))
        texto = limpiar(texto.strip())
        if texto:
            out.append(texto)
    return out


def frases(md):
    items = []
    for l in md.split('\n'):
        l = l.strip()
        if not l or l == '---':
            continue
        if re.match(r'^\d+\.\s', l):
            items.append(l)
        elif items:
            items[-1] += ' ' + l
    out = []
    for l in items:
        m = re.match(r'^\s*\d+\.\s+(.+)$', l)
        if not m:
            continue
        resto = m.group(1)
        es = None
        for h in re.finditer(r'\s[—–]\s', resto):
            if resto[:h.start()].count('**') % 2 == 0:
                es = limpiar(resto[:h.start()])
                break
        if es is None:
            es = limpiar(resto)
        if es:
            out.append(es)
    return out


def vocabulario(md):
    out, en_bloque = [], False
    for l in md.split('\n'):
        m = re.match(r'^###\s+(.+)$', l)
        if m:
            t = m.group(1).lower()
            en_bloque = any(b in t for b in
                            ('palabras nuevas', 'ya lo conoces', 'frases clave'))
            continue
        if not en_bloque or not l.strip().startswith('|'):
            continue
        celdas = [c.strip() for c in l.strip().strip('|').split('|')]
        if not celdas or re.match(r'^:?-{2,}:?$', celdas[0].replace(' ', '')):
            continue
        if celdas[0].lower() in ('español', 'espanol', 'verbo', 'palabra'):
            continue
        es = limpiar(celdas[0])
        if es:
            out.append(es)
    return out


def fragmentos(md):
    """Lo que la web marca como audible dentro de las explicaciones:
    la negrita latina y la primera columna latina de las tablas."""
    out = []

    def negritas(texto):
        for m in re.finditer(r'\*\*(.+?)\*\*', texto, re.S):
            s = m.group(1)
            if not CYR.search(s) and LAT.search(s):
                out.append(s)

    for l in md.split('\n'):
        t = l.strip()
        if t.startswith('|'):
            celdas = [c.strip() for c in t.strip('|').split('|')]
            if not celdas or re.match(r'^:?-{2,}:?$',
                                      celdas[0].replace(' ', '')):
                continue
            primera = celdas[0]
            if primera and not CYR.search(primera):
                # como marcarEspanol: la acotacion en cursiva queda fuera
                limpio = re.sub(r'\*\*(.+?)\*\*', r'\1', primera).strip()
                m = re.match(r'^(.*?)\s*\*\((.+?)\)\*\s*$', limpio)
                base = m.group(1).strip() if m else limpio
                if base and base != '—' and LAT.search(base):
                    out.append(base)
            else:
                negritas(primera)
            for c in celdas[1:]:
                negritas(c)
        else:
            negritas(l)
    return out


# ------------------------------------------------------------ el inventario

def inventario(carpeta, nivel=None, sin_fragmentos=False):
    """Devuelve (tareas, cuentos): que hay que generar y con que voz."""
    tareas = {}          # hash -> (texto, voz, rate, categoria)
    narraciones = []     # (id_pieza, texto completo)
    cuenta = {'replicas': 0, 'frases': 0, 'vocabulario': 0,
              'fragmentos': 0, 'cuentos': 0}

    def apunta(texto, voz, categoria, forzar=False):
        # Sin letras ni numeros no hay nada que pronunciar («…», «—»):
        # esos fragmentos se quedan mudos a proposito.
        if not re.search(r'[A-Za-z\u00C0-\u024F0-9]', texto):
            return
        h = hash_de(texto)
        if h in tareas and not forzar:
            return
        tareas[h] = (normalizar(texto), voz[0], voz[1], categoria)
        cuenta[categoria] += 1

    archivos = sorted(glob.glob(os.path.join(carpeta, '**', '*.md'),
                                recursive=True))
    if not archivos:
        sys.exit(f'No hay archivos .md en {carpeta}')

    for a in archivos:
        with open(a, encoding='utf-8') as fh:
            cab, cuerpo = separar(fh.read())
        if 'id' not in cab:
            continue
        if nivel and cab.get('nivel') and int(cab['nivel']) != nivel:
            continue

        for s in secciones(cuerpo):
            k = s['kind']

            if k == 'escena' and cab.get('tipo') != 'cuento':
                for pers, texto in replicas(s['body']):
                    apunta(texto, voz_de(pers), 'replicas', forzar=True)

            elif k in ('escena', 'cuento') and cab.get('tipo') == 'cuento':
                partes = prosa(s['body'])
                for p in partes:
                    apunta(p, NARRADORA, 'fragmentos')
                if partes:
                    narraciones.append((cab['id'], '\n\n'.join(partes)))
                    cuenta['cuentos'] += 1

            elif k == 'frases':
                for f in frases(s['body']):
                    apunta(f, NARRADORA, 'frases')

            elif k == 'apoyo':
                for v in vocabulario(s['body']):
                    apunta(v, NARRADORA, 'vocabulario')

            elif k in ('traduccion', 'enlaces', 'ejercicios', 'preguntas'):
                continue

            elif not sin_fragmentos:
                for f in fragmentos(s['body']):
                    apunta(f, NARRADORA, 'fragmentos')

    return tareas, narraciones, cuenta


# ------------------------------------------------------------- generacion

async def genera_uno(sem, texto, voz, rate, ruta):
    import edge_tts
    async with sem:
        # Un respiro entre peticiones: el servicio frena a quien no lo da,
        # y entonces empieza a devolver audio VACIO (archivos de 0 bytes).
        await asyncio.sleep(0.25)

        ultimo_error = ''
        for intento in (1, 2, 3, 4):
            # Plan B para los textos «x. y»: si el punto intermedio no
            # genera, en el intento 3 se prueba con coma (pausa mas corta,
            # pero audio al fin y al cabo).
            enviar = texto
            if intento >= 3 and '. ' in texto and not texto.endswith('.'):
                enviar = texto.replace('. ', ', ')

            try:
                await edge_tts.Communicate(enviar, voz, rate=rate).save(ruta)

                # Un mp3 real de una palabra ya pesa varios KB: si pesa menos,
                # el servicio nos ha frenado y esto no vale.
                if os.path.getsize(ruta) > 1000:
                    return True
                peso = os.path.getsize(ruta)
                os.remove(ruta)
                raise RuntimeError(f'respuesta de {peso} bytes')

            except Exception as e:
                ultimo_error = type(e).__name__ + ': ' + str(e)[:120]
                try:
                    if os.path.exists(ruta) and os.path.getsize(ruta) == 0:
                        os.remove(ruta)
                except OSError:
                    pass

                if intento == 3 and voz in RESERVA:
                    voz = RESERVA[voz]      # ultima carta: la voz de reserva
                    continue
                if intento == 4:
                    return ultimo_error or False
                # Esperar de verdad: el frenado se pasa solo en unos segundos
                await asyncio.sleep(6 * intento)


async def genera_todo(tareas, narraciones, salida, prueba=False,
                      rehacer_barras=False):
    # 4 a la vez y con pausa: mas lento, pero el servicio no nos frena.
    sem = os.makedirs(salida, exist_ok=True) or asyncio.Semaphore(4)
    pendientes, saltados = [], 0

    for h, (texto, voz, rate, _) in tareas.items():
        carpeta = os.path.join(salida, h[:2])
        os.makedirs(carpeta, exist_ok=True)
        ruta = os.path.join(carpeta, h + '.mp3')
        if os.path.exists(ruta):
            if rehacer_barras and '/' in texto:
                os.remove(ruta)      # se genero diciendo «barra diagonal»
            elif os.path.getsize(ruta) > 0:
                saltados += 1
                continue
            else:
                os.remove(ruta)      # vacio de una corrida frenada: se rehace
        pendientes.append((texto_para_voz(texto), voz, rate, ruta))

    os.makedirs(os.path.join(salida, 'cuentos'), exist_ok=True)
    for pid, texto in narraciones:
        ruta = os.path.join(salida, 'cuentos', pid + '.mp3')
        if os.path.exists(ruta) and os.path.getsize(ruta) > 0:
            saltados += 1
            continue
        pendientes.append((texto_para_voz(texto), NARRADORA[0], NARRADORA[1], ruta))

    if prueba:
        pendientes = pendientes[:12]

    print(f'Por generar: {len(pendientes)} · ya existian: {saltados}')

    hechos, errores = 0, []
    lotes = [pendientes[i:i + 60] for i in range(0, len(pendientes), 60)]
    for lote in lotes:
        resultados = await asyncio.gather(*[
            genera_uno(sem, t, v, r, ruta) for t, v, r, ruta in lote
        ])
        for (t, v, r, ruta), ok in zip(lote, resultados):
            if ok is True:
                hechos += 1
            else:
                errores.append((ruta, v, t + '  [' + (ok or 'sin detalle') + ']'))
        print(f'  {hechos + len(errores)} / {len(pendientes)}')

    print(f'\nGenerados: {hechos} · errores: {len(errores)}')
    if errores:
        with open('errores-audio.txt', 'w', encoding='utf-8') as fh:
            for ruta, v, txt in errores:
                fh.write(f'{os.path.basename(ruta)}\t{v}\t{txt}\n')
        print(f'La lista completa, con el texto de cada uno, esta en '
              f'errores-audio.txt ({len(errores)} lineas). Si al relanzar '
              f'fallan siempre los mismos, pasa ese archivo para revisarlos.')

    total = 0
    for raiz, _, archivos in os.walk(salida):
        for a in archivos:
            total += os.path.getsize(os.path.join(raiz, a))
    print(f'Tamaño total de {salida}: {total / 1024 / 1024:.0f} MB')


# -------------------------------------------------------------------- main

def main():
    ap = argparse.ArgumentParser(description='Audio del curso holaprivet')
    ap.add_argument('--contenido', default='ContenidoWeb',
                    help='carpeta con los .md del curso')
    ap.add_argument('--salida', default='audio')
    ap.add_argument('--nivel', type=int, choices=[1, 2, 3])
    ap.add_argument('--sin-fragmentos', action='store_true')
    ap.add_argument('--contar', action='store_true',
                    help='solo contar, sin generar (no requiere edge-tts)')
    ap.add_argument('--prueba', action='store_true',
                    help='genera solo 12 archivos para oir las voces')
    ap.add_argument('--rehacer-barras', action='store_true',
                    help='regenera los audios cuyo texto lleva «/» (antes '
                         'se leia «barra diagonal»; ahora es una pausa)')
    args = ap.parse_args()

    tareas, narraciones, cuenta = inventario(
        args.contenido, args.nivel, args.sin_fragmentos)

    print('Inventario:')
    for k, v in cuenta.items():
        print(f'  {k:12} {v}')
    print(f'  {"unicos":12} {len(tareas)} fragmentos '
          f'+ {len(narraciones)} narraciones')

    if args.contar:
        return

    try:
        import edge_tts  # noqa: F401
    except ImportError:
        sys.exit('Falta edge-tts. Instalalo con:  pip install edge-tts')

    asyncio.run(genera_todo(tareas, narraciones, args.salida, args.prueba,
                            args.rehacer_barras))


if __name__ == '__main__':
    main()
