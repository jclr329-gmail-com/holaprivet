<?php

/*
 * La pagina de recursos: materiales descargables y enlaces externos.
 *
 * Anadir un descargable = subir el PDF a recursos/ (en la raiz del
 * subdominio, junto a audio/ e img/) y anadir aqui su entrada. Si el
 * archivo no existe todavia, la tarjeta no se pinta: se puede preparar
 * la lista antes de subir los PDF.
 */

return [

    'descargables' => [
        [
            'archivo' => 'primeras-100-frases.pdf',
            'titulo'  => 'Первые 100 фраз',
            'nota'    => 'Сто фраз, с которыми можно жить в Испании с первого дня — по темам, с переводом. Распечатайте и держите под рукой.',
        ],
        [
            'archivo' => 'falsos-amigos.pdf',
            'titulo'  => 'Ловушки и ложные друзья',
            'nota'    => 'Семнадцать пар слов, на которых спотыкаются русскоговорящие, — и главная мысль по каждой, на нескольких страницах.',
        ],
        [
            'archivo' => 'alfabeto-y-sonidos.pdf',
            'titulo'  => 'Алфавит и трудные звуки',
            'nota'    => 'Как читается испанский и какие звуки сложны именно для нас — лист, который держат рядом весь первый месяц.',
        ],
        [
            'archivo' => 'numeros-hora-fechas.pdf',
            'titulo'  => 'Числа, время и даты',
            'nota'    => 'От нуля до миллиона, который час и какое сегодня число — всё, что забывается, на одном конспекте.',
        ],
        [
            'archivo' => 'preposiciones.pdf',
            'titulo'  => 'Предлоги испанского языка',
            'nota'    => 'Все предлоги с примерами и типичными ошибками русскоговорящих — на нескольких страницах, чтобы распечатать и держать под рукой.',
        ],
        [
            'archivo' => 'verbos-conjugados.pdf',
            'titulo'  => 'Самые нужные глаголы, проспрягованные',
            'nota'    => 'Таблицы спряжения самых употребимых глаголов — настоящее, прошедшее и будущее время на одном развороте.',
        ],
    ],

    'enlaces' => [
        [
            'url'    => 'https://context.reverso.net/перевод/испанский-русский/',
            'titulo' => 'Reverso Context',
            'nota'   => 'Перевод в контексте: как слово живёт в настоящих фразах, испанский ↔ русский.',
        ],
        [
            'url'    => 'https://conjugator.reverso.net/conjugation-spanish.html',
            'titulo' => 'Reverso Conjugator',
            'nota'   => 'Спряжение любого испанского глагола во всех временах.',
        ],
        [
            'url'    => 'https://forvo.com/languages/es/',
            'titulo' => 'Forvo',
            'nota'   => 'Произношение слов, записанное носителями — включая андалусский вариант.',
        ],
        [
            'url'    => 'https://dle.rae.es',
            'titulo' => 'Diccionario RAE',
            'nota'   => 'Главный толковый словарь испанского языка. Когда сомневаетесь — сюда.',
        ],
        [
            'url'    => 'https://www.rtve.es/play/',
            'titulo' => 'RTVE Play',
            'nota'   => 'Испанское телевидение бесплатно: новости, сериалы и документальные фильмы — лучшая тренировка для слуха.',
        ],
    ],

];
