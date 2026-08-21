<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hito 10: el area de gestion y los materiales en base de datos.
     *
     * - admin_users: los correos con acceso a /gestion. Se alimenta A MANO
     *   en phpMyAdmin: sin interfaz de alta, a proposito.
     * - resources: los materiales de la pagina «Материалы», hasta ahora en
     *   config/recursos.php. El peso NO se guarda: se lee del archivo.
     */
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 190)->unique();
            $table->timestamps();
        });

        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->enum('categoria', ['descarga', 'enlace']);
            $table->string('titulo', 190);
            $table->string('nota', 500)->nullable();
            $table->string('tipo', 20)->default('pdf');       // pdf | enlace
            $table->string('archivo', 190)->nullable();       // en descargas/
            $table->string('url', 300)->nullable();           // si es enlace
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('visible')->default(true);
            $table->timestamps();
            $table->index(['categoria', 'visible', 'orden']);
        });

        // Siembra: lo que hoy vive en config/recursos.php
        $ahora = now();
        $d = fn ($o, $archivo, $titulo, $nota) => [
            'categoria' => 'descarga', 'tipo' => 'pdf', 'archivo' => $archivo,
            'titulo' => $titulo, 'nota' => $nota, 'orden' => $o,
            'created_at' => $ahora, 'updated_at' => $ahora,
        ];
        $e = fn ($o, $url, $titulo, $nota) => [
            'categoria' => 'enlace', 'tipo' => 'enlace', 'url' => $url,
            'titulo' => $titulo, 'nota' => $nota, 'orden' => $o,
            'created_at' => $ahora, 'updated_at' => $ahora,
        ];

        DB::table('resources')->insert([
            $d(1, 'primeras-100-frases.pdf', 'Первые 100 фраз',
               'Сто фраз, с которыми можно жить в Испании с первого дня — по темам, с переводом. Распечатайте и держите под рукой.'),
            $d(2, 'alfabeto-y-sonidos.pdf', 'Алфавит и трудные звуки',
               'Как читается испанский и какие звуки сложны именно для нас — лист, который держат рядом весь первый месяц.'),
            $d(3, 'acentos.pdf', 'Ударение и знак тильде',
               'Agudas, llanas и esdrújulas: два правила и один знак, чтобы правильно читать вслух любое испанское слово.'),
            $d(4, 'numeros-hora-fechas.pdf', 'Числа, время и даты',
               'От нуля до миллиона, который час и какое сегодня число — всё, что забывается, на одном конспекте.'),
            $d(5, 'preposiciones.pdf', 'Предлоги испанского языка',
               'Все предлоги с примерами и типичными ошибками русскоговорящих — на нескольких страницах, чтобы распечатать и держать под рукой.'),
            $d(6, 'verbos-conjugados.pdf', 'Самые нужные глаголы, проспрягованные',
               'Карта времён, неправильные глаголы по семьям и словарь спряжений 68 глаголов.'),
            $d(7, 'falsos-amigos.pdf', 'Ловушки и ложные друзья',
               'Семнадцать пар слов, на которых спотыкаются русскоговорящие, — и главная мысль по каждой.'),
            $e(1, 'https://context.reverso.net/перевод/испанский-русский/', 'Reverso Context',
               'Перевод в контексте: как слово живёт в настоящих фразах, испанский ↔ русский.'),
            $e(2, 'https://conjugator.reverso.net/conjugation-spanish.html', 'Reverso Conjugator',
               'Спряжение любого испанского глагола во всех временах.'),
            $e(3, 'https://forvo.com/languages/es/', 'Forvo',
               'Произношение слов, записанное носителями — включая андалусский вариант.'),
            $e(4, 'https://dle.rae.es', 'Diccionario RAE',
               'Главный толковый словарь испанского языка. Когда сомневаетесь — сюда.'),
            $e(5, 'https://www.rtve.es/play/', 'RTVE Play',
               'Испанское телевидение бесплатно: новости, сериалы и документальные фильмы — лучшая тренировка для слуха.'),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
        Schema::dropIfExists('admin_users');
    }
};
