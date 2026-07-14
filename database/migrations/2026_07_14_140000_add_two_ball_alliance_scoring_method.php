<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('scoring_methods')->updateOrInsert(
            ['id' => 11],
            [
                'name' => 'Two Ball Alliance',
                'description' => 'Team Stableford for a pair — the best N scores per hole count, set per par',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('scoring_methods')->where('id', 11)->delete();
    }
};
