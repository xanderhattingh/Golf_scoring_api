<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Guarded so it's safe on databases where the column was already added out-of-band
        if (! Schema::hasColumn('tournaments', 'invite_code')) {
            Schema::table('tournaments', function (Blueprint $table) {
                // 6-digit join code, unique so it can be looked up
                $table->string('invite_code', 6)->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tournaments', 'invite_code')) {
            Schema::table('tournaments', function (Blueprint $table) {
                $table->dropColumn('invite_code');
            });
        }
    }
};
