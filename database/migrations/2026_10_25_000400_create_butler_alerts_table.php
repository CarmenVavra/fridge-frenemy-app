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
        Schema::create('butler_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('household_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('fridge_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tone', 20);
            $table->text('message');
            $table->string('source', 20)->default('fallback');
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->index(['household_id', 'dismissed_at', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('butler_alerts');
    }
};
