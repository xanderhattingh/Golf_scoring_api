<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $methods = [
            ['id' => 7, 'name' => 'Medal', 'description' => 'Net stroke play — gross strokes minus playing handicap, lowest net wins'],
            ['id' => 8, 'name' => 'Four Ball Alliance', 'description' => 'Team Stableford — the best N scores per hole count, set per par'],
        ];

        foreach ($methods as $m) {
            DB::table('scoring_methods')->updateOrInsert(
                ['id' => $m['id']],
                $m + ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('scoring_methods')->whereIn('id', [7, 8])->delete();
    }
};
