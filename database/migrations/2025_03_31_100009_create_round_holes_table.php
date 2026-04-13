<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('round_holes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained('rounds')->onDelete('cascade');
            $table->tinyInteger('hole_number');
            $table->tinyInteger('par');
            $table->tinyInteger('stroke_index');
            $table->timestamps();

            $table->unique(['round_id', 'hole_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('round_holes');
    }
};
