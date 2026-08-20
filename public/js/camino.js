/* El camino del curso.
 *
 * El servidor publica el orden (JSON en #camino-datos); el progreso vive en
 * el navegador: hp-done-<pieza> lo deja ejercicios.js al completar una pieza.
 * Aqui solo se decora: ✓ en lo hecho, «вы здесь» en el siguiente paso, y las
 * piezas posteriores en voz baja. Nada se bloquea: es un curso libre y el
 * ritmo lo marca la vista, no un candado.
 */
(function () {
  'use strict';

  var datos = document.getElementById('camino-datos');
  if (!datos) return;

  var camino = [];
  try { camino = JSON.parse(datos.textContent || '[]') || []; }
  catch (e) { return; }

  function hecha(slug) {
    try { return localStorage.getItem('hp-done-' + slug); }
    catch (e) { return null; }
  }

  function tarjeta(slug) {
    return document.querySelector('[data-slug="' + slug + '"]');
  }

  /* ✓ y nota en lo completado. */
  document.querySelectorAll('[data-slug]').forEach(function (t) {
    var v = hecha(t.getAttribute('data-slug'));
    if (!v) return;
    t.classList.add('hecha');
    var marca = document.createElement('span');
    marca.className = 'marca-hecha';
    marca.textContent = '\u2713 ' + v;
    t.appendChild(marca);
  });

  /* El siguiente paso: lo primero del camino sin hacer. */
  var siguiente = null;
  var algoHecho = false;

  for (var i = 0; i < camino.length; i++) {
    if (hecha(camino[i].slug)) { algoHecho = true; continue; }
    if (!siguiente) siguiente = camino[i];
  }

  if (siguiente) {
    var t = tarjeta(siguiente.slug);
    if (t) {
      t.classList.add('aqui');
      var et = document.createElement('span');
      et.className = 'marca-aqui';
      et.textContent = algoHecho ? 'вы здесь' : 'начните здесь';
      t.appendChild(et);
    }

    /* Lo que viene despues del siguiente paso, en voz baja. */
    var despues = false;
    camino.forEach(function (p) {
      if (p.slug === siguiente.slug) { despues = true; return; }
      if (despues && !hecha(p.slug)) {
        var f = tarjeta(p.slug);
        if (f) f.classList.add('futura');
      }
    });
  }

  /* Banner de continuar (solo existe en la portada). */
  var banner = document.querySelector('[data-continuar]');
  if (banner && siguiente) {
    banner.href = siguiente.url;
    banner.querySelector('.c-et').textContent = algoHecho ? 'Продолжить' : 'Начать курс';
    banner.querySelector('.c-ti').textContent = siguiente.etiqueta + ' \u00b7 ' + siguiente.titulo;
    banner.hidden = false;
  }
})();
