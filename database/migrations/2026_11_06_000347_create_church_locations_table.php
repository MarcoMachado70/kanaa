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
        Schema::create('church_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('city');
            $table->char('state', 2);
            $table->string('neighborhood');
            $table->string('address');
            $table->text('regular_schedule')->nullable();
            $table->text('accessibility')->nullable();
            $table->text('visitor_information')->nullable();
            $table->boolean('welcomes_visitors')->default(false);
            $table->timestamps();
            $table->index(['city', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('church_locations');
    }
};
