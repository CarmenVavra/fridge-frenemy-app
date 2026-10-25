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
        Schema::create('household_stats', function (Blueprint $table) {
            $table->foreignUuid('household_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('saved_count')->default(0);
            $table->unsignedInteger('trashed_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_stats');
    }
};
