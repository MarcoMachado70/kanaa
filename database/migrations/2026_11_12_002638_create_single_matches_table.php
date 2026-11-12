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
        Schema::create('single_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('low_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('high_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->unique(['low_user_id', 'high_user_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('single_matches');
    }
};
