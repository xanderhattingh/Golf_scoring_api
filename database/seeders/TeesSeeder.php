<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeesSeeder extends Seeder
{
    public function run(): void
    {
        $tees = [
            [
                'id' => 1,
                'name' => 'Yellow',
                'description' => "Pro's",
                'colour_code' => '#FFD700',
            ],
            [
                'id' => 2,
                'name' => 'White',
                'description' => "Men's",
                'colour_code' => '#FFFFFF',
            ],
            [
                'id' => 3,
                'name' => 'Red',
                'description' => "Ladies'",
                'colour_code' => '#DC143C',
            ],
            [
                'id' => 4,
                'name' => 'Blue',
                'description' => "Seniors",
                'colour_code' => '#4169E1',
            ],
        ];

        foreach ($tees as $tee) {
            DB::table('tees')->updateOrInsert(
                ['id' => $tee['id']],
                $tee
            );
        }
    }
}
