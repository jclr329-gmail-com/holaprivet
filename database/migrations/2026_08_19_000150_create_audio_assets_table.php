<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audio_assets', function (Blueprint $table) {
            $table->id();
            // El hash del texto evita generar dos veces la misma frase.
            $table->char('hash', 64)->unique();
            $table->text('text_es');
            $table->string('voice', 40)->default('narrador');
            $table->string('path', 255)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('engine', 40)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            $table->index('voice');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_assets');
    }
};
