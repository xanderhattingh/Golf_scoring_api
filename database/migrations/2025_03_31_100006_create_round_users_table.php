<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('round_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained('rounds')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->tinyInteger('round_handicap');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('round_users');
    }
};
