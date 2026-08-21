/* La descarga completa del curso para estudiar sin conexion.
 *
 * Dos pasos a la vista del alumno: un clic pide el inventario y ensena el
 * peso real; el segundo clic descarga. Todo va a las caches del service
 * worker: hp-medios (audio, imagenes) y hp-fijas (paginas y esqueleto),
 * que sobreviven a los despliegues. Lo ya descargado se salta: el boton
 * tambien sirve para ACTUALIZAR tras nuevos contenidos.
 */
(function () {
  'use strict';

  var zona = document.getElementById('zona-offline');
  if (!zona || !('caches' in window)) return;

  var boton = zona.querySelector('[data-offline-boton]');
  var barra = zona.querySelector('progress');
  var texto = zona.querySelector('[data-offline-texto]');
  var manifiesto = null;
  var bajando = false;

  function mb(bytes) { return Math.round(bytes / 1024 / 1024); }

  function di(mensaje) { texto.textContent = mensaje; }

  boton.addEventListener('click', function () {
    if (bajando) { bajando = false; boton.textContent = 'Скачать курс целиком'; return; }
    if (!manifiesto) { pedirInventario(); } else { descargar(); }
  });

  function pedirInventario() {
    di('Считаем…');
    fetch('/offline/manifiesto', { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        manifiesto = d;
        var total = d.paginas.length + d.medios.length;
        di('Всего ' + total + ' файлов, примерно ' + mb(d.bytes) + ' МБ. ' +
           'Лучше по Wi-Fi. Уже скачанное качаться заново не будет.');
        boton.textContent = 'Скачать ≈ ' + mb(d.bytes) + ' МБ';
      })
      .catch(function () { di('Не получилось связаться с сервером — попробуйте ещё раз.'); });
  }

  function descargar() {
    bajando = true;
    boton.textContent = 'Остановить';
    var cola = [];
    manifiesto.paginas.forEach(function (u) { cola.push({ u: u, fija: true }); });
    manifiesto.medios.forEach(function (u) { cola.push({ u: u, fija: false }); });
    // el esqueleto tambien, con su version actual
    document.querySelectorAll('link[rel="stylesheet"], script[src]').forEach(function (n) {
      var u = n.href || n.src;
      if (u && u.indexOf(location.origin) === 0) {
        cola.push({ u: u.slice(location.origin.length), fija: true });
      }
    });

    barra.max = cola.length;
    barra.value = 0;
    barra.hidden = false;
    var hechos = 0, fallos = 0;

    Promise.all([caches.open('hp-fijas'), caches.open('hp-medios')])
      .then(function (cs) {
        var fijas = cs[0], medios = cs[1];

        function uno() {
          if (!bajando) { di('Остановлено. Нажмите ещё раз, чтобы продолжить.'); return Promise.resolve(); }
          var tarea = cola.shift();
          if (!tarea) return Promise.resolve();
          var cache = tarea.fija ? fijas : medios;

          return cache.match(tarea.u, { ignoreSearch: !tarea.fija })
            .then(function (ya) {
              if (ya) return null;
              return fetch(tarea.u, { credentials: 'same-origin' })
                .then(function (r) {
                  if (r.ok) return cache.put(tarea.u, r);
                  fallos++;
                })
                .catch(function () { fallos++; });
            })
            .then(function () {
              hechos++;
              barra.value = hechos;
              if (hechos % 25 === 0 || cola.length === 0) {
                di(hechos + ' / ' + barra.max + (fallos ? ' · не скачалось: ' + fallos : ''));
              }
              return uno();
            });
        }

        // cuatro carriles en paralelo
        return Promise.all([uno(), uno(), uno(), uno()]);
      })
      .then(function () {
        if (!bajando) return;
        bajando = false;
        try { localStorage.setItem('hp-offline-fecha', new Date().toISOString().slice(0, 10)); } catch (e) {}
        boton.textContent = 'Обновить офлайн-копию';
        di(fallos
          ? 'Готово, но ' + fallos + ' файлов не скачалось — нажмите ещё раз, докачаются.'
          : 'Готово! Курс работает без интернета — проверьте в авиарежиме.');
      });
  }
})();
