<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('position')->unsigned();
            $table->enum('kind', ['opcion_multiple', 'verdadero_falso', 'hueco'])
                  ->default('opcion_multiple');
            $table->text('prompt');
            $table->text('explanation_ru')->nullable();
            // Si es de repaso, puntua para la pieza de origen, no para esta.
            $table->string('review_of_slug', 64)->nullable();
            $table->boolean('is_calque_trap')->default(false);
            $table->unique(['piece_id', 'position'], 'uk_ejercicio');
            $table->index('review_of_slug');
        });

        Schema::create('exercise_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->char('letter', 1);
            $table->string('text', 255);
            $table->boolean('is_correct')->default(false);
            $table->unique(['exercise_id', 'letter'], 'uk_opcion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_options');
        Schema::dropIfExists('exercises');
    }
};
