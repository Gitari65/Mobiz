<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledMessage extends Model
{
    protected $fillable = [
        'company_id',
        'message_template_id',
        'created_by_user_id',
        'name',
        'frequency',
        'schedule_config',
        'recipient_filters',
        'estimated_recipients',
        'is_active',
        'scheduled_for',
        'last_sent_at',
        'next_send_at',
        'total_sent',
        'successful_sends',
        'failed_sends',
        'notes',
    ];

    protected $casts = [
        'schedule_config' => 'array',
        'recipient_filters' => 'array',
        'is_active' => 'boolean',
        'scheduled_for' => 'datetime',
        'last_sent_at' => 'datetime',
        'next_send_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function messageTemplate(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Mark this scheduled message as sent
     */
    public function markAsSent(int $successCount, int $failedCount): void
    {
        $this->update([
            'last_sent_at' => now(),
            'total_sent' => $this->total_sent + $successCount + $failedCount,
            'successful_sends' => $this->successful_sends + $successCount,
            'failed_sends' => $this->failed_sends + $failedCount,
        ]);

        // Calculate next send date
        $this->calculateNextSend();
    }

    /**
     * Calculate next send date based on frequency
     */
    public function calculateNextSend(): void
    {
        $config = $this->schedule_config;

        $nextSend = match ($this->frequency) {
            'daily' => now()->addDay()->setTime($config['time'] ?? '10:00', 0),
            'weekly' => now()->addWeek()
                ->startOfWeek()
                ->addDays(collect(['monday' => 0, 'tuesday' => 1, 'wednesday' => 2, 'thursday' => 3, 'friday' => 4, 'saturday' => 5, 'sunday' => 6])
                    ->get(strtolower($config['day'] ?? 'monday'), 0))
                ->setTime($config['time'] ?? '10:00', 0),
            'monthly' => now()->addMonth()->setTime($config['time'] ?? '10:00', 0),
            default => null,
        };

        if ($nextSend) {
            $this->update(['next_send_at' => $nextSend]);
        }
    }

    /**
     * Get success rate
     */
    public function getSuccessRate(): float
    {
        $total = $this->total_sent;
        return $total > 0 ? ($this->successful_sends / $total) * 100 : 0;
    }
}
