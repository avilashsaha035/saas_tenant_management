<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendTenantWelcomeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public User $owner
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Production mail sending simulated or logged cleanly
        Log::info("Asynchronous Welcome Email sent to Owner [{$this->owner->email}] for Tenant [{$this->tenant->name}].");
    }
}
