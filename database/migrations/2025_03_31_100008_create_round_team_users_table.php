<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('round_team_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_team_id')->constrained('round_teams')->onDelete('cascade');
            $table->foreignId('round_user_id')->constrained('round_users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('round_team_users');
    }
};
