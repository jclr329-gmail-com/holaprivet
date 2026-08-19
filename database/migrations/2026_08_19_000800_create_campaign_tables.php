<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('level')->unsigned();
            $table->smallInteger('pass_number')->unsigned()->default(1);
            // Semilla del orden aleatorio: permite reproducirlo sin guardarlo.
            $table->unsignedInteger('seed');
            $table->enum('status', ['abierta', 'cerrada'])->default('abierta');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->unique(['user_id', 'level', 'pass_number'], 'uk_campana');
            $table->index(['user_id', 'status']);
        });

        Schema::create('campaign_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('exercise_options')->cascadeOnDelete();
            $table->boolean('is_correct');
            $table->timestamp('answered_at')->useCurrent();

            // ESTA es la regla del sistema de campanas: una sola respuesta por
            // ejercicio y campana. La impone la base de datos, no el navegador.
            $table->unique(['campaign_id', 'exercise_id'], 'uk_respuesta_unica');
        });

        Schema::create('campaign_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->enum('scope', ['global', 'piece', 'tag']);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->smallInteger('correct')->unsigned()->default(0);
            $table->smallInteger('total')->unsigned()->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->index(['campaign_id', 'scope'], 'idx_nota');
        });

        Schema::create('training_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('exercise_options')->cascadeOnDelete();
            $table->boolean('is_correct');
            $table->timestamp('answered_at')->useCurrent();
            // Sin restriccion de unicidad: en entrenamiento se repite sin limite.
            $table->index(['user_id', 'exercise_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_answers');
        Schema::dropIfExists('campaign_scores');
        Schema::dropIfExists('campaign_answers');
        Schema::dropIfExists('campaigns');
    }
};
