<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionUpgradeRequest extends Model
{
    protected $fillable = [
        'company_id', 'subscription_id', 'current_plan_id', 'requested_plan_id',
        'requested_by', 'reviewed_by', 'status', 'admin_notes', 'reviewer_notes', 'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function currentPlan()
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    public function requestedPlan()
    {
        return $this->belongsTo(Plan::class, 'requested_plan_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
