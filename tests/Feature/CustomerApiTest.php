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

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);

        $proPlan = Plan::where('slug', 'pro')->first();

        $this->tenant = Tenant::create(['name' => 'Alpha Corp', 'slug' => 'alpha-corp']);
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id'   => $proPlan->id,
            'status'    => 'active',
            'starts_at' => now(),
        ]);

        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Alpha Admin',
            'email'     => 'admin@alphacorp.test',
            'password'  => bcrypt('password'),
            'role'      => User::ROLE_ADMIN,
            'status'    => 'active',
        ]);
    }

    public function test_can_list_customers_with_pagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Customer::create([
                'tenant_id' => $this->tenant->id,
                'name'      => "Customer {$i}",
                'email'     => "customer{$i}@alpha.test",
                'status'    => 'active',
            ]);
        }

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/v1/customers?per_page=2&page=1');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'meta'    => [
                    'current_page' => 1,
                    'per_page'     => 2,
                    'total'        => 5,
                    'last_page'    => 3,
                ],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_filter_customers_by_status(): void
    {
        Customer::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Active Customer',
            'status'    => Customer::STATUS_ACTIVE,
        ]);

        Customer::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Lead Customer',
            'status'    => Customer::STATUS_LEAD,
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/v1/customers?status=lead');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Lead Customer', $response->json('data.0.name'));
    }

    public function test_can_search_customers_by_keyword(): void
    {
        Customer::create([
            'tenant_id'    => $this->tenant->id,
            'name'         => 'Bruce Wayne',
            'company_name' => 'Wayne Enterprises',
        ]);

        Customer::create([
            'tenant_id'    => $this->tenant->id,
            'name'         => 'Clark Kent',
            'company_name' => 'Daily Planet',
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson('/api/v1/customers?search=wayne');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Bruce Wayne', $response->json('data.0.name'));
    }

    public function test_can_crud_customer(): void
    {
        // 1. Create
        $createResponse = $this->actingAs($this->owner, 'sanctum')
            ->postJson('/api/v1/customers', [
                'name'         => 'Acme Partner',
                'email'        => 'partner@acme.com',
                'phone'        => '+1-555-9988',
                'company_name' => 'Acme Inc',
                'status'       => 'active',
                'revenue'      => 15000.50,
                'notes'        => 'High value account',
            ]);

        $createResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'name'         => 'Acme Partner',
                    'company_name' => 'Acme Inc',
                    'revenue'      => 15000.50,
                ],
            ]);

        $customerId = $createResponse->json('data.id');

        // 2. Read (Show)
        $showResponse = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/v1/customers/{$customerId}");

        $showResponse->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Acme Partner']]);

        // 3. Update
        $updateResponse = $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/v1/customers/{$customerId}", [
                'name'    => 'Acme Global Partner',
                'revenue' => 20000.00,
            ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name'    => 'Acme Global Partner',
                    'revenue' => 20000.00,
                ],
            ]);

        // 4. Delete
        $deleteResponse = $this->actingAs($this->owner, 'sanctum')
            ->deleteJson("/api/v1/customers/{$customerId}");

        $deleteResponse->assertStatus(200);

        // Assert soft delete
        $this->assertSoftDeleted('customers', ['id' => $customerId]);
    }
}
