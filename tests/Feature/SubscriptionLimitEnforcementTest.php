<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionLimitEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);

        $freePlan = Plan::where('slug', 'free')->first();

        $this->tenant = Tenant::create([
            'name' => 'Limited Startup',
            'slug' => 'limited-startup',
        ]);

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id'   => $freePlan->id,
            'status'    => 'active',
            'starts_at' => now(),
        ]);

        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Startup Owner',
            'email'     => 'owner@startup.test',
            'password'  => bcrypt('password'),
            'role'      => User::ROLE_OWNER,
            'status'    => 'active',
        ]);
    }

    public function test_customer_creation_is_blocked_when_plan_limit_reached(): void
    {
        // Free plan limit is 10 customers. Pre-populate 10 customers:
        for ($i = 1; $i <= 10; $i++) {
            Customer::create([
                'tenant_id'    => $this->tenant->id,
                'name'         => "Customer {$i}",
                'email'        => "cust{$i}@test.com",
                'status'       => 'active',
            ]);
        }

        // Try creating 11th customer via API
        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/v1/customers', [
                'name'  => 'Overflow Customer',
                'email' => 'overflow@test.com',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'errors'  => [
                    'feature'         => 'max_customers',
                    'current_usage'   => 10,
                    'allowed_limit'   => 10,
                    'action_required' => 'upgrade_plan',
                ],
            ]);
    }

    public function test_upgrading_plan_allows_creating_more_customers(): void
    {
        // Fill 10 customers
        for ($i = 1; $i <= 10; $i++) {
            Customer::create([
                'tenant_id' => $this->tenant->id,
                'name'      => "Customer {$i}",
                'email'     => "cust{$i}@test.com",
            ]);
        }

        // Upgrade to Pro plan via API
        $upgradeResponse = $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/v1/subscriptions/change-plan', [
                'plan_slug' => 'pro',
            ]);

        $upgradeResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        // Attempt creating 11th customer after upgrade
        $customerResponse = $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/v1/customers', [
                'name'  => 'Pro Customer',
                'email' => 'pro@test.com',
            ]);

        $customerResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data'    => ['name' => 'Pro Customer'],
            ]);
    }

    public function test_user_limit_is_enforced(): void
    {
        // Free plan allows max 2 users. Owner is already 1 user.
        // Creating 2nd user should succeed:
        $secondUserResponse = $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/v1/users', [
                'name'     => 'Second User',
                'email'    => 'second@test.com',
                'password' => 'password123',
                'role'     => 'member',
            ]);

        $secondUserResponse->assertStatus(201);

        // Creating 3rd user should fail with 403 limit exceeded:
        $thirdUserResponse = $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/v1/users', [
                'name'     => 'Third User',
                'email'    => 'third@test.com',
                'password' => 'password123',
                'role'     => 'member',
            ]);

        $thirdUserResponse->assertStatus(403)
            ->assertJson([
                'success' => false,
                'errors'  => [
                    'feature'         => 'max_users',
                    'current_usage'   => 2,
                    'allowed_limit'   => 2,
                    'action_required' => 'upgrade_plan',
                ],
            ]);
    }
}
