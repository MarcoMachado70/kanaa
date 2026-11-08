<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('bio')->nullable();
            $table->string('city')->nullable();
            $table->boolean('public_profile')->default(false);
        });
        Schema::table('community_posts', function (Blueprint $table) {
            $table->foreignId('author_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('community_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bio', 'city', 'public_profile']);
        });
    }
};
