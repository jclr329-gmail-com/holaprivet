@extends('layout')
@section('titulo', 'Испанский для жизни в Испании')

@section('cuerpo')
<div class="ancho">

  <div class="hero">
    <div class="eyebrow"><span class="pipe"></span> Бесплатный курс испанского</div>
    <h1>Испанский для тех, кто уже здесь живёт</h1>
    <p class="lema">Не «учебник с нуля до C2», а язык для настоящей жизни: поздороваться
    с соседкой, спросить дорогу, объясниться у врача, снять квартиру. Объяснения
    по-русски, примеры из Испании, и весь курс — бесплатно.</p>

    <div class="cta">
      <a class="boton" href="{{ route('registro') }}">Создать аккаунт</a>
      <a class="boton claro" href="{{ route('login') }}">Войти</a>
      <a class="enlace-suave" href="{{ route('curso') }}">Смотреть курс без регистрации →</a>
    </div>
    <p class="nota-cta">Аккаунт нужен только для одного: чтобы ваш прогресс сохранялся
    и ждал вас на любом устройстве.</p>

    <div class="cifras">
      <div><b>{{ $modulos }}</b> модулей</div>
      <div><b>{{ $cuentos }}</b> рассказов</div>
      <div><b>{{ $fichas }}</b> карточек</div>
      <div><b>{{ number_format($ejercicios, 0, ',', ' ') }}</b> упражнений</div>
    </div>
  </div>

  <div class="nivel-cab">
    <div class="n">Как устроен курс</div>
    <h2>Три уровня — как три года жизни в Испании</h2>
    <p>Каждый модуль — одна жизненная ситуация: сцена с диалогом, объяснение
    по-русски, фразы «на вынос» и упражнения с мгновенной проверкой. Рассказы
    открываются по мере продвижения, как главы одной истории.</p>
  </div>

  <div class="rejilla tres">
    <div class="tarjeta quieta">
      <div class="num">Уровень 1</div>
      <div class="tit">Ничего не знаю, но очень хочу</div>
      <div class="sub">Поздороваться, купить хлеб, спросить дорогу. Первые
      победы — с первого модуля.</div>
    </div>
    <div class="tarjeta quieta">
      <div class="num">Уровень 2</div>
      <div class="tit">Уже строю свои фразы</div>
      <div class="sub">Рассказать о себе, о доме, о планах. Прошедшее время
      перестаёт пугать.</div>
    </div>
    <div class="tarjeta quieta">
      <div class="num">Уровень 3</div>
      <div class="tit">Решаю свои дела по-испански</div>
      <div class="sub">Врач, квартира, документы, работа. Испания начинает
      отвечать вам как своей.</div>
    </div>
  </div>

  <div class="nivel-cab">
    <div class="n">Метод</div>
    <h2>Четыре вещи, которые делают курс живым</h2>
  </div>

  <div class="rejilla dos">
    <div class="tarjeta quieta">
      <div class="tit">Сцены из настоящей жизни</div>
      <div class="sub">Не «Мария идёт в библиотеку», а рынок, аптека, звонок
      хозяину квартиры. Одни и те же герои растут вместе с вами все три уровня.</div>
    </div>
    <div class="tarjeta quieta">
      <div class="tit">Всё звучит</div>
      <div class="sub">Нажмите на любую испанскую фразу — она произнесётся.
      Диалоги можно слушать целиком, рассказы — как аудиокниги.</div>
    </div>
    <div class="tarjeta quieta">
      <div class="tit">Объяснения по-русски, про ваши ошибки</div>
      <div class="sub">Курс написан для русскоговорящих: ser и estar, артикли,
      ложные друзья — разобрано именно то, на чём мы спотыкаемся.</div>
    </div>
    <div class="tarjeta quieta">
      <div class="tit">«Отложите телефон»</div>
      <div class="sub">Каждый модуль заканчивается заданием в реальном мире:
      спросить дорогу, поздороваться с соседом. Язык — там, не в экране.</div>
    </div>
  </div>

  <div class="cierre-bienvenida">
    <h2>Начнём?</h2>
    <p>Первый модуль занимает полчаса. К его концу вы поздороваетесь по-испански
    — и вам ответят.</p>
    <a class="boton" href="{{ route('registro') }}">Начать курс</a>
    <button class="compartir" type="button" data-compartir>Поделиться курсом ↗</button>
  </div>

</div>
@endsection
