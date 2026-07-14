<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScoringMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $scoringMethods = [
            [
                'id' => 1,
                'name' => 'Stroke Play',
                'description' => 'Total strokes for the round',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Stableford',
                'description' => 'Points based on score relative to par',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Match Play',
                'description' => 'Hole-by-hole competition',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Stableford with Pink',
                'description' => 'One player per hole gets double points with the pink ball',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => 'Stableford with Animals',
                'description' => 'Stableford with Tree, Water, Bunker, and 3-Putt tracking per 9 holes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'name' => 'Stableford with Animals and Pink',
                'description' => 'Stableford with Animals plus Pink Ball double points',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'name' => 'Medal',
                'description' => 'Net stroke play — gross strokes minus playing handicap, lowest net wins',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'name' => 'Four Ball Alliance',
                'description' => 'Team Stableford — the best N scores per hole count, set per par',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9,
                'name' => 'Betterball Stableford',
                'description' => 'Teams of two — the better Stableford score on each hole counts toward the team total',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 10,
                'name' => 'Worst Ball Stableford',
                'description' => 'Teams of two — the worse Stableford score on each hole counts toward the team total',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'name' => 'Two Ball Alliance',
                'description' => 'Team Stableford for a pair — the best N scores per hole count, set per par',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($scoringMethods as $method) {
            DB::table('scoring_methods')->updateOrInsert(
                ['id' => $method['id']],
                $method
            );
        }
    }
}
