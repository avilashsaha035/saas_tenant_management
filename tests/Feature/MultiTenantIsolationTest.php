<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $userA;
    protected User $userB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);

        // Tenant A
        $this->tenantA = Tenant::create(['name' => 'Company A', 'slug' => 'company-a']);
        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name'      => 'Alice A',
            'email'     => 'alice@companya.com',
            'password'  => bcrypt('password'),
            'role'      => User::ROLE_OWNER,
            'status'    => 'active',
        ]);

        // Tenant B
        $this->tenantB = Tenant::create(['name' => 'Company B', 'slug' => 'company-b']);
        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name'      => 'Bob B',
            'email'     => 'bob@companyb.com',
            'password'  => bcrypt('password'),
            'role'      => User::ROLE_OWNER,
            'status'    => 'active',
        ]);
    }

    public function test_tenant_b_cannot_see_tenant_a_customers_in_list(): void
    {
        // Create customer for Tenant A
        Customer::create([
            'tenant_id'    => $this->tenantA->id,
            'name'         => 'Secret Client A',
            'email'        => 'secret@clienta.com',
            'company_name' => 'Client A Inc',
            'status'       => 'active',
            'revenue'      => 50000.00,
        ]);

        // Create customer for Tenant B
        Customer::create([
            'tenant_id'    => $this->tenantB->id,
            'name'         => 'Client B Corp',
            'email'        => 'client@companyb.com',
            'company_name' => 'Client B Inc',
            'status'       => 'active',
            'revenue'      => 10000.00,
        ]);

        // Act as User B (Tenant B)
        $response = $this->actingAs($this->userB, 'sanctum')
            ->getJson('/api/v1/customers');

        $response->assertStatus(200);

        // Tenant B should only see Client B
        $response->assertJsonFragment(['name' => 'Client B Corp']);
        $response->assertJsonMissing(['name' => 'Secret Client A']);
    }

    public function test_tenant_b_cannot_view_tenant_a_customer_by_id(): void
    {
        $customerA = Customer::create([
            'tenant_id' => $this->tenantA->id,
            'name'      => 'Confidential Client A',
            'email'     => 'confidential@clienta.com',
        ]);

        $response = $this->actingAs($this->userB, 'sanctum')
            ->getJson("/api/v1/customers/{$customerA->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Customer not found.',
            ]);
    }

    public function test_tenant_b_cannot_update_or_delete_tenant_a_customer(): void
    {
        $customerA = Customer::create([
            'tenant_id' => $this->tenantA->id,
            'name'      => 'Confidential Client A',
            'email'     => 'confidential@clienta.com',
        ]);

        // Try update
        $updateResponse = $this->actingAs($this->userB, 'sanctum')
            ->putJson("/api/v1/customers/{$customerA->id}", [
                'name' => 'Hacked Name',
            ]);

        $updateResponse->assertStatus(404);

        // Try delete
        $deleteResponse = $this->actingAs($this->userB, 'sanctum')
            ->deleteJson("/api/v1/customers/{$customerA->id}");

        $deleteResponse->assertStatus(404);

        // Assert customerA was not modified
        $this->assertDatabaseHas('customers', [
            'id'   => $customerA->id,
            'name' => 'Confidential Client A',
        ]);
    }

    public function test_eloquent_global_tenant_scope_strictly_isolates_records(): void
    {
        Customer::create([
            'tenant_id' => $this->tenantA->id,
            'name'      => 'A Record',
        ]);

        Customer::create([
            'tenant_id' => $this->tenantB->id,
            'name'      => 'B Record',
        ]);

        // Set context to Tenant A
        TenantContext::setTenant($this->tenantA);
        $this->assertEquals(1, Customer::count());
        $this->assertEquals('A Record', Customer::first()->name);

        // Switch context to Tenant B
        TenantContext::setTenant($this->tenantB);
        $this->assertEquals(1, Customer::count());
        $this->assertEquals('B Record', Customer::first()->name);

        TenantContext::reset();
    }
}
