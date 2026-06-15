<?php

namespace App\Http\Controllers;

use App\Models\AnimalEvent;
use App\Models\CourseTees;
use App\Models\Friend;
use App\Models\HoleScore;
use App\Models\Round;
use App\Models\RoundHole;
use App\Models\RoundTeam;
use App\Models\RoundTeamUser;
use App\Models\RoundUser;
use App\Models\Tournament_Rounds;
use App\Models\Tournaments;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoundsController extends Controller
{
    public function index(Request $request)
    {
        $rounds = Round::with(['course', 'scoringMethod', 'roundUsers.user', 'holeScores'])
            ->where(function ($query) {
                $query->where('created_by', Auth::id())
                    ->orWhereHas('roundUsers', function ($q) {
                        $q->where('user_id', Auth::id());
                    });
            })
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($round) {
                return $this->formatRoundForList($round);
            });

        return response()->json([
            'success' => true,
            'data' => $rounds,
        ]);
    }

    public function show(Request $request, $id)
    {
        $round = Round::with([
            'course',
            'scoringMethod',
            'roundUsers.user',
            'roundTeams.roundTeamUsers.roundUser',
            'roundHoles',
            'holeScores',
            'animalEvents',
        ])
            ->where(function ($query) {
                $query->where('created_by', Auth::id())
                    ->orWhereHas('roundUsers', function ($q) {
                        $q->where('user_id', Auth::id());
                    });
            })
            ->find($id);

        if (!$round) {
            return response()->json([
                'success' => false,
                'message' => 'Round not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatRoundForDetail($round),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'course_tee_id' => 'required|exists:course_tees,id',
            'player_ids' => 'required|array|min:1|max:4',
            'player_ids.*' => 'exists:users,id',
            'player_handicaps' => 'nullable|array',
            'player_handicaps.*' => 'integer|min:0|max:54',
            'scoring_method_id' => 'required|exists:scoring_methods,id',
            'date' => 'required|date',
            'format' => 'required|in:individual,teams',
            'teams' => 'nullable|array',
            'teams.*.name' => 'required|string',
            'teams.*.playerIds' => 'required|array',
            'teams.*.playerIds.*' => 'exists:users,id',
            'starting_hole' => 'nullable|integer|in:1,10',
            'tournament_id' => 'nullable|exists:tournaments,id',
        ]);

        $courseTee = CourseTees::where('id', $validated['course_tee_id'])
            ->where('course_id', $validated['course_id'])
            ->first();

        if (!$courseTee) {
            return response()->json([
                'success' => false,
                'message' => 'Selected tee does not belong to this course',
            ], 422);
        }

        if ($validated['format'] === 'teams' && empty($validated['teams'])) {
            return response()->json([
                'success' => false,
                'message' => 'Teams are required for team format',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $round = Round::create([
                'course_id' => $validated['course_id'],
                'scoring_method_id' => $validated['scoring_method_id'],
                'created_by' => Auth::id(),
                'date' => $validated['date'],
                'format' => $validated['format'],
                'completed' => false,
                'current_hole' => $validated['starting_hole'] ?? 1,
                'starting_hole' => $validated['starting_hole'] ?? 1,
            ]);

            $roundUsers = [];
            foreach ($validated['player_ids'] as $playerId) {
                $user = User::find($playerId);
                $handicap = $validated['player_handicaps'][$playerId] ?? ($user->handicap ?? 0);

                $roundUser = RoundUser::create([
                    'round_id' => $round->id,
                    'user_id' => $playerId,
                    'round_handicap' => $handicap,
                ]);
                $roundUsers[$playerId] = $roundUser;
            }

            if ($validated['format'] === 'teams' && !empty($validated['teams'])) {
                foreach ($validated['teams'] as $teamData) {
                    $team = RoundTeam::create([
                        'round_id' => $round->id,
                        'name' => $teamData['name'],
                    ]);

                    foreach ($teamData['playerIds'] as $playerId) {
                        if (isset($roundUsers[$playerId])) {
                            RoundTeamUser::create([
                                'round_team_id' => $team->id,
                                'round_user_id' => $roundUsers[$playerId]->id,
                            ]);
                        }
                    }
                }
            }

            foreach ($courseTee->holes as $hole) {
                RoundHole::create([
                    'round_id' => $round->id,
                    'hole_number' => $hole->hole_number,
                    'par' => $hole->par,
                    'stroke_index' => $hole->stroke_index,
                ]);
            }

            // Create friendships between round creator and all players
            $creatorId = Auth::id();
            foreach ($validated['player_ids'] as $playerId) {
                Friend::createFriendship($creatorId, $playerId);
            }

            // If this round was created by joining a tournament, link it
            if (! empty($validated['tournament_id'])) {
                Tournament_Rounds::create([
                    'tournament_id' => $validated['tournament_id'],
                    'round_id' => $round->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $this->formatRoundForDetail($round->fresh([
                    'course', 'scoringMethod', 'roundUsers.user', 'roundTeams.roundTeamUsers.roundUser',
                    'roundHoles', 'holeScores', 'animalEvents',
                ])),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create round: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $round = Round::where(function ($query) {
            $query->where('created_by', Auth::id())
                ->orWhereHas('roundUsers', function ($q) {
                    $q->where('user_id', Auth::id());
                });
        })->find($id);

        if (!$round) {
            return response()->json([
                'success' => false,
                'message' => 'Round not found',
            ], 404);
        }

        $validated = $request->validate([
            'current_hole' => 'sometimes|integer|min:1|max:18',
            'completed' => 'sometimes|boolean',
            'scores' => 'sometimes|array',
            'scores.*.holeNumber' => 'required_with:scores|integer|min:1|max:18',
            'scores.*.playerScores' => 'required_with:scores|array',
            'scores.*.playerScores.*.playerId' => 'required|exists:users,id',
            'scores.*.playerScores.*.strokes' => 'required|integer|min:1',
            'scores.*.playerScores.*.points' => 'required|integer|min:0',
            'scores.*.pinkPlayerId' => 'nullable|exists:users,id',
            'scores.*.animalEvents' => 'sometimes|array',
            'scores.*.animalEvents.*.playerId' => 'required|exists:users,id',
            'scores.*.animalEvents.*.animalType' => 'required|in:tree,water,bunker,three_putt',
            'course_holes' => 'sometimes|array',
            'course_holes.*.hole_number' => 'required|integer|min:1|max:18',
            'course_holes.*.hole_par' => 'required|integer|in:3,4,5',
            'course_holes.*.hole_stroke' => 'required|integer|min:1|max:18',
        ]);

        DB::beginTransaction();

        try {
            $updateData = [];
            if (isset($validated['current_hole'])) {
                $updateData['current_hole'] = $validated['current_hole'];
            }
            if (isset($validated['completed'])) {
                $updateData['completed'] = $validated['completed'];
            }
            if (!empty($updateData)) {
                $round->update($updateData);
            }

            $roundUserMap = $round->roundUsers->keyBy('user_id')->map->id->toArray();

            $teamMap = [];
            foreach ($round->roundTeams as $team) {
                foreach ($team->roundTeamUsers as $rtu) {
                    $teamMap[$rtu->round_user_id] = $team->id;
                }
            }

            if (isset($validated['course_holes'])) {
                foreach ($validated['course_holes'] as $holeData) {
                    RoundHole::updateOrCreate(
                        [
                            'round_id' => $round->id,
                            'hole_number' => $holeData['hole_number'],
                        ],
                        [
                            'par' => $holeData['hole_par'],
                            'stroke_index' => $holeData['hole_stroke'],
                        ]
                    );
                }
            }

            if (isset($validated['scores'])) {
                foreach ($validated['scores'] as $scoreData) {
                    $holeNumber = $scoreData['holeNumber'];

                    HoleScore::where('round_id', $round->id)
                        ->where('hole_number', $holeNumber)
                        ->delete();

                    AnimalEvent::where('round_id', $round->id)
                        ->where('hole_number', $holeNumber)
                        ->delete();

                    foreach ($scoreData['playerScores'] as $playerScore) {
                        $roundUserId = $roundUserMap[$playerScore['playerId']] ?? null;
                        if (!$roundUserId) {
                            continue;
                        }

                        HoleScore::create([
                            'round_id' => $round->id,
                            'round_team_id' => $teamMap[$roundUserId] ?? null,
                            'round_user_id' => $roundUserId,
                            'hole_number' => $holeNumber,
                            'strokes' => $playerScore['strokes'],
                            'points' => $playerScore['points'],
                            'has_pink_ball' => ($scoreData['pinkPlayerId'] ?? null) === $playerScore['playerId'],
                        ]);
                    }

                    if (!empty($scoreData['animalEvents'])) {
                        foreach ($scoreData['animalEvents'] as $event) {
                            $roundUserId = $roundUserMap[$event['playerId']] ?? null;
                            if (!$roundUserId) {
                                continue;
                            }

                            AnimalEvent::create([
                                'round_id' => $round->id,
                                'round_user_id' => $roundUserId,
                                'hole_number' => $holeNumber,
                                'animal_type' => $event['animalType'],
                                'event_at' => $event['timestamp'] ?? now(),
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $this->formatRoundForDetail($round->fresh([
                    'course', 'scoringMethod', 'roundUsers.user', 'roundTeams.roundTeamUsers.roundUser',
                    'roundHoles', 'holeScores', 'animalEvents',
                ])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update round: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $round = Round::where('created_by', Auth::id())->find($id);

        if (!$round) {
            return response()->json([
                'success' => false,
                'message' => 'Round not found',
            ], 404);
        }

        $round->delete();

        return response()->json([
            'success' => true,
            'message' => 'Round deleted successfully',
        ]);
    }

    private function formatRoundForList($round): array
    {
        return [
            'id' => $round->id,
            'course' => [
                'id' => $round->course->id,
                'name' => $round->course->name,
            ],
            'players' => $round->roundUsers->map(function ($ru) {
                return [
                    'id' => $ru->user->id,
                    'name' => trim($ru->user->name . ' ' . ($ru->user->surname ?? '')),
                    'handicap' => $ru->round_handicap,
                ];
            })->values()->all(),
            'scoring_method' => [
                'id' => $round->scoringMethod->id,
                'name' => $round->scoringMethod->name,
            ],
            'date' => $round->date->format('Y-m-d'),
            'completed' => $round->completed,
            'created_at' => $round->created_at->toIso8601String(),
            'format' => $round->format,
            'scores_count' => $round->holeScores->groupBy('hole_number')->count(),
        ];
    }

    private function formatRoundForDetail($round): array
    {
        $roundUserMap = $round->roundUsers->keyBy('id')->map->user_id->toArray();

        $teams = $round->roundTeams->map(function ($team) use ($roundUserMap) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'playerIds' => $team->roundTeamUsers->map(function ($rtu) use ($roundUserMap) {
                    return $roundUserMap[$rtu->round_user_id] ?? null;
                })->filter()->values()->all(),
            ];
        })->values()->all();

        $scores = [];
        $holeScoresGrouped = $round->holeScores->groupBy('hole_number');

        foreach ($holeScoresGrouped as $holeNumber => $holeScoreRows) {
            $playerScores = $holeScoreRows->map(function ($score) use ($roundUserMap) {
                return [
                    'team_id' => $score->round_team_id,
                    'playerId' => $roundUserMap[$score->round_user_id] ?? null,
                    'strokes' => $score->strokes,
                    'points' => $score->points,
                ];
            })->filter(fn($ps) => $ps['playerId'] !== null)->values()->all();

            $pinkScore = $holeScoreRows->firstWhere('has_pink_ball', true);
            $pinkPlayerId = $pinkScore ? ($roundUserMap[$pinkScore->round_user_id] ?? null) : null;

            $animalEvents = $round->animalEvents
                ->where('hole_number', (int)$holeNumber)
                ->map(function ($event) use ($roundUserMap) {
                    return [
                        'playerId' => $roundUserMap[$event->round_user_id] ?? null,
                        'animalType' => $event->animal_type,
                        'holeNumber' => $event->hole_number,
                        'timestamp' => ($event->event_at ?? $event->created_at)->toIso8601String(),
                    ];
                })->filter(fn($e) => $e['playerId'] !== null)->values()->all();

            $scores[] = [
                'holeNumber' => (int)$holeNumber,
                'playerScores' => $playerScores,
                'pinkPlayerId' => $pinkPlayerId,
                'animalEvents' => $animalEvents,
            ];
        }

        usort($scores, fn($a, $b) => $a['holeNumber'] <=> $b['holeNumber']);

        $animalHolders = null;
        if (in_array($round->scoring_method_id, [5, 6])) {
            $animalHolders = [
                'front9' => ['tree' => null, 'water' => null, 'bunker' => null, 'three_putt' => null],
                'back9' => ['tree' => null, 'water' => null, 'bunker' => null, 'three_putt' => null],
            ];

            foreach (['front9' => [1, 9], 'back9' => [10, 18]] as $nine => $range) {
                foreach (['tree', 'water', 'bunker', 'three_putt'] as $type) {
                    $latestEvent = $round->animalEvents
                        ->where('animal_type', $type)
                        ->filter(function ($e) use ($range) {
                            return $e->hole_number >= $range[0] && $e->hole_number <= $range[1];
                        })
                        ->sortByDesc('hole_number')
                        ->first();

                    if ($latestEvent) {
                        $animalHolders[$nine][$type] = [
                            'playerId' => $roundUserMap[$latestEvent->round_user_id] ?? null,
                            'holeNumber' => $latestEvent->hole_number,
                        ];
                    }
                }
            }
        }

        return [
            'id' => $round->id,
            'course' => [
                'id' => $round->course->id,
                'name' => $round->course->name,
                'holes' => $round->roundHoles->sortBy('hole_number')->values()->map(function ($hole) {
                    return [
                        'hole_number' => $hole->hole_number,
                        'hole_par' => $hole->par,
                        'hole_stroke' => $hole->stroke_index,
                    ];
                })->all(),
                'created_at' => $round->course->created_at->toIso8601String(),
            ],
            'players' => $round->roundUsers->map(function ($ru) {
                return [
                    'id' => $ru->user->id,
                    'name' => trim($ru->user->name . ' ' . ($ru->user->surname ?? '')),
                    'handicap' => $ru->round_handicap,
                    'created_at' => $ru->user->created_at->toIso8601String(),
                ];
            })->values()->all(),
            'scoring_method' => [
                'id' => $round->scoringMethod->id,
                'name' => $round->scoringMethod->name,
                'description' => $round->scoringMethod->description,
            ],
            'date' => $round->date->format('Y-m-d'),
            'completed' => $round->completed,
            'created_at' => $round->created_at->toIso8601String(),
            'format' => $round->format,
            'teams' => $teams,
            'scores' => $scores,
            'currentHole' => $round->current_hole,
            'animalHolders' => $animalHolders,
        ];
    }
    

}
