<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pieces', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->enum('type', ['modulo', 'cuento', 'ficha_ojo', 'ficha_practica']);
            $table->tinyInteger('level')->unsigned()->nullable();
            $table->smallInteger('position')->unsigned()->default(0);

            $table->string('title_es', 160);
            $table->string('title_ru', 160);

            $table->smallInteger('duration_min')->unsigned()->nullable();
            $table->smallInteger('word_count')->unsigned()->nullable();

            $table->string('read_after_slug', 64)->nullable();
            $table->string('anchor_slug', 64)->nullable();

            $table->boolean('in_campaign')->default(true);
            $table->boolean('printable')->default(false);
            $table->enum('audio_status', ['pendiente', 'imprescindible', 'listo'])
                  ->default('pendiente');

            $table->text('image_prompt')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->json('characters')->nullable();

            $table->smallInteger('exercise_count')->unsigned()->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'level', 'position'], 'idx_orden');
            $table->index('published_at');
            $table->index('read_after_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pieces');
    }
};
