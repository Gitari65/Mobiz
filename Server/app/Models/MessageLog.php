<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLog extends Model
{
    protected $fillable = [
        'company_id',
        'message_template_id',
        'sent_by_user_id',
        'type',
        'recipient_name',
        'recipient_contact',
        'recipient_type',
        'subject',
        'body',
        'status',
        'external_id',
        'error_message',
        'campaign_name',
        'campaign_type',
        'metadata',
        'retry_count',
        'sent_at',
        'delivered_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function messageTemplate(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    /**
     * Mark message as sent
     */
    public function markAsSent(?string $externalId = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'external_id' => $externalId,
        ]);
    }

    /**
     * Mark message as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark message as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->increment('retry_count');
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }

    /**
     * Get stats for a company
     */
    public static function getStats(int $companyId, string $type = null, int $days = 7)
    {
        $query = self::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays($days));

        if ($type) {
            $query->where('type', $type);
        }

        return [
            'total_sent' => $query->clone()->where('status', 'sent')->count(),
            'total_failed' => $query->clone()->where('status', 'failed')->count(),
            'total_pending' => $query->clone()->where('status', 'pending')->count(),
            'by_type' => $query->clone()->selectRaw('type, COUNT(*) as count')->groupBy('type')->get(),
            'by_campaign' => $query->clone()->selectRaw('campaign_type, COUNT(*) as count')->groupBy('campaign_type')->get(),
        ];
    }
}
