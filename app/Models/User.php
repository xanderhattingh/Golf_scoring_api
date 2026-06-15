<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'surname',
        'email',
        'phone',
        'password',
        'invite_code',
        'handicap',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user has completed registration (has password)
     */
    public function isRegistered(): bool
    {
        return !is_null($this->password);
    }

    /**
     * Scope to get only registered users
     */
    public function scopeRegistered($query)
    {
        return $query->whereNotNull('password');
    }

    /**
     * Scope to get only invited players (no password yet)
     */
    public function scopeInvited($query)
    {
        return $query->whereNull('password');
    }

    /**
     * Get all friends of this user.
     */
    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friends', 'user_id', 'friend_id')
            ->withTimestamps();
    }

    public function rounds(): hasMany
    {
        return $this->hasMany(RoundUser::class, 'user_id', 'id');
    }
}
