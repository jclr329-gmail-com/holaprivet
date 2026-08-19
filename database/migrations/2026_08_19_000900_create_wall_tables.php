<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wall_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 40)->unique();
            $table->string('translation_ru', 80);
            $table->enum('kind', ['normal', 'especial'])->default('normal');
            $table->unsignedInteger('price_cents')->default(300);
            $table->smallInteger('grid_x')->unsigned()->default(0);
            $table->smallInteger('grid_y')->unsigned()->default(0);
            $table->tinyInteger('grid_w')->unsigned()->default(1);
            $table->tinyInteger('grid_h')->unsigned()->default(1);
            $table->enum('status', ['libre', 'reservada', 'ocupada'])->default('libre');
            $table->timestamp('reserved_until')->nullable();
            $table->foreignId('audio_id')->nullable()
                  ->constrained('audio_assets')->nullOnDelete();
            // Enlace al curso: convierte el muro en material didactico.
            $table->foreignId('vocabulary_id')->nullable()
                  ->constrained('vocabulary')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'kind']);
            $table->index(['grid_y', 'grid_x']);
        });

        Schema::create('wall_ownerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('word_id')->constrained('wall_words')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('display_name', 60);
            $table->string('dedication', 100)->nullable();
            $table->enum('moderation', ['pendiente', 'aprobada', 'rechazada'])
                  ->default('pendiente');
            $table->timestamp('moderated_at')->nullable();
            $table->string('moderated_note', 200)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->timestamp('grace_until');
            $table->enum('status', ['activa', 'en_gracia', 'caducada'])->default('activa');
            $table->foreignId('renewed_from_id')->nullable()
                  ->constrained('wall_ownerships')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'expires_at']);
            $table->index(['user_id', 'status']);
            $table->index('moderation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wall_ownerships');
        Schema::dropIfExists('wall_words');
    }
};
