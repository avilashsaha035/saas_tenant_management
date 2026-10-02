<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRegistrationAndAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlanSeeder::class);
    }

    public function test_tenant_registration_creates_company_owner_and_active_subscription(): void
    {
        $payload = [
            'company_name'          => 'Innovatech Labs',
            'company_slug'          => 'innovatech',
            'domain'                => 'innovatech.test',
            'owner_name'            => 'John Innovator',
            'email'                 => 'john@innovatech.test',
            'password'              => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'plan_slug'             => 'starter',
        ];

        $response = $this->postJson('/api/v1/auth/register-tenant', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Tenant registered successfully with active subscription.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'tenant' => ['id', 'name', 'slug', 'status'],
                    'user'   => ['id', 'name', 'email', 'role'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('tenants', [
            'name' => 'Innovatech Labs',
            'slug' => 'innovatech',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@innovatech.test',
            'role'  => User::ROLE_OWNER,
        ]);

        $tenant = Tenant::where('slug', 'innovatech')->first();
        $this->assertNotNull($tenant->activeSubscription);
        $this->assertEquals('starter', $tenant->activeSubscription->plan->slug);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $tenant = Tenant::create(['name' => 'Demo Co', 'slug' => 'demo-co']);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Demo User',
            'email'     => 'demo@example.com',
            'password'  => bcrypt('secret123'),
            'role'      => User::ROLE_OWNER,
            'status'    => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'demo@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'email'],
                    'tenant' => ['id', 'name'],
                ],
            ]);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $tenant = Tenant::create(['name' => 'Demo Co', 'slug' => 'demo-co']);
        User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Demo User',
            'email'     => 'demo@example.com',
            'password'  => bcrypt('secret123'),
            'role'      => User::ROLE_OWNER,
            'status'    => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'demo@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid email or password credentials.',
            ]);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $tenant = Tenant::create(['name' => 'Profile Co', 'slug' => 'profile-co']);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Profile User',
            'email'     => 'profile@example.com',
            'password'  => bcrypt('secret123'),
            'role'      => User::ROLE_ADMIN,
            'status'    => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'user' => [
                        'email' => 'profile@example.com',
                        'role'  => 'admin',
                    ],
                ],
            ]);
    }
}
