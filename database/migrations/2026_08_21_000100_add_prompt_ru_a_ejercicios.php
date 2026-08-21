<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** El pulsador de traduccion: cada enunciado puede llevar su ruso. */
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->string('prompt_ru', 500)->nullable()->after('prompt');
        });
    }

    public function down(): void
    {
        Schema::table('exercises', fn (Blueprint $t) => $t->dropColumn('prompt_ru'));
    }
};
