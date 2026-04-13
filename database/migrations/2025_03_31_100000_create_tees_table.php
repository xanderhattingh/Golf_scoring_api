<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tees', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Yellow, White, Red, Blue
            $table->string('description'); // Pros, Men's, Ladies', Seniors
            $table->string('colour_code')->nullable(); // hex color for UI
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tees');
    }
};
