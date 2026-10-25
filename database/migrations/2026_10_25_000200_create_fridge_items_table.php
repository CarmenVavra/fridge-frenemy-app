<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fridge_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('household_id')->constrained()->cascadeOnDelete();
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->integer('quantity')->default(1);
            $table->string('unit')->default('Stück');
            $table->date('expires_at');
            $table->enum('status', ['fresh', 'warning', 'expired', 'consumed', 'trashed'])->default('fresh');
            $table->integer('annoyance_level')->default(0);
            $table->timestamps();

            $table->index(['household_id', 'status', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fridge_items');
    }
};
