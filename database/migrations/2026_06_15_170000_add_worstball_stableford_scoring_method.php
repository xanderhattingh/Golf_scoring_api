<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('scoring_methods')->updateOrInsert(
            ['id' => 10],
            [
                'name' => 'Worst Ball Stableford',
                'description' => 'Teams of two — the worse Stableford score on each hole counts toward the team total',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('scoring_methods')->where('id', 10)->delete();
    }
};
