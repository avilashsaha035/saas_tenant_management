<?php

namespace App\Jobs;

use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SubscriptionRenewalAlertJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Renewal warning dispatched for Subscription [{$this->subscription->id}] of Tenant [{$this->subscription->tenant_id}].");
    }
}
