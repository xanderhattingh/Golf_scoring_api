<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_holes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_tee_id')->constrained('course_tees')->onDelete('cascade');
            $table->tinyInteger('hole_number'); // 1-18
            $table->tinyInteger('par'); // 3, 4, 5
            $table->tinyInteger('stroke_index'); // 1-18
            $table->integer('yards')->nullable();
            $table->integer('meters')->nullable();
            $table->timestamps();
            
            // Prevent duplicate holes for same course_tee
            $table->unique(['course_tee_id', 'hole_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_holes');
    }
};
