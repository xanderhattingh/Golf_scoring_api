<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Scrapes course / tee / hole data from the public handicaps.co.za API.
 *
 * Endpoint chain (all GET, public):
 *   getCourses?clubId={id}                         -> [{ CourseId, Name }]
 *   getMarkers?courseId={id}&gender={M|F}&isNineHoles=false&memberUid=
 *                                                  -> [{ DisplayMarkerName, MarkerColor,
 *                                                        UsgaNzcr, SlopeRating, Holes[18] }]
 *
 * Writes a single JSON file that the HandicapsCoursesSeeder consumes — the
 * network scrape and the DB import are deliberately separate steps so the slow,
 * rate-limited fetch only has to run once.
 */
class ScrapeHandicapsCourses extends Command
{
    protected $signature = 'handicaps:scrape
        {--from=1 : First clubId to fetch (inclusive)}
        {--to=289 : Last clubId to fetch (inclusive)}
        {--delay=400 : Delay between HTTP requests in milliseconds (rate limit)}
        {--genders=M,F : Comma-separated genders to fetch markers for}
        {--details-only : Skip courses/markers; only backfill club details (lat/long/phone) into an existing file}
        {--out= : Output path (defaults to storage/app/handicaps_courses.json)}';

    protected $description = 'Scrape course/tee/hole data from handicaps.co.za into a JSON file for seeding';

    private string $base = 'https://www.handicaps.co.za/api/clubs';

