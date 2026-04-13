<?php

namespace App\Http\Controllers;

use App\Models\ScoringMethod;
use Illuminate\Http\Request;

class ScoringMethodController extends Controller
{
    public function index(Request $request)
    {
        $methods = ScoringMethod::orderBy('id')->get()->map(function ($method) {
            return [
                'id' => $method->id,
                'name' => $method->name,
                'description' => $method->description,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }
}
