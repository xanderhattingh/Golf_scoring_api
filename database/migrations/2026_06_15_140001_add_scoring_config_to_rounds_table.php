<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            if (! Schema::hasColumn('rounds', 'scoring_config')) {
                // Per-round settings for methods that need them, e.g. Four Ball
                // Alliance: {"alliance":{"par3":2,"par4":4,"par5":4}}
                $table->json('scoring_config')->nullable()->after('format');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            if (Schema::hasColumn('rounds', 'scoring_config')) {
                $table->dropColumn('scoring_config');
            }
        });
    }
};
