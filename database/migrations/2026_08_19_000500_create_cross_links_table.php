<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cross_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_piece_id')->constrained('pieces')->cascadeOnDelete();
            // Se guarda el slug y no el id: al importar, el destino puede no
            // existir todavia. Se resuelve al terminar la importacion.
            $table->string('to_slug', 64);
            $table->foreignId('to_piece_id')->nullable()
                  ->constrained('pieces')->nullOnDelete();
            $table->string('label', 200)->nullable();
            $table->smallInteger('position')->unsigned()->default(0);
            $table->index('to_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cross_links');
    }
};
