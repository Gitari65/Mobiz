<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'message',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    // ── Helper to create a notification for a user ──────────────────────────
    public static function notify(int $userId, string $message, string $type = 'info', array $data = []): self
    {
        return self::create([
            'user_id' => $userId,
            'type'    => $type,
            'message' => $message,
            'data'    => $data ?: null,
        ]);
    }

    // ── Notify all users with a given role name ──────────────────────────────
    public static function notifyRole(string $roleName, string $message, string $type = 'info', array $data = []): void
    {
        $role = Role::where('name', $roleName)->first();
        if (! $role) {
            return;
        }

        User::where('role_id', $role->id)->each(function (User $user) use ($message, $type, $data) {
            self::notify($user->id, $message, $type, $data);
        });
    }
}
