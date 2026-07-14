<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlayerController extends Controller
{
    /**
     * Search players by name, surname, or phone.
     */
    public function search(Request $request)
    {
        $query = $request->get('q');

        $players = User::select('id', 'name', 'surname', 'phone', 'handicap', 'email')
            ->when($query, function ($q) use ($query) {
                $words = array_filter(explode(' ', trim($query)));
                $q->where(function ($sub) use ($words) {
                    foreach ($words as $word) {
                        $term = '%' . strtolower($word) . '%';
                        $sub->where(function ($inner) use ($term) {
                            $inner->whereRaw('LOWER(name) LIKE ?', [$term])
                                  ->orWhereRaw('LOWER(surname) LIKE ?', [$term])
                                  ->orWhereRaw('LOWER(phone) LIKE ?', [$term])
                                  ->orWhereRaw("LOWER(CONCAT(name, ' ', surname)) LIKE ?", [$term]);
                        });
                    }
                });
            })
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $players,
        ]);
    }

    /**
     * Get all players (both registered users and invited players)
     * Excludes the currently authenticated user
     */
    public function index(Request $request)
    {
        $currentUserId = Auth::id();
        $busyIds = \App\Models\RoundUser::activeUserIds()->flip();

        $players = User::select('id', 'name', 'surname', 'phone', 'handicap', 'invite_code', 'created_at')
            ->orderBy('name')
            ->get()
            ->map(function ($player) use ($busyIds) {
                return [
                    'id' => $player->id,
                    'name' => $player->name,
                    'surname' => $player->surname,
                    'phone' => $player->phone,
                    'handicap' => $player->handicap,
                    'invite_code' => $player->invite_code,
                    'is_registered' => $player->isRegistered(),
                    'in_active_round' => $busyIds->has($player->id),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $players
        ]);
    }

    /**
     * Create a new player (invited user)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'phone' => 'nullable|string|unique:users,phone',
            'handicap' => 'nullable|integer|min:0|max:54',
        ]);

        // Generate unique 6-digit invite code
        $inviteCode = $this->generateUniqueInviteCode();

        $player = User::create([
            'name' => $validated['name'],
            'surname' => $validated['surname'],
            'phone' => $validated['phone'] ?? null,
            'handicap' => $validated['handicap'] ?? 0,
            'password' => null, // No password yet - invited player
            'invite_code' => $inviteCode,
        ]);

        // Create friendship between the creator and the invited player
        Friend::createFriendship(Auth::id(), $player->id);

        return response()->json([
            'success' => true,
            'message' => 'Player created successfully',
            'data' => [
                'id' => $player->id,
                'name' => $player->name,
                'surname' => $player->surname,
                'phone' => $player->phone,
                'handicap' => $player->handicap,
                'invite_code' => $player->invite_code,
                'is_registered' => false,
            ]
        ], 201);
    }

    /**
     * Update a player
     */
    public function update(Request $request, $id)
    {
        $player = User::find($id);

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        // Don't allow updating registered users through player endpoint
        if ($player->isRegistered() && $player->id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update registered users'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'surname' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|unique:users,phone,' . $id,
            'handicap' => 'nullable|integer|min:0|max:54',
        ]);

        $player->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Player updated successfully',
            'data' => [
                'id' => $player->id,
                'name' => $player->name,
                'surname' => $player->surname,
                'phone' => $player->phone,
                'handicap' => $player->handicap,
                'invite_code' => $player->invite_code,
                'is_registered' => $player->isRegistered(),
            ]
        ]);
    }

    /**
     * Delete a player
     */
    public function destroy(Request $request, $id)
    {
        $player = User::find($id);

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        // Don't allow deleting registered users (only invited players)
        if ($player->isRegistered()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete registered users'
            ], 403);
        }

        $player->delete();

        return response()->json([
            'success' => true,
            'message' => 'Player deleted successfully'
        ]);
    }

    /**
     * Generate a unique 6-digit invite code
     */
    private function generateUniqueInviteCode(): string
    {
        do {
            $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (User::where('invite_code', $code)->exists());

        return $code;
    }
}
