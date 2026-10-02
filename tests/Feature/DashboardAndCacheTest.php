<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardAndCacheTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);

        $proPlan = Plan::where('slug', 'pro')->first();

        $this->tenant = Tenant::create(['name' => 'Cache Test Co', 'slug' => 'cache-test-co']);
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id'   => $proPlan->id,
            'status'    => 'active',
            'starts_at' => now(),
        ]);

        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Cache Admin',
            'email'     => 'admin@cacheco.test',
            'password'  => bcrypt('password'),
            'role'      => User::ROLE_OWNER,
            'status'    => 'active',
        ]);
    }

    public function test_dashboard_analytics_is_cached_and_invalidated_on_customer_change(): void
    {
        $cacheKey = "tenant:{$this->tenant->id}:dashboard:analytics";

        // Initial state: ensure cache is clean
        Cache::forget($cacheKey);
        $this->assertFalse(Cache::has($cacheKey));

        // 1. First call computes and caches data
        $response1 = $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/v1/dashboard/analytics');

        $response1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'tenant' => ['id' => $this->tenant->id],
                    'metrics' => ['total_customers' => 0],
                ],
            ]);

        // Assert Redis/application cache now contains this key
        $this->assertTrue(Cache::has($cacheKey));

        // 2. Creating a customer should trigger cache invalidation
        $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/v1/customers', [
                'name'    => 'New Invalidation Trigger Customer',
                'status'  => 'active',
                'revenue' => 5000,
            ])
            ->assertStatus(201);

        // Cache must now be invalidated (forgotten)
        $this->assertFalse(Cache::has($cacheKey));

        // 3. Second call to analytics recomputes with fresh data and caches again
        $response2 = $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/v1/dashboard/analytics');

        $response2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'metrics' => [
                        'total_customers'  => 1,
                        'active_customers' => 1,
                        'total_revenue'    => 5000,
                    ],
                ],
            ]);

        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_usage_summary_endpoint(): void
    {
        Customer::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Usage Client',
            'status'    => 'active',
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/v1/dashboard/usage');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'plan' => ['slug' => 'pro'],
                    'customers' => [
                        'current' => 1,
                        'limit'   => 1000,
                    ],
                    'users' => [
                        'current' => 1,
                        'limit'   => 20,
                    ],
                ],
            ]);
    }
}
