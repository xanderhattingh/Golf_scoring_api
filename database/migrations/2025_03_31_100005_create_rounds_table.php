<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses');
            $table->foreignId('scoring_method_id')->constrained('scoring_methods');
            $table->foreignId('created_by')->constrained('users');
            $table->date('date');
            $table->enum('format', ['individual', 'teams']);
            $table->boolean('completed')->default(false);
            $table->tinyInteger('current_hole')->default(1);
            $table->tinyInteger('starting_hole')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rounds');
    }
};
