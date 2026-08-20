<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hito 7: cuentas y progreso con respuestas.
     *
     * - users.google_id: quien entra con Google no necesita contrasena.
     * - progress.answers_json: las respuestas dadas ({numero: letra}), para
     *   que el progreso viaje entre dispositivos, no solo el "hecho/no hecho".
     * - progress.score_*: la nota del resumen (18 de 20) sin recalcular.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id', 30)->nullable()->unique()->after('password');
        });

        Schema::table('progress', function (Blueprint $table) {
            $table->json('answers_json')->nullable()->after('open_count');
            $table->smallInteger('score_num')->unsigned()->nullable()->after('answers_json');
            $table->smallInteger('score_den')->unsigned()->nullable()->after('score_num');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('google_id'));
        Schema::table('progress', fn (Blueprint $t) => $t->dropColumn(['answers_json', 'score_num', 'score_den']));
    }
};
