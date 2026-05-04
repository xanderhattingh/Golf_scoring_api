<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friend extends Model
{
    /** @use HasFactory<\Database\Factories\FriendFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'friend_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function friend(): BelongsTo
    {
        return $this->belongsTo(User::class, 'friend_id');
    }

    /**
     * Create a bidirectional friendship between two users.
     * Silently skips if the friendship already exists.
     */
    public static function createFriendship(int $userId, int $friendId): void
    {
        if ($userId === $friendId) {
            return;
        }

        // Create user -> friend
        self::firstOrCreate([
            'user_id' => $userId,
            'friend_id' => $friendId,
        ]);

        // Create friend -> user (mutual)
        self::firstOrCreate([
            'user_id' => $friendId,
            'friend_id' => $userId,
        ]);
    }
}
