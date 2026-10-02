<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancels_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'starts_at'     => 'datetime',
        'ends_at'       => 'datetime',
        'cancels_at'    => 'datetime',
    ];

    /**
     * Tenant who owns this subscription.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Plan for this subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Check if subscription is currently valid and active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing']) &&
            ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * Check if subscription is currently on trial.
     */
    public function isTrialing(): bool
    {
        return $this->status === 'trialing' &&
            $this->trial_ends_at !== null &&
            $this->trial_ends_at->isFuture();
    }

    /**
     * Check if subscription is past due.
     */
    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    /**
     * Check if subscription is canceled.
     */
    public function isCanceled(): bool
    {
        return $this->status === 'canceled' ||
            ($this->cancels_at !== null && $this->cancels_at->isPast());
    }
}
