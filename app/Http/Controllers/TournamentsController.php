<?php

namespace App\Http\Controllers;

use App\Models\CourseTees;
use App\Models\Tournaments;
use Illuminate\Http\Request;

class TournamentsController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'course_id' => 'required|exists:courses,id',
            'tee_id' => 'required|exists:course_tees,id',
            'scoring_method_id' => 'required|exists:scoring_methods,id',
            // Four Ball Alliance: how many scores count per hole, by par
            'scoring_config' => 'nullable|array',
            'scoring_config.alliance.par3' => 'nullable|integer|min:1|max:4',
            'scoring_config.alliance.par4' => 'nullable|integer|min:1|max:4',
            'scoring_config.alliance.par5' => 'nullable|integer|min:1|max:4',
        ]);

        // Tee must belong to the chosen course
        $tee = CourseTees::where('id', $validated['tee_id'])
            ->where('course_id', $validated['course_id'])
            ->first();

        if (! $tee) {
            return response()->json([
                'success' => false,
                'message' => 'Selected tee does not belong to this course',
            ], 422);
        }

        // Unique 6-digit invite code
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (Tournaments::where('invite_code', $code)->exists());

        $tournament = Tournaments::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'creator_id' => $user->id,
            'course_id' => $validated['course_id'],
            'tee_id' => $validated['tee_id'],
            'scoring_method_id' => $validated['scoring_method_id'],
            'scoring_config' => $validated['scoring_config'] ?? null,
            'status' => 0,
            'invite_code' => $code,
        ]);

        // Shape it like an index row so the frontend can drop it straight into the list
        $tournament->load(['course', 'scoring_method']);
        $tournament->setRelation('players', collect());
        $tournament->date = optional($tournament->created_at)->format('Y-m-d');

        return response()->json([
            'success' => true,
            'data' => $tournament,
        ], 201);
    }

    public function lookup($code)
    {
        // Public-ish within auth: anyone with the code can resolve a tournament's fixed settings
        $tournament = Tournaments::with([
            'course.courseTees.tee',
            'course.courseTees.holes',
            'scoring_method',
        ])->where('invite_code', $code)->first();

        if (! $tournament) {
            return response()->json([
                'success' => false,
                'message' => 'No tournament found with that code',
            ], 404);
        }

        // Return the full course/scoring objects so the round wizard can lock its
        // fields without relying on a preloaded course list.
        $course = $tournament->course;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'description' => $tournament->description,
                'course_id' => $tournament->course_id,
                'tee_id' => $tournament->tee_id,
                'scoring_method_id' => $tournament->scoring_method_id,
                'scoring_method' => $tournament->scoring_method,
                'scoring_config' => $tournament->scoring_config,
                'course' => $course ? [
                    'id' => $course->id,
                    'name' => $course->name,
                    'location' => $course->location,
                    'num_holes' => $course->num_holes,
                    'tees' => $course->courseTees->map(function ($courseTee) {
                        return [
                            'id' => $courseTee->id,
                            'tee_id' => $courseTee->tee_id,
                            'tee_name' => $courseTee->tee->name,
                            'tee_description' => $courseTee->tee->description,
                            'gender' => $courseTee->tee->gender,
                            'colour_code' => $courseTee->tee->colour_code,
                            'holes' => $courseTee->holes->map(fn ($hole) => [
                                'id' => $hole->id,
                                'hole_number' => $hole->hole_number,
                                'par' => $hole->par,
                                'stroke_index' => $hole->stroke_index,
                            ]),
                        ];
                    }),
                ] : null,
            ],
        ]);
    }

    public function index()
    {
        $user = auth()->user();

        // Tournaments the user created OR is playing in (a participant in one of its rounds)
        $tournaments = Tournaments::where(function ($query) use ($user) {
            $query->where('creator_id', $user->id)
                ->orWhereHas('rounds.round.roundUsers', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
        })
            ->with([
                'scoring_method',
                'course',
                'rounds.round',
                'rounds.round.roundHoles',
                'rounds.roundUsers.user',
                'rounds.roundUsers.holeScores',
                // Betterball: teams (of 1-2) per round
                'rounds.round.roundTeams.roundTeamUsers.roundUser.user',
                'rounds.round.roundTeams.roundTeamUsers.roundUser.holeScores',
            ])
            ->orderByDesc('created_at')
            ->get();

        $tournaments->each(function ($tournament) {
            // Derive the tournament date from its rounds (earliest), falling back to created_at
            $roundDate = $tournament->rounds
                ->map(fn ($tr) => optional($tr->round)->date)
                ->filter()
                ->min();
            $tournament->date = $roundDate
                ? $roundDate->format('Y-m-d')
                : optional($tournament->created_at)->format('Y-m-d');

            // Expose the underlying round ids so the client can pull full per-round
            // scorecards on demand (e.g. for the results PDF export).
            $tournament->round_ids = $tournament->rounds
                ->map(fn ($tr) => optional($tr->round)->id)
                ->filter()
                ->values();

            // Flatten every round's players into a single unique players list, each carrying
            // their progress (holes played, strokes, points) and per-round handicap.
            $players = $tournament->rounds
                ->flatMap(fn ($round) => $round->roundUsers->map(function ($roundUser) {
                    $user = $roundUser->user;
                    $user->handicap = $roundUser->round_handicap;
                    $user->holes_played = $roundUser->holeScores->pluck('hole_number')->unique()->count();
                    $user->strokes = (int) $roundUser->holeScores->sum('strokes');
                    $user->points = (int) $roundUser->holeScores->sum('points');

                    return $user;
                }))
                ->unique('id')
                ->values();

            $tournament->setRelation('players', $players);

            // Four Ball Alliance: each round is one alliance — rank teams by their
            // best-N-per-hole total (N by par, from the tournament's config).
            if ((int) $tournament->scoring_method_id === 8) {
                $cfg = $tournament->scoring_config['alliance'] ?? ['par3' => 4, 'par4' => 4, 'par5' => 4];

                $alliances = $tournament->rounds->map(function ($tr) use ($cfg) {
                    $round = $tr->round;
                    if (! $round) {
                        return null;
                    }

                    $parByHole = $round->roundHoles->pluck('par', 'hole_number');
                    $roundUsers = $tr->roundUsers;

                    // best-N points per hole summed = alliance total
                    $total = $roundUsers
                        ->flatMap(fn ($ru) => $ru->holeScores)
                        ->groupBy('hole_number')
                        ->reduce(function ($carry, $scores, $holeNumber) use ($parByHole, $cfg) {
                            $par = (int) ($parByHole[$holeNumber] ?? 4);
                            $n = $par === 3 ? $cfg['par3'] : ($par === 5 ? $cfg['par5'] : $cfg['par4']);
                            return $carry + $scores->pluck('points')->sortDesc()->take($n)->sum();
                        }, 0);

                    $holesPlayed = $roundUsers
                        ->flatMap(fn ($ru) => $ru->holeScores->pluck('hole_number'))
                        ->unique()
                        ->count();

                    $members = $roundUsers->map(fn ($ru) => [
                        'id' => $ru->user->id,
                        'name' => $ru->user->name,
                        'surname' => $ru->user->surname,
                        'handicap' => $ru->round_handicap,
                        'points' => (int) $ru->holeScores->sum('points'),
                        'strokes' => (int) $ru->holeScores->sum('strokes'),
                        'holes_played' => $ru->holeScores->pluck('hole_number')->unique()->count(),
                    ])->values()->all();

                    return [
                        'round_id' => $round->id,
                        'total' => (int) $total,
                        'holes_played' => $holesPlayed,
                        'players' => $members,
                    ];
                })->filter()->values()->all();

                $tournament->alliances = $alliances;
            }

            // Betterball (9) & Worst Ball (10) Stableford: each round_team is a team —
            // rank by the sum of the better (9) / worse (10) member score on each hole.
            if (in_array((int) $tournament->scoring_method_id, [9, 10], true)) {
                $useWorst = (int) $tournament->scoring_method_id === 10;
                $teams = $tournament->rounds->flatMap(function ($tr) use ($useWorst) {
                    $round = $tr->round;
                    if (! $round) {
                        return [];
                    }

                    return $round->roundTeams->map(function ($team) use ($useWorst) {
                        $members = $team->roundTeamUsers->map(fn ($rtu) => $rtu->roundUser)->filter();

                        $byHole = $members->flatMap(fn ($ru) => $ru->holeScores)->groupBy('hole_number');
                        $total = $byHole->reduce(fn ($carry, $scores) => $carry + (int) ($useWorst ? $scores->min('points') : $scores->max('points')), 0);

                        return [
                            'id' => $team->id,
                            'name' => $team->name,
                            'total' => (int) $total,
                            'holes_played' => $byHole->count(),
                            'players' => $members->map(fn ($ru) => [
                                'id' => $ru->user->id,
                                'name' => $ru->user->name,
                                'surname' => $ru->user->surname,
                                'handicap' => $ru->round_handicap,
                                'points' => (int) $ru->holeScores->sum('points'),
                                'strokes' => (int) $ru->holeScores->sum('strokes'),
                                'holes_played' => $ru->holeScores->pluck('hole_number')->unique()->count(),
                            ])->values()->all(),
                        ];
                    });
                })->values()->all();

                $tournament->betterball_teams = $teams;
            }

            $tournament->unsetRelation('rounds');
        });

        return response()->json([
            'success' => true,
            'data' => $tournaments,
        ]);
    }

    public function destroy($id)
    {
        $user = auth()->user();

        // Only the creator can delete a tournament
        $tournament = Tournaments::where('id', $id)
            ->where('creator_id', $user->id)
            ->first();

        if (! $tournament) {
            return response()->json([
                'success' => false,
                'message' => 'Tournament not found or you are not the host',
            ], 404);
        }

        // tournament_rounds rows cascade on delete; the underlying rounds are left intact
        $tournament->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tournament deleted',
        ]);
    }
}
