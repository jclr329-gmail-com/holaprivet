/* holaprivet · service worker (fase A)
 *
 * Estrategia, pensada para nuestros pesos:
 *  - Esqueleto (css/js):   se sirve de cache y se refresca detras (rapido
 *                          siempre, actualizado al siguiente golpe).
 *  - Paginas (HTML):       red primero, copia en cache de respaldo — llevan
 *                          la sesion del usuario, no se congelan.
 *  - Audio, imagenes, PDF: cache sobre la marcha. Lo que escuchas y ves se
 *                          queda (el modulo estudiado funciona en el metro);
 *                          lo que no, no ocupa. JAMAS se precargan los
 *                          ~130 MB de audio.
 *
 * Versionado: el layout registra /sw.js?v=<mtime del despliegue>. Ese ?v
 * cambia con cada despliegue, el navegador ve un worker nuevo, y aqui
 * skipWaiting + clients.claim lo activan al momento: nadie se queda
 * atrapado en la version vieja (la leccion de la cache, aprendida).
 */
'use strict';

const V = new URL(self.location.href).searchParams.get('v') || '1';
const ESQUELETO = 'hp-esqueleto-' + V;
const MEDIOS = 'hp-medios';           // sobrevive a las versiones
const PAGINAS = 'hp-paginas-' + V;

self.addEventListener('install', function (e) {
  self.skipWaiting();
});

self.addEventListener('activate', function (e) {
  e.waitUntil((async function () {
    const nombres = await caches.keys();
    await Promise.all(nombres.map(function (n) {
      const viva = n === ESQUELETO || n === MEDIOS || n === PAGINAS;
      return viva ? null : caches.delete(n);
    }));
    await self.clients.claim();
  })());
});

self.addEventListener('fetch', function (e) {
  const pet = e.request;
  if (pet.method !== 'GET') return;

  const url = new URL(pet.url);
  if (url.origin !== self.location.origin) return;   // Stripe, Google: ni tocar

  // medios: cache sobre la marcha
  if (/^\/(audio|img|descargas)\//.test(url.pathname)) {
    e.respondWith((async function () {
      const cache = await caches.open(MEDIOS);
      const guardado = await cache.match(pet);
      if (guardado) return guardado;
      const fresco = await fetch(pet);
      if (fresco.ok) cache.put(pet, fresco.clone());
      return fresco;
    })());
    return;
  }

  // esqueleto: cache primero, refresco detras
  if (/^\/(css|js)\//.test(url.pathname)) {
    e.respondWith((async function () {
      const cache = await caches.open(ESQUELETO);
      const guardado = await cache.match(pet);
      const red = fetch(pet).then(function (r) {
        if (r.ok) cache.put(pet, r.clone());
        return r;
      }).catch(function () { return guardado; });
      return guardado || red;
    })());
    return;
  }

  // paginas: red primero, respaldo de cache para el metro
  if (pet.mode === 'navigate') {
    e.respondWith((async function () {
      const cache = await caches.open(PAGINAS);
      try {
        const fresco = await fetch(pet);
        if (fresco.ok) cache.put(pet, fresco.clone());
        return fresco;
      } catch (err) {
        const guardado = await cache.match(pet);
        if (guardado) return guardado;
        throw err;
      }
    })());
  }
});
