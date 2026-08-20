#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Convierte los masteres de las ilustraciones a los WebP que sirve la web.

Preparacion (una vez):
    py -m pip install pillow

Uso:
    py preparar-imagenes.py --origen masteres --salida img/piezas

Que hace: coge cada .png (o .jpg) de la carpeta de masteres, lo reduce a
1400 px de lado mayor y lo guarda como .webp con el mismo nombre. Un master
de 2K y varios MB queda en 60-120 KB, que es lo que de verdad necesita una
columna de lectura de 700 px.

Los nombres se respetan tal cual: n1-m01-saludos.png -> n1-m01-saludos.webp.
Por eso importa haberlos renombrado con el slug de su pieza: la web busca
exactamente img/piezas/<slug>.webp.

Los archivos que empiezan por «personaje-» se omiten: son las referencias
del elenco, no ilustraciones de piezas. Con --personajes se incluyen.
"""

import argparse
import os
import sys

ANCHO_MAX = 1400
CALIDAD = 82


def main():
    ap = argparse.ArgumentParser(description='Masteres -> WebP para la web')
    ap.add_argument('--origen', default='masteres')
    ap.add_argument('--salida', default='img/piezas')
    ap.add_argument('--ancho', type=int, default=ANCHO_MAX,
                    help=f'lado mayor en pixeles (por defecto {ANCHO_MAX})')
    ap.add_argument('--calidad', type=int, default=CALIDAD)
    ap.add_argument('--personajes', action='store_true',
                    help='incluir tambien los personaje-*.png')
    args = ap.parse_args()

    try:
        from PIL import Image
    except ImportError:
        sys.exit('Falta Pillow. Instalalo con:  py -m pip install pillow')

    if not os.path.isdir(args.origen):
        sys.exit(f'No existe la carpeta {args.origen}')

    os.makedirs(args.salida, exist_ok=True)

    entradas = sorted(f for f in os.listdir(args.origen)
                      if f.lower().endswith(('.png', '.jpg', '.jpeg', '.webp')))
    if not entradas:
        sys.exit(f'No hay imagenes en {args.origen}')

    hechas, saltadas, total_bytes = 0, 0, 0

    for nombre in entradas:
        base = os.path.splitext(nombre)[0]

        if base.startswith('personaje-') and not args.personajes:
            saltadas += 1
            continue

        origen = os.path.join(args.origen, nombre)
        destino = os.path.join(args.salida, base + '.webp')

        with Image.open(origen) as im:
            im = im.convert('RGB')
            ancho, alto = im.size
            escala = args.ancho / max(ancho, alto)
            if escala < 1:
                im = im.resize((round(ancho * escala), round(alto * escala)),
                               Image.LANCZOS)
            im.save(destino, 'WEBP', quality=args.calidad, method=6)

        peso = os.path.getsize(destino)
        total_bytes += peso
        hechas += 1
        print(f'  {base+".webp":42} {peso/1024:6.0f} KB')

    print(f'\nConvertidas: {hechas}'
          + (f' · omitidas (personajes): {saltadas}' if saltadas else ''))
    print(f'Peso total: {total_bytes/1024/1024:.1f} MB'
          f'  ({total_bytes/hechas/1024:.0f} KB de media)')
    print(f'\nComprime la carpeta «{args.salida}» y subela a la raiz del '
          f'subdominio en Plesk, junto a css/ y js/ y audio/.')


if __name__ == '__main__':
    main()
