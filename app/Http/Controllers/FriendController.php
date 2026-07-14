<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FriendController extends Controller
{
    /**
     * Get all friends of the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $busyIds = \App\Models\RoundUser::activeUserIds()->flip();

        $friends = $user->friends()
            ->select('users.id', 'users.name', 'users.surname', 'users.phone', 'users.handicap', 'users.invite_code', 'users.password')
            ->orderBy('users.name')
            ->get()
            ->map(function ($friend) use ($busyIds) {
                return [
                    'id' => $friend->id,
                    'name' => $friend->name,
                    'surname' => $friend->surname,
                    'phone' => $friend->phone,
                    'handicap' => $friend->handicap,
                    'invite_code' => $friend->invite_code,
                    'is_registered' => ! is_null($friend->password),
                    'in_active_round' => $busyIds->has($friend->id),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $friends,
        ]);
    }

    /**
     * Create a friendship with an existing user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'friend_id' => 'required|exists:users,id',
        ]);

        $friendId = $validated['friend_id'];
        $userId = Auth::id();

        if ($friendId == $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot be friends with yourself',
            ], 422);
        }

        Friend::createFriendship($userId, $friendId);

        $friend = User::find($friendId);

        return response()->json([
            'success' => true,
            'message' => 'Friend added successfully',
            'data' => [
                'id' => $friend->id,
                'name' => $friend->name,
                'surname' => $friend->surname,
                'phone' => $friend->phone,
                'handicap' => $friend->handicap,
                'invite_code' => $friend->invite_code,
                'is_registered' => ! is_null($friend->password),
            ],
        ], 201);
    }

    /**
     * Remove a friendship.
     */
    public function destroy(Request $request, $friendId)
    {
        $userId = Auth::id();

        // Delete both directions of the friendship
        Friend::where(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $userId)
                  ->where('friend_id', $friendId);
        })->orWhere(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $friendId)
                  ->where('friend_id', $userId);
        })->delete();

        return response()->json([
            'success' => true,
            'message' => 'Friend removed successfully',
        ]);
    }
}
