<?php

namespace App\Services;

use App\Contracts\TenantServiceInterface;
use App\Jobs\SendTenantWelcomeJob;
use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantService implements TenantServiceInterface
{
    /**
     * Register a new company/tenant with owner user and subscription plan.
     */
    public function registerTenant(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. Resolve or generate tenant slug
            $slug = !empty($data['company_slug'])
                ? Str::slug($data['company_slug'])
                : Str::slug($data['company_name']);

            // Ensure uniqueness
            $originalSlug = $slug;
            $counter = 1;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = "{$originalSlug}-" . $counter++;
            }

            // 2. Create Tenant
            $tenant = Tenant::create([
                'name'     => $data['company_name'],
                'slug'     => $slug,
                'domain'   => $data['domain'] ?? null,
                'status'   => 'active',
                'settings' => $data['settings'] ?? [],
            ]);

            // 3. Resolve Plan (default to 'free' plan if not specified)
            $planSlug = $data['plan_slug'] ?? 'free';
            $plan = Plan::where('slug', $planSlug)->first()
                ?? Plan::where('slug', 'free')->first()
                ?? Plan::first();

            // 4. Create Active Subscription
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id'   => $plan->id,
                'status'    => 'active',
                'starts_at' => now(),
                'ends_at'   => now()->addMonth(),
            ]);

            // 5. Create Owner User
            $owner = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $data['owner_name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'role'      => User::ROLE_OWNER,
                'status'    => 'active',
            ]);

            // 6. Record Initial Activity Log
            ActivityLog::create([
                'tenant_id'   => $tenant->id,
                'user_id'     => $owner->id,
                'action'      => 'tenant_registered',
                'entity_type' => 'Tenant',
                'entity_id'   => $tenant->id,
                'description' => "Tenant '{$tenant->name}' registered with owner '{$owner->name}'.",
                'ip_address'  => request()->ip(),
            ]);

            // 7. Dispatch asynchronous welcome job
            SendTenantWelcomeJob::dispatch($tenant, $owner);

            // 8. Generate API token for immediate access
            $token = $owner->createToken('api-token')->plainTextToken;

            return [
                'tenant'       => $tenant->load('activeSubscription.plan.features'),
                'user'         => $owner,
                'token'        => $token,
                'subscription' => $subscription->load('plan.features'),
            ];
        });
    }

    /**
     * Get tenant profile with current subscription and plan.
     */
    public function getTenantProfile(Tenant $tenant): Tenant
    {
        return $tenant->load(['activeSubscription.plan.features']);
    }

    /**
     * Update tenant company profile or settings.
     */
    public function updateTenantProfile(Tenant $tenant, array $data): Tenant
    {
        $tenant->update(array_filter([
            'name'     => $data['name'] ?? null,
            'domain'   => $data['domain'] ?? null,
            'settings' => isset($data['settings'])
                ? array_merge($tenant->settings ?? [], $data['settings'])
                : null,
        ]));

        ActivityLog::create([
            'tenant_id'   => $tenant->id,
            'user_id'     => auth()->id(),
            'action'      => 'tenant_updated',
            'entity_type' => 'Tenant',
            'entity_id'   => $tenant->id,
            'description' => "Updated tenant profile for '{$tenant->name}'.",
            'ip_address'  => request()->ip(),
        ]);

        return $tenant->fresh(['activeSubscription.plan.features']);
    }
}
