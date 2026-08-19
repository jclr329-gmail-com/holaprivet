<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['abierta', 'completada'])->default('abierta');
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->smallInteger('open_count')->unsigned()->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'piece_id'], 'uk_progreso');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress');
    }
};
