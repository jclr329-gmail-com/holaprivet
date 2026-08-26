@extends('layout')
@section('titulo', 'О нас: кто делает holaprivet')
@section('descripcion', 'История курса holaprivet: испанец из Малаги, его жена, которая учит испанский, и курс, который вырос из их разговоров. Почему он бесплатный и для кого.')

@section('cuerpo')
<div class="ancho estrecho-medio">

  <div class="nivel-cab">
    <div class="n">О нас</div>
    <h2>Кто делает holaprivet</h2>
  </div>

  <div class="nosotros-texto">
  @if ($foto)
    <figure class="nosotros-foto">
      <img src="{{ $foto }}" alt="Мы">
    </figure>
  @endif

  <p class="bi">
    <span class="bi-es">Todo empezó</span> <span class="bi-ru">всё началось</span>
    <span class="bi-es">en la pandemia.</span> <span class="bi-ru">в пандемию.</span>
    <span class="bi-es">Encerrados en casa,</span> <span class="bi-ru">запертые по домам,</span>
    <span class="bi-es">apareció un curso de ruso:</span> <span class="bi-ru">появился курс русского:</span>
    <span class="bi-es">«la escritura cirílica</span> <span class="bi-ru">«кириллица</span>
    <span class="bi-es">en tres vídeos de diez minutos».</span> <span class="bi-ru">за три видео по десять минут».</span>
    <span class="bi-es">¿Sería posible?</span> <span class="bi-ru">разве это возможно?</span>
    <span class="bi-es">Para mí aquellas letras</span> <span class="bi-ru">для меня эти буквы</span>
    <span class="bi-es">eran jeroglíficos</span> <span class="bi-ru">были иероглифами</span>
    <span class="bi-es">— y sí:</span> <span class="bi-ru">— и да:</span>
    <span class="bi-es">en tres vídeos aprendí a leerlas.</span> <span class="bi-ru">за три видео я научился их читать.</span>
  </p>

  <p class="bi">
    <span class="bi-es">Me enamoré del idioma,</span> <span class="bi-ru">я влюбился в язык,</span>
    <span class="bi-es">conocí a gente rusohablante</span> <span class="bi-ru">познакомился с русскоговорящими</span>
    <span class="bi-es">y, entre ellos,</span> <span class="bi-ru">и среди них —</span>
    <span class="bi-es">a quien hoy es mi esposa.</span> <span class="bi-ru">с той, кто сегодня моя жена.</span>
    <span class="bi-es">Ella aprende español</span> <span class="bi-ru">она учит испанский,</span>
    <span class="bi-es">y yo le enseño lo que puedo,</span> <span class="bi-ru">а я учу её, как могу,</span>
    <span class="bi-es">de la mejor forma que sé.</span> <span class="bi-ru">лучшим известным мне способом.</span>
    <span class="bi-es">De esas clases en nuestra cocina</span> <span class="bi-ru">из тех занятий на нашей кухне</span>
    <span class="bi-es">nació este curso.</span> <span class="bi-ru">родился этот курс.</span>
  </p>

  <p class="bi">
    <span class="bi-es">Si la gramática se te hace indigesta,</span> <span class="bi-ru">если грамматика кажется вам неподъёмной,</span>
    <span class="bi-es">este material es para ti.</span> <span class="bi-ru">этот материал для вас.</span>
    <span class="bi-es">Además hay PDF para imprimir</span> <span class="bi-ru">а ещё есть PDF для печати</span>
    <span class="bi-es">y recursos escogidos</span> <span class="bi-ru">и отобранные ресурсы,</span>
    <span class="bi-es">que te van a gustar.</span> <span class="bi-ru">которые вам понравятся.</span>
    <span class="bi-es">No ha sido fácil,</span> <span class="bi-ru">это было непросто,</span>
    <span class="bi-es">pero creo que servirá</span> <span class="bi-ru">но, думаю, пригодится</span>
    <span class="bi-es">a todos los que quieren aprender español.</span> <span class="bi-ru">всем, кто хочет выучить испанский.</span>
  </p>

  <p class="bi">
    <span class="bi-es">Todo el curso y todo el material</span> <span class="bi-ru">весь курс и все материалы</span>
    <span class="bi-es">son gratuitos.</span> <span class="bi-ru">бесплатны.</span>
    <span class="bi-es">Si te resulta útil</span> <span class="bi-ru">если он вам полезен</span>
    <span class="bi-es">y quieres contribuir,</span> <span class="bi-ru">и хотите поддержать,</span>
    <span class="bi-es">puedes donar lo que cuesta un café:</span> <span class="bi-ru">можно подарить проекту чашку кофе:</span>
    <span class="bi-es">me ayudará a continuar,</span> <span class="bi-ru">это поможет мне продолжать,</span>
    <span class="bi-es">corregir y ampliar.</span> <span class="bi-ru">исправлять и расширять курс.</span>
    <span class="bi-es">Y a cambio,</span> <span class="bi-ru">а взамен</span>
    <span class="bi-es">una palabra española en <a href="{{ route('muro') }}">la Стена</a>:</span> <span class="bi-ru">испанское слово на Стене:</span>
    <span class="bi-es">esperanza, cielo, bosque…</span> <span class="bi-ru">надежда, небо, лес…</span>
    <span class="bi-es">¡puede ser tuya!</span> <span class="bi-ru">оно может стать вашим!</span>
  </p>

  <p class="bi">
    <span class="bi-es">Gracias por la ayuda y el apoyo.</span> <span class="bi-ru">спасибо за помощь и поддержку.</span>
  </p>

  <p><button class="compartir" type="button" data-compartir>Поделиться курсом ↗</button></p>

  <p class="nosotros-firma">Con gratitud · С благодарностью<br>
  <b>Carlos Liñán</b> · Málaga</p>
  </div>

</div>
@endsection
