<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_cents');
            $table->char('currency', 3)->default('EUR');
            $table->enum('status', ['pendiente', 'pagado', 'cancelado', 'reembolsado'])
                  ->default('pendiente');
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('word_id')->constrained('wall_words')->cascadeOnDelete();
            $table->unsignedInteger('price_cents');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('gateway', ['stripe', 'paypal']);
            $table->string('gateway_ref', 120)->nullable();
            $table->unsignedInteger('amount_cents');
            // Guardar comision y neto: con importes de 3 EUR no es un detalle menor.
            $table->unsignedInteger('fee_cents')->nullable();
            $table->unsignedInteger('net_cents')->nullable();
            $table->enum('status', ['pendiente', 'completado', 'fallido', 'reembolsado'])
                  ->default('pendiente');
            $table->timestamp('paid_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
            $table->index('gateway_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
