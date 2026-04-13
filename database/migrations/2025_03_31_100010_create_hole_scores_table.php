<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hole_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained('rounds')->onDelete('cascade');
            $table->foreignId('round_team_id')->nullable()->constrained('round_teams');
            $table->foreignId('round_user_id')->constrained('round_users')->onDelete('cascade');
            $table->tinyInteger('hole_number');
            $table->tinyInteger('strokes');
            $table->tinyInteger('points')->default(0);
            $table->boolean('has_pink_ball')->default(false);
            $table->timestamps();

            $table->unique(['round_id', 'round_user_id', 'hole_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hole_scores');
    }
};
