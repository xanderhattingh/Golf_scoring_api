<?php

namespace Database\Seeders;

use App\Models\CourseHoles;
use App\Models\Courses;
use App\Models\CourseTees;
use App\Models\Tees;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Imports the JSON produced by `php artisan handicaps:scrape` into
 * courses / tees / course_tees / course_holes.
 *
 * Idempotent: re-running matches existing courses by name and tees by
 * (name, description) so it updates rather than duplicates.
 */
class HandicapsCoursesSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('app/handicaps_courses.json');

        if (! file_exists($path)) {
            $this->command->error("Data file not found: {$path}");
            $this->command->warn('Run `php artisan handicaps:scrape` first.');
            return;
        }

        $clubs = json_decode(file_get_contents($path), true);
        if (! is_array($clubs)) {
            $this->command->error('Could not decode the data file.');
            return;
        }

        // courses.created_by is a required FK — attribute imported courses to the first user
        $ownerId = User::orderBy('id')->value('id');
        if (! $ownerId) {
            $this->command->error('No users exist to own the imported courses.');
            return;
        }

        // Gender -> tee "description" (the tees table uses description for the player group)
        $genderLabel = ['M' => "Men's", 'F' => "Ladies'"];

        $courseCount = 0;
        $teeCount    = 0;

        foreach ($clubs as $club) {
            // Club-level details apply to every course at that club
            $details = [
                'latitude'  => $club['latitude'] ?? null,
                'longitude' => $club['longitude'] ?? null,
                'phone'     => $club['phone'] ?? null,
            ];

            foreach (($club['courses'] ?? []) as $courseData) {
                $name = trim($courseData['name'] ?? '');
                if ($name === '') {
                    continue;
                }

                DB::transaction(function () use ($courseData, $name, $ownerId, $genderLabel, $details, &$courseCount, &$teeCount) {
                    $holeCount = max(array_map(fn ($t) => count($t['holes']), $courseData['tees'] ?: [[ 'holes' => [] ]]));

                    $course = Courses::firstOrCreate(
                        ['name' => $name],
                        ['num_holes' => $holeCount ?: 18, 'created_by' => $ownerId]
                    );
                    $courseCount++;

                    // Backfill coords/phone (also updates existing courses on re-run);
                    // never overwrite an existing value with null
                    $fill = array_filter($details, fn ($v) => $v !== null && $v !== '');
                    if ($fill) {
                        $course->fill($fill);
                        if ($course->isDirty()) {
                            $course->save();
                        }
                    }

                    foreach ($courseData['tees'] as $teeData) {
                        $gender      = $teeData['gender'] ?? 'M';
                        $description = $genderLabel[$gender] ?? "Men's";

                        // Master tee row, distinct per (name, gender) so a course can
                        // carry both a Men's "Red" and a Ladies' "Red" without colliding.
                        $tee = Tees::firstOrCreate(
                            ['name' => $teeData['name'], 'gender' => $gender],
                            ['description' => $description, 'colour_code' => $this->normaliseColour($teeData['colour'])]
                        );

                        $courseTee = CourseTees::updateOrCreate(
                            ['course_id' => $course->id, 'tee_id' => $tee->id],
                            [
                                'course_rating' => $teeData['course_rating'],
                                'slope_rating'  => $teeData['slope_rating'],
                                'total_meters'  => $teeData['total_meters'] ?: null,
                                'total_yards'   => $teeData['total_yards'] ?: null,
                            ]
                        );
                        $teeCount++;

                        foreach ($teeData['holes'] as $hole) {
                            CourseHoles::updateOrCreate(
                                ['course_tee_id' => $courseTee->id, 'hole_number' => $hole['hole_number']],
                                [
                                    'par'          => $hole['par'],
                                    'stroke_index' => $hole['stroke_index'],
                                    'meters'       => $hole['meters'] ?: null,
                                    'yards'        => $hole['yards'] ?: null,
                                ]
                            );
                        }
                    }
                });
            }
        }

        $this->command->info("Imported/updated {$courseCount} courses and {$teeCount} tees.");
    }

    /**
     * handicaps.co.za gives a bare hex like "ffffff" — store it as "#ffffff".
     */
    private function normaliseColour(?string $colour): ?string
    {
        if (! $colour) {
            return null;
        }
        $colour = ltrim(trim($colour), '#');
        return $colour === '' ? null : '#' . strtolower($colour);
    }
}
