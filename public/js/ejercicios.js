/* Ejercicios interactivos.
 *
 * Sin dependencias y sin servidor: el estado es un objeto
 * { "numero de ejercicio": "letra elegida" } guardado en localStorage con la
 * clave hp-ej-<pieza>. Al volver a la pagina se repinta todo tal cual quedo.
 */
(function () {
  'use strict';

  var raiz = document.querySelector('[data-ejercicios]');
  if (!raiz) return;

  var clave = 'hp-ej-' + raiz.getAttribute('data-pieza');
  var lista = raiz.querySelectorAll('.ejercicio');
  var total = lista.length;
  if (!total) return;

  var hecha = 'hp-done-' + raiz.getAttribute('data-pieza');

  var estado = {};
  try { estado = JSON.parse(localStorage.getItem(clave) || '{}') || {}; }
  catch (e) { estado = {}; }

  function guardar() {
    try { localStorage.setItem(clave, JSON.stringify(estado)); }
    catch (e) { /* modo privado o almacen lleno: se sigue sin guardar */ }
  }

  /* Pinta un ejercicio ya respondido. */
  function pintar(ej, letra) {
    var correcta = ej.getAttribute('data-correcta');
    var acierto  = (letra === correcta);

    ej.querySelectorAll('.opcion').forEach(function (b) {
      var l = b.getAttribute('data-letra');
      b.disabled = true;
      if (l === correcta)              b.classList.add('op-correcta');
      if (l === letra && ! acierto)    b.classList.add('op-fallida');
      if (l === letra)                 b.classList.add('op-elegida');
    });

    var exp = ej.querySelector('.explicacion');
    if (exp) {
      exp.querySelector('.veredicto').textContent = acierto ? 'Верно. ' : 'Не совсем. ';
      exp.hidden = false;
    }

    ej.classList.add(acierto ? 'ej-acierto' : 'ej-fallo');
  }

  /* Barra, contador y, si esta todo, el resumen. */
  function actualizar() {
    var hechas = 0, aciertos = 0;

    lista.forEach(function (ej) {
      var letra = estado[ej.getAttribute('data-n')];
      if (!letra) return;
      hechas++;
      if (letra === ej.getAttribute('data-correcta')) aciertos++;
    });

    var barra = raiz.querySelector('.ej-barra');
    if (barra) barra.style.width = (total ? (hechas / total) * 100 : 0) + '%';

    var cuenta = raiz.querySelector('.ej-hechas');
    if (cuenta) cuenta.textContent = hechas;

    var resumen = raiz.querySelector('.ej-resumen');
    if (!resumen) return;

    if (hechas < total) {
      resumen.hidden = true;
      try { localStorage.removeItem(hecha); } catch (e) {}
      return;
    }

    // Pieza completada: la marca que la portada y los niveles leen para
    // pintar el camino (✓ en la tarjeta y «siguiente paso»).
    try { localStorage.setItem(hecha, aciertos + ' из ' + total); } catch (e) {}

    var nota  = resumen.querySelector('.ej-nota');
    var frase = resumen.querySelector('.ej-frase');
    var parte = aciertos / total;

    if (nota) nota.textContent = aciertos + ' из ' + total;
    if (frase) {
      frase.textContent =
        parte === 1   ? '¡Perfecto! Ни одной ошибки.' :
        parte >= 0.85 ? 'Отличный результат.' :
        parte >= 0.6  ? 'Хорошо. Загляните в объяснения к ошибкам — они того стоят.' :
                        'Не расстраивайтесь: пройдите объяснения и попробуйте ещё раз.';
    }

    resumen.hidden = false;
  }

  /* Responder: un intento por ejercicio. */
  raiz.addEventListener('click', function (e) {
    var boton = e.target.closest('.opcion');

    if (boton && !boton.disabled) {
      var ej = boton.closest('.ejercicio');
      var n  = ej.getAttribute('data-n');
      if (estado[n]) return;                 // ya respondido

      estado[n] = boton.getAttribute('data-letra');
      guardar();
      pintar(ej, estado[n]);
      actualizar();
      return;
    }

    if (e.target.closest('.ej-reiniciar')) {
      estado = {};
      try { localStorage.removeItem(clave); localStorage.removeItem(hecha); } catch (er) {}

      lista.forEach(function (ej) {
        ej.classList.remove('ej-acierto', 'ej-fallo');
        ej.querySelectorAll('.opcion').forEach(function (b) {
          b.disabled = false;
          b.classList.remove('op-correcta', 'op-fallida', 'op-elegida');
        });
        var exp = ej.querySelector('.explicacion');
        if (exp) exp.hidden = true;
      });

      actualizar();
      raiz.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });

  /* Restaurar lo ya respondido en visitas anteriores. */
  lista.forEach(function (ej) {
    var letra = estado[ej.getAttribute('data-n')];
    if (letra) pintar(ej, letra);
  });
  actualizar();
})();
