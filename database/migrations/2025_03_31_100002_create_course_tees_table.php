<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_tees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('tee_id')->constrained('tees');
            $table->decimal('course_rating', 4, 1)->nullable();
            $table->integer('slope_rating')->nullable();
            $table->integer('total_yards')->nullable();
            $table->integer('total_meters')->nullable();
            $table->timestamps();
            
            // Prevent duplicate tee assignments for same course
            $table->unique(['course_id', 'tee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_tees');
    }
};
