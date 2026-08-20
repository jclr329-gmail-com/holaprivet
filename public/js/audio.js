/* El audio del curso.
 *
 * Cada fragmento espanol lleva data-audio con el sha1 de su texto; el
 * archivo vive en /audio/<2 primeras>/<hash>.mp3, subido al servidor fuera
 * del repositorio. Si un archivo no existe todavia, el clic hace lo de
 * siempre (el pulso visual) y no molesta: el curso funciona con audio a
 * medias mientras se va generando.
 *
 * El boton «Слушать» de una escena encadena sus replicas; en un cuento
 * reproduce la narracion completa (/audio/cuentos/<pieza>.mp3).
 */
(function () {
  'use strict';

  var BASE   = '/audio/';
  var rotos  = {};          // hashes que ya dieron 404: no se reintentan
  var actual = null;        // el Audio sonando ahora mismo

  function ruta(h) { return BASE + h.slice(0, 2) + '/' + h + '.mp3'; }

  function pulso(el) {
    el.classList.add('son');
    setTimeout(function () { el.classList.remove('son'); }, 900);
  }

  function parar() {
    if (actual) { actual.pause(); actual = null; }
    document.querySelectorAll('.sonando').forEach(function (el) {
      el.classList.remove('sonando');
    });
  }

  function sonar(el) {
    pulso(el);

    var h = el.getAttribute('data-audio');
    if (!h || rotos[h]) return;

    parar();
    var a = new Audio(ruta(h));
    actual = a;
    el.classList.add('sonando');

    a.addEventListener('ended', function () {
      el.classList.remove('sonando');
      if (actual === a) actual = null;
    });
    a.addEventListener('error', function () {
      rotos[h] = true;
      el.classList.remove('sonando');
    });
    a.play().catch(function () { el.classList.remove('sonando'); });
  }

  /* Fragmentos sueltos: pulso al pasar, sonido al pulsar. */
  document.querySelectorAll('.es').forEach(function (el) {
    el.tabIndex = 0;
    el.addEventListener('mouseenter', function () { pulso(el); });
    el.addEventListener('click', function () { sonar(el); });
    el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sonar(el); }
    });
  });

  /* El boton de cada escena o cuento. */
  document.querySelectorAll('.escena').forEach(function (caja) {
    var boton = caja.querySelector('.oir');
    if (!boton) return;

    var cuento   = boton.getAttribute('data-cuento');
    var enMarcha = false;

    function detener() {
      enMarcha = false;
      boton.classList.remove('en-marcha');
      parar();
    }

    boton.addEventListener('click', function () {
      if (enMarcha) { detener(); return; }

      /* Cuento: la narracion entera, un solo archivo. */
      if (cuento) {
        parar();
        var a = new Audio(BASE + 'cuentos/' + cuento + '.mp3');
        actual   = a;
        enMarcha = true;
        boton.classList.add('en-marcha');
        a.addEventListener('ended', detener);
        a.addEventListener('error', detener);
        a.play().catch(detener);
        return;
      }

      /* Escena: las replicas, una detras de otra. */
      var cola = caja.querySelectorAll('.dice [data-audio]');
      var i    = 0;
      enMarcha = true;
      boton.classList.add('en-marcha');

      (function paso() {
        if (!enMarcha || i >= cola.length) { detener(); return; }

        var el = cola[i++];
        var h  = el.getAttribute('data-audio');
        if (rotos[h]) { paso(); return; }

        parar();
        var a = new Audio(ruta(h));
        actual = a;
        el.classList.add('sonando');
        el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

        a.addEventListener('ended', function () {
          el.classList.remove('sonando');
          setTimeout(function () { if (enMarcha) paso(); }, 120);
        });
        a.addEventListener('error', function () {
          rotos[h] = true;
          el.classList.remove('sonando');
          paso();
        });
        a.play().catch(function () { detener(); });
      })();
    });
  });
})();
