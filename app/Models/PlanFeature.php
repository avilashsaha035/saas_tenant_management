<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'max_users',
        'max_customers',
        'has_advanced_analytics',
        'has_api_access',
        'rate_limit_per_minute',
        'custom_features',
    ];

    protected $casts = [
        'max_users'              => 'integer',
        'max_customers'          => 'integer',
        'has_advanced_analytics' => 'boolean',
        'has_api_access'         => 'boolean',
        'rate_limit_per_minute'  => 'integer',
        'custom_features'        => 'array',
    ];

    /**
     * Plan that owns these features.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
