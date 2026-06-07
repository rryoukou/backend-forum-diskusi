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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username', 100)->unique();
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->string('avatar_url', 500)->nullable();
            $table->text('bio')->nullable();
            $table->integer('reputation_points')->default(0);
            $table->integer('level')->default(1);
            $table->boolean('is_banned')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
