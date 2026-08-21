/* «Поделиться курсом»: en movil abre la hoja nativa de compartir; en
   escritorio copia el enlace y lo dice. La direccion compartida es siempre
   la casa de produccion, este donde este el boton. */
(function () {
  'use strict';

  var URL_CURSO = 'https://holaprivet.com';
  var TEXTO = 'Бесплатный курс испанского для русскоговорящих — holaprivet';

  document.querySelectorAll('[data-compartir]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (navigator.share) {
        navigator.share({ title: 'holaprivet', text: TEXTO, url: URL_CURSO })
          .catch(function () {});
        return;
      }
      var listo = function () {
        var antes = b.textContent;
        b.textContent = 'Ссылка скопирована ✓';
        b.disabled = true;
        setTimeout(function () { b.textContent = antes; b.disabled = false; }, 2200);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(URL_CURSO).then(listo).catch(function () {
          window.prompt('Скопируйте ссылку:', URL_CURSO);
        });
      } else {
        window.prompt('Скопируйте ссылку:', URL_CURSO);
      }
    });
  });
})();
