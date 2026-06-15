<?php

namespace App\Http\Controllers;

use App\Models\Courses;
use App\Models\CourseTees;
use App\Models\CourseHoles;
use App\Models\Tees;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * Get courses with their tees and holes.
     *
     * Query params:
     *   - search: filter by name/location (case-insensitive), capped at `limit` (default 25)
     *   - lat & lng: return the nearest courses by distance, capped at `limit` (default 10)
     *   - limit: override the cap; with neither search nor coords, omit to return all
     * (No params returns all courses — keeps the round/tournament pickers working.)
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $lat    = $request->query('lat');
        $lng    = $request->query('lng');
        $limit  = (int) $request->query('limit', 0);

        $query = Courses::with(['courseTees.tee', 'courseTees.holes']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('location', 'ILIKE', "%{$search}%");
            })->orderBy('name');
            $limit = $limit ?: 25;
        } elseif (is_numeric($lat) && is_numeric($lng)) {
            // Nearest first, via the haversine great-circle distance (km)
            $query->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->select('courses.*')
                ->selectRaw(
                    '(6371 * acos(LEAST(1, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))) AS distance_km',
                    [(float) $lat, (float) $lng, (float) $lat]
                )
                ->orderBy('distance_km');
            $limit = $limit ?: 10;
        } else {
            $query->orderBy('name');
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $courses = $query->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'location' => $course->location,
                    'latitude' => $course->latitude,
                    'longitude' => $course->longitude,
                    'phone' => $course->phone,
                    'distance_km' => isset($course->distance_km) ? round((float) $course->distance_km, 1) : null,
                    'num_holes' => $course->num_holes,
                    'tees' => $course->courseTees->map(function ($courseTee) {
                        return [
                            'id' => $courseTee->id,
                            'tee_id' => $courseTee->tee_id,
                            'tee_name' => $courseTee->tee->name,
                            'tee_description' => $courseTee->tee->description,
                            'gender' => $courseTee->tee->gender,
                            'colour_code' => $courseTee->tee->colour_code,
                            'course_rating' => $courseTee->course_rating,
                            'slope_rating' => $courseTee->slope_rating,
                            'total_yards' => $courseTee->total_yards,
                            'total_meters' => $courseTee->total_meters,
                            'holes' => $courseTee->holes->map(function ($hole) {
                                return [
                                    'id' => $hole->id,
                                    'hole_number' => $hole->hole_number,
                                    'par' => $hole->par,
                                    'stroke_index' => $hole->stroke_index,
                                    'yards' => $hole->yards,
                                    'meters' => $hole->meters,
                                ];
                            }),
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $courses
        ]);
    }

    /**
     * Get all predefined tees
     */
    public function getTees(Request $request)
    {
        $tees = Tees::all()->map(function ($tee) {
            return [
                'id' => $tee->id,
                'name' => $tee->name,
                'description' => $tee->description,
                'gender' => $tee->gender,
                'colour_code' => $tee->colour_code,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $tees
        ]);
    }

    /**
     * Create a new tee type
     */
    public function createTee(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'gender' => 'nullable|in:M,F',
            'colour_code' => 'nullable|string|max:7',
        ]);

        // Case-insensitive lookup to avoid duplicates, scoped to gender so a
        // Men's "Red" and a Ladies' "Red" can coexist
        $tee = Tees::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->where('gender', $validated['gender'] ?? null)
            ->first();

        if (!$tee) {
            $tee = Tees::create([
                'name' => ucfirst(strtolower($validated['name'])),
                'description' => $validated['description'] ?? ucfirst(strtolower($validated['name'])),
                'gender' => $validated['gender'] ?? null,
                'colour_code' => $validated['colour_code'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tee->id,
                'name' => $tee->name,
                'description' => $tee->description,
                'gender' => $tee->gender,
                'colour_code' => $tee->colour_code,
            ]
        ]);
    }

    /**
     * Create a new course with tee data
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'num_holes' => 'required|integer|in:9,18',
            'tees' => 'required|array|min:1',
            'tees.*.tee_id' => 'required|exists:tees,id',
            'tees.*.course_rating' => 'nullable|numeric',
            'tees.*.slope_rating' => 'nullable|integer',
            'tees.*.holes' => 'required|array',
            'tees.*.holes.*.hole_number' => 'required|integer|min:1|max:18',
            'tees.*.holes.*.par' => 'required|integer|in:3,4,5',
            'tees.*.holes.*.stroke_index' => 'required|integer|min:1|max:18',
        ]);

        try {
            DB::beginTransaction();

            // Create the course
            $course = Courses::create([
                'name' => $validated['name'],
                'location' => $validated['location'] ?? null,
                'num_holes' => $validated['num_holes'],
                'created_by' => Auth::id(),
            ]);

            // Create course tees and holes
            foreach ($validated['tees'] as $teeData) {
                $courseTee = CourseTees::create([
                    'course_id' => $course->id,
                    'tee_id' => $teeData['tee_id'],
                    'course_rating' => $teeData['course_rating'] ?? null,
                    'slope_rating' => $teeData['slope_rating'] ?? null,
                ]);

                // Create holes for this tee
                foreach ($teeData['holes'] as $holeData) {
                    CourseHoles::create([
                        'course_tee_id' => $courseTee->id,
                        'hole_number' => $holeData['hole_number'],
                        'par' => $holeData['par'],
                        'stroke_index' => $holeData['stroke_index'],
                    ]);
                }

                // Update total yards/meters
                $courseTee->update([
                    'total_yards' => $courseTee->holes->sum('yards'),
                    'total_meters' => $courseTee->holes->sum('meters'),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course created successfully',
                'data' => [
                    'id' => $course->id,
                    'name' => $course->name,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create course: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add or update tee data for an existing course
     */
    public function addTee(Request $request, $courseId)
    {
        $course = Courses::where('id', $courseId)
            ->where('created_by', Auth::id())
            ->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        $validated = $request->validate([
            'tee_id' => 'required|exists:tees,id',
            'course_rating' => 'nullable|numeric',
            'slope_rating' => 'nullable|integer',
            'holes' => 'required|array',
            'holes.*.hole_number' => 'required|integer|min:1|max:18',
            'holes.*.par' => 'required|integer|in:3,4,5',
            'holes.*.stroke_index' => 'required|integer|min:1|max:18',
        ]);

        // Check if this tee already exists for this course
        $existingTee = CourseTees::where('course_id', $courseId)
            ->where('tee_id', $validated['tee_id'])
            ->first();

        if ($existingTee) {
            return response()->json([
                'success' => false,
                'message' => 'This tee already exists for this course. Use update instead.'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $courseTee = CourseTees::create([
                'course_id' => $courseId,
                'tee_id' => $validated['tee_id'],
                'course_rating' => $validated['course_rating'] ?? null,
                'slope_rating' => $validated['slope_rating'] ?? null,
            ]);

            foreach ($validated['holes'] as $holeData) {
                CourseHoles::create([
                    'course_tee_id' => $courseTee->id,
                    'hole_number' => $holeData['hole_number'],
                    'par' => $holeData['par'],
                    'stroke_index' => $holeData['stroke_index'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tee added successfully',
                'data' => [
                    'id' => $courseTee->id,
                    'tee_name' => $courseTee->tee->name,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add tee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update course and/or tee data
     */
    public function update(Request $request, $id)
    {
        $course = Courses::where('id', $id)
            ->where('created_by', Auth::id())
            ->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location' => 'nullable|string|max:255',
            'tee_data' => 'sometimes|array',
            'tee_data.course_tee_id' => 'required_with:tee_data|exists:course_tees,id',
            'tee_data.course_rating' => 'nullable|numeric',
            'tee_data.slope_rating' => 'nullable|integer',
            'tee_data.holes' => 'sometimes|array',
            'tee_data.holes.*.hole_number' => 'required_with:tee_data.holes|integer|min:1|max:18',
            'tee_data.holes.*.par' => 'required_with:tee_data.holes|integer|in:3,4,5',
            'tee_data.holes.*.stroke_index' => 'required_with:tee_data.holes|integer|min:1|max:18',
        ]);

        try {
            DB::beginTransaction();

            // Update course info
            if (isset($validated['name']) || isset($validated['location'])) {
                $course->update([
                    'name' => $validated['name'] ?? $course->name,
                    'location' => $validated['location'] ?? $course->location,
                ]);
            }

            // Update tee data if provided
            if (isset($validated['tee_data'])) {
                $courseTee = CourseTees::where('id', $validated['tee_data']['course_tee_id'])
                    ->where('course_id', $id)
                    ->first();

                if ($courseTee) {
                    $courseTee->update([
                        'course_rating' => $validated['tee_data']['course_rating'] ?? $courseTee->course_rating,
                        'slope_rating' => $validated['tee_data']['slope_rating'] ?? $courseTee->slope_rating,
                    ]);

                    // Update holes if provided
                    if (isset($validated['tee_data']['holes'])) {
                        foreach ($validated['tee_data']['holes'] as $holeData) {
                            CourseHoles::updateOrCreate(
                                [
                                    'course_tee_id' => $courseTee->id,
                                    'hole_number' => $holeData['hole_number'],
                                ],
                                [
                                    'par' => $holeData['par'],
                                    'stroke_index' => $holeData['stroke_index'],
                                ]
                            );
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update course: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a course
     */
    public function destroy(Request $request, $id)
    {
        $course = Courses::where('id', $id)
            ->where('created_by', Auth::id())
            ->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        }

        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully'
        ]);
    }
}
