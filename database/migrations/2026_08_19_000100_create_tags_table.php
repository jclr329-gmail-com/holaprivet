<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name_es', 120);
            $table->string('name_ru', 120);
            $table->enum('kind', ['gramatical', 'situacional', 'lexico', 'otro'])
                  ->default('gramatical');
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('taggable_id');
            $table->string('taggable_type', 40);
            $table->index(['taggable_type', 'taggable_id'], 'idx_taggable');
            $table->unique(['tag_id', 'taggable_id', 'taggable_type'], 'uk_taggable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }
};
