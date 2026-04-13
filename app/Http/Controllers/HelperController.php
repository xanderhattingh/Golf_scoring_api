<?php

namespace App\Http\Controllers;

use App\Models\Courses;
use App\Models\Players;
use App\Models\ScoringMethods;
use Illuminate\Http\Request;

class HelperController extends Controller
{
    public function getCreateRoundData(Request $req)
    {
        $allCourses = Courses::with('holes')->get();
        $allPlayers = Players::all();
        $allScoringMethods = ScoringMethods::all();

        return response()->json([
            'courses' => $allCourses,
            'players' => $allPlayers,
            'scoring_methods' => $allScoringMethods,
        ]);
    }
}
