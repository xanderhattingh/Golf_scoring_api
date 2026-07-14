<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            if (! Schema::hasColumn('tournaments', 'scoring_config')) {
                // Mirrors rounds.scoring_config — e.g. Four Ball Alliance:
                // {"alliance":{"par3":2,"par4":4,"par5":4}}. Carried into joined rounds.
                $table->json('scoring_config')->nullable()->after('scoring_method_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            if (Schema::hasColumn('tournaments', 'scoring_config')) {
                $table->dropColumn('scoring_config');
            }
        });
    }
};