    public function handle(): int
    {
        $from = (int)$this->option('from');
        $to = (int)$this->option('to');
        $delayMs = (int)$this->option('delay');
        $genders = array_filter(array_map('trim', explode(',', (string)$this->option('genders'))));
        $out = $this->option('out') ?: storage_path('app/handicaps_courses.json');

        // Fast path: only add club details (lat/long/phone) to an already-scraped file
        if ($this->option('details-only')) {
            return $this->enrichDetails($out, $delayMs);
        }

        $this->info("Scraping clubs {$from}..{$to} (genders: " . implode(',', $genders) . ", delay: {$delayMs}ms)");

        $clubs = [];
        $errors = [];
        $bar = $this->output->createProgressBar($to - $from + 1);
        $bar->start();

        for ($clubId = $from; $clubId <= $to; $clubId++) {
            try {
                $courses = $this->getJson('getCourses', ['clubId' => $clubId], $delayMs);

                if (empty($courses)) {
                    $bar->advance();
                    continue;
                }

                $clubCourses = [];

                foreach ($courses as $course) {
                    $courseId = $course['CourseId'] ?? null;
                    if (!$courseId) {
                        continue;
                    }

                    $tees = [];
                    foreach ($genders as $gender) {
                        $markers = $this->getJson('getMarkers', [
                            'courseId' => $courseId,
                            'gender' => $gender,
                            'isNineHoles' => 'false',
                            'memberUid' => '',
                        ], $delayMs);

                        foreach (($markers ?: []) as $marker) {
                            $tee = $this->mapMarker($marker, $gender);
                            if ($tee) {
                                $tees[] = $tee;
                            }
                        }
                    }

                    if (!empty($tees)) {
                        $clubCourses[] = [
                            'course_id' => $courseId,
                            'name' => trim($course['Name'] ?? ''),
                            'tees' => $tees,
                        ];
                    }
                }

                if (!empty($clubCourses)) {
                    // One details call per club (lat/long/phone) — only when it has courses
                    $details = $this->mapClubDetails($this->getJson('GetClubDetails', ['clubId' => $clubId], $delayMs));

                    $clubs[] = array_merge(
                        ['club_id' => $clubId],
                        $details,
                        ['courses' => $clubCourses]
                    );
                }
            } catch (\Throwable $e) {
                $errors[] = "club {$clubId}: " . $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        file_put_contents($out, json_encode($clubs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $courseCount = array_sum(array_map(fn($c) => count($c['courses']), $clubs));
        $this->info("Done. {$courseCount} courses from " . count($clubs) . " clubs written to {$out}");

        if ($errors) {
            $this->warn(count($errors) . ' request(s) failed:');
            foreach (array_slice($errors, 0, 20) as $err) {
                $this->line('  - ' . $err);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Backfill club details (lat/long/phone) into an already-scraped file,
     * one GetClubDetails call per club — avoids re-fetching courses & markers.
     */
    private function enrichDetails(string $out, int $delayMs): int
    {
        if (! file_exists($out)) {
            $this->error("No file at {$out} — run a full scrape first.");
            return self::FAILURE;
        }

        $clubs = json_decode(file_get_contents($out), true);
        if (! is_array($clubs)) {
            $this->error('Could not decode the existing file.');
            return self::FAILURE;
        }

        $this->info('Enriching ' . count($clubs) . " clubs with details (delay: {$delayMs}ms)…");
        $bar = $this->output->createProgressBar(count($clubs));
        $bar->start();

        $withCoords = 0;
        foreach ($clubs as &$club) {
            try {
                $details = $this->mapClubDetails($this->getJson('GetClubDetails', ['clubId' => $club['club_id']], $delayMs));
                $club = array_merge($club, $details);
                if (! empty($club['latitude'])) {
                    $withCoords++;
                }
            } catch (\Throwable $e) {
                // leave this club's details as-is
            }
            $bar->advance();
        }
        unset($club);

        $bar->finish();
        $this->newLine(2);

        file_put_contents($out, json_encode($clubs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info("Done. {$withCoords}/" . count($clubs) . " clubs now have coordinates. Updated {$out}");

        return self::SUCCESS;
    }

    /**
     * Normalise a GetClubDetails response into lat/long/phone.
     * Treats 0/blank coordinates as null (0,0 is in the ocean).
     */
    private function mapClubDetails(array $d): array
    {
        $lat = $d['Latitude'] ?? null;
        $lng = $d['Longitude'] ?? null;
        $phone = trim((string) ($d['Phone'] ?? ''));

        return [
            'latitude'  => (is_numeric($lat) && (float) $lat != 0.0) ? (float) $lat : null,
            'longitude' => (is_numeric($lng) && (float) $lng != 0.0) ? (float) $lng : null,
            'phone'     => $phone !== '' ? $phone : null,
        ];
    }

    /**
     * Map a raw getMarkers marker into our normalised tee shape.
     */
    private function mapMarker(array $marker, string $gender): ?array
    {
        $holes = [];
        foreach (($marker['Holes'] ?? []) as $hole) {
            $par = (int)($hole['Par'] ?? 0);
            // Skip placeholder/empty holes (the API sometimes returns zeroed rows)
            if ($par <= 0) {
                continue;
            }
            $holes[] = [
                'hole_number' => (int)($hole['Alias'] ?? $hole['HoleNo'] ?? 0),
                'par' => $par,
                'stroke_index' => (int)($hole['Stroke'] ?? 0),
                'meters' => (int)($hole['DistanceMetres'] ?? 0),
                'yards' => (int)($hole['DistanceYards'] ?? 0),
            ];
        }

        if (count($holes) === 0) {
            return null;
        }

        return [
            'gender' => $gender,
            'name' => trim($marker['DisplayMarkerName'] ?? '') ?: 'Tee',
            'colour' => $marker['MarkerColor'] ?? null,
            'course_rating' => $marker['UsgaNzcr'] ?? null,
            'slope_rating' => $marker['SlopeRating'] ?? null,
            'total_meters' => (int)(($marker['FrontNineDistanceMetres'] ?? 0) + ($marker['BackNineDistanceMetres'] ?? 0)),
            'total_yards' => (int)(($marker['FrontNineDistanceYards'] ?? 0) + ($marker['BackNineDistanceYards'] ?? 0)),
            'holes' => $holes,
        ];
    }

    /**
     * Issue a rate-limited GET and return the decoded JSON array (or []).
     */
    private function getJson(string $path, array $query, int $delayMs): array
    {
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }

        $response = Http::timeout(25)
            ->retry(2, 500)
            ->acceptJson()
            ->get("{$this->base}/{$path}", $query);

        if (!$response->successful()) {
            throw new \RuntimeException("{$path} returned HTTP {$response->status()}");
        }

        return $response->json() ?? [];
    }
}
