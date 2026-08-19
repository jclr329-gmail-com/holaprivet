<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_versions', function (Blueprint $table) {
            $table->id();
            $table->timestamp('imported_at')->useCurrent();
            $table->unsignedSmallInteger('file_count')->default(0);
            $table->unsignedSmallInteger('piece_count')->default(0);
            $table->unsignedSmallInteger('exercise_count')->default(0);
            $table->unsignedSmallInteger('error_count')->default(0);
            $table->enum('status', ['en_curso', 'correcta', 'con_errores', 'revertida'])
                  ->default('en_curso');
            $table->text('notes')->nullable();
        });

        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('content_versions')->cascadeOnDelete();
            $table->string('file', 120)->nullable();
            $table->enum('level', ['info', 'aviso', 'error'])->default('info');
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['version_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
        Schema::dropIfExists('content_versions');
    }
};
