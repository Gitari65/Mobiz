<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'company_id',
        'action',
        'auditable_type',
        'auditable_id',
        'user_id',
        'user_name',
        'ip_address',
        'old_values',
        'new_values',
        'notes',
        'review_status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public const REVIEW_OPEN = 'open';
    public const REVIEW_IN_PROGRESS = 'in_progress';
    public const REVIEW_RESOLVED = 'resolved';

    public static function allowedReviewStatuses(): array
    {
        return [
            self::REVIEW_OPEN,
            self::REVIEW_IN_PROGRESS,
            self::REVIEW_RESOLVED,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Backward-compatible alias for existing superuser pages/controllers.
    public function business()
    {
        return $this->company();
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
