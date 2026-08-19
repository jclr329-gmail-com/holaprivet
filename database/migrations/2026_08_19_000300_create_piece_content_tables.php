<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- Secciones de texto -------------------------------------------
        Schema::create('piece_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('number')->unsigned();
            $table->string('kind', 40);
            $table->string('title_es', 160)->nullable();
            $table->string('title_ru', 160)->nullable();
            $table->mediumText('body_md')->nullable();
            $table->mediumText('body_html')->nullable();
            $table->unique(['piece_id', 'number'], 'uk_seccion');
            $table->index('kind');
        });

        // --- Glosarios -----------------------------------------------------
        Schema::create('vocabulary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->enum('block', ['nuevas', 'conocidas', 'clave']);
            $table->smallInteger('position')->unsigned()->default(0);
            $table->string('term_es', 120);
            $table->string('term_ru', 120);
            $table->string('note', 160)->nullable();
            $table->string('seen_in_slug', 64)->nullable();
            $table->foreignId('audio_id')->nullable()
                  ->constrained('audio_assets')->nullOnDelete();
            $table->index(['piece_id', 'block', 'position'], 'idx_glosario');
            $table->index('term_es');
        });

        // --- Escenas y cuentos ---------------------------------------------
        Schema::create('dialogue_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('position')->unsigned();
            $table->string('character', 40)->nullable();
            $table->string('stage_note_ru', 200)->nullable();
            $table->text('text_es');
            $table->text('text_ru')->nullable();
            $table->boolean('is_break')->default(false);
            $table->foreignId('audio_id')->nullable()
                  ->constrained('audio_assets')->nullOnDelete();
            $table->unique(['piece_id', 'position'], 'uk_linea');
        });

        // --- Frases para llevar ---------------------------------------------
        Schema::create('phrases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('position')->unsigned();
            $table->string('text_es', 255);
            $table->string('text_ru', 255);
            $table->foreignId('audio_id')->nullable()
                  ->constrained('audio_assets')->nullOnDelete();
            $table->unique(['piece_id', 'position'], 'uk_frase');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phrases');
        Schema::dropIfExists('dialogue_lines');
        Schema::dropIfExists('vocabulary');
        Schema::dropIfExists('piece_sections');
    }
};
