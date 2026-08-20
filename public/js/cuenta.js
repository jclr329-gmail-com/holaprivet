/* La cuenta y el progreso.
 *
 * localStorage sigue mandando en la pagina (asi camino.js y ejercicios.js no
 * cambian de vida); este guion lo mantiene sincronizado con el servidor:
 *
 *  1. Al cargar, baja el progreso de la cuenta y lo vuelca en localStorage.
 *  2. Lo que haya en el navegador y el servidor no conozca, se sube: el
 *     progreso hecho ANTES de crear la cuenta no se pierde.
 *  3. Expone window.hpSync.subir(pieza): ejercicios.js lo llama al responder.
 *
 * Solo se carga cuando hay sesion (el layout lo incluye con @auth).
 */
(function () {
  'use strict';

  var csrf = (document.querySelector('meta[name="csrf"]') || {}).content || '';

  function subir(pieza) {
    var respuestas = null;
    var nota = null;
    try {
      respuestas = JSON.parse(localStorage.getItem('hp-ej-' + pieza) || 'null');
      nota = localStorage.getItem('hp-done-' + pieza);
    } catch (e) { return; }

    fetch('/progreso', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json'
      },
      credentials: 'same-origin',
      keepalive: true,
      body: JSON.stringify({
        pieza: pieza,
        respuestas: respuestas || {},
        hecha: !!nota,
        nota: nota
      })
    }).catch(function () { /* sin red ahora mismo: localStorage lo conserva */ });
  }

  window.hpSync = { subir: subir };

  /* Bajar el estado de la cuenta y detectar que hay que subir. */
  fetch('/progreso/estado', {
    headers: { 'Accept': 'application/json' },
    credentials: 'same-origin'
  })
    .then(function (r) { return r.json(); })
    .then(function (datos) {
      var enServidor = (datos && datos.piezas) || {};
      var cambio = false;

      /* piezas locales (para el volcado inicial) */
      var locales = {};
      for (var i = 0; i < localStorage.length; i++) {
        var k = localStorage.key(i) || '';
        if (k.indexOf('hp-ej-') === 0)   locales[k.slice(6)] = true;
        if (k.indexOf('hp-done-') === 0) locales[k.slice(8)] = true;
      }

      Object.keys(enServidor).forEach(function (pieza) {
        var p = enServidor[pieza];
        try {
          if (p.respuestas) {
            var nuevo = JSON.stringify(p.respuestas);
            if (localStorage.getItem('hp-ej-' + pieza) !== nuevo) {
              localStorage.setItem('hp-ej-' + pieza, nuevo);
              cambio = true;
            }
          }
          if (p.hecha) {
            var marca = p.nota || '\u2713';
            if (localStorage.getItem('hp-done-' + pieza) !== marca) {
              localStorage.setItem('hp-done-' + pieza, marca);
              cambio = true;
            }
          }
        } catch (e) {}
        delete locales[pieza];
      });

      /* lo del navegador que el servidor no conoce: arriba con ello */
      Object.keys(locales).forEach(subir);

      /* si ha llegado progreso nuevo y la pagina pinta el camino, un solo
         refresco lo decora; el candado de sesion evita bucles */
      var pinta = document.getElementById('camino-datos') ||
                  document.querySelector('[data-ejercicios]');
      try {
        if (cambio && pinta && !sessionStorage.getItem('hp-sync-refresco')) {
          sessionStorage.setItem('hp-sync-refresco', '1');
          location.reload();
        }
      } catch (e) {}
    })
    .catch(function () {});
})();
