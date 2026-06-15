<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('creator_id')->constrained("users")->onDelete('cascade');
            $table->foreignId('course_id')->constrained("courses")->onDelete('cascade');
            $table->foreignId('tee_id')->constrained("course_tees")->onDelete('cascade');
            $table->foreignId('scoring_method_id')->constrained("scoring_methods")->onDelete('cascade');
            /*
             * Status = 0 Created/Started
             * Status = 1 Completed/Finished
             */
            $table->tinyInteger("status")->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
