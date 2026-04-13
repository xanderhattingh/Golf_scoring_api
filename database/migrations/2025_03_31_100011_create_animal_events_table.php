<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained('rounds')->onDelete('cascade');
            $table->foreignId('round_user_id')->constrained('round_users')->onDelete('cascade');
            $table->tinyInteger('hole_number');
            $table->enum('animal_type', ['tree', 'water', 'bunker', 'three_putt']);
            $table->timestamp('event_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_events');
    }
};
