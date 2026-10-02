<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $proPlan = Plan::where('slug', 'pro')->first();
        $freePlan = Plan::where('slug', 'free')->first();

        // 1. Acme Corporation (Pro Plan)
        $acme = Tenant::updateOrCreate(
            ['slug' => 'acme'],
            [
                'name'     => 'Acme Corporation',
                'domain'   => 'acme.saas.test',
                'status'   => 'active',
                'settings' => ['timezone' => 'UTC', 'currency' => 'USD'],
            ]
        );

        Subscription::updateOrCreate(
            ['tenant_id' => $acme->id],
            [
                'plan_id'   => $proPlan->id,
                'status'    => 'active',
                'starts_at' => now()->subMonth(),
                'ends_at'   => now()->addYear(),
            ]
        );

        // Seed Users for Acme
        TenantContext::setTenant($acme);

        $acmeUsers = [
            ['name' => 'Alice Acme (Owner)', 'email' => 'owner@acme.com', 'role' => User::ROLE_OWNER],
            ['name' => 'Bob Admin', 'email' => 'admin@acme.com', 'role' => User::ROLE_ADMIN],
            ['name' => 'Charlie Manager', 'email' => 'manager@acme.com', 'role' => User::ROLE_MANAGER],
            ['name' => 'David Member', 'email' => 'member@acme.com', 'role' => User::ROLE_MEMBER],
        ];

        foreach ($acmeUsers as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'tenant_id' => $acme->id,
                    'name'      => $userData['name'],
                    'password'  => Hash::make('password'),
                    'role'      => $userData['role'],
                    'status'    => 'active',
                ]
            );
        }

        // Seed Customers for Acme
        $sampleCustomers = [
            ['name' => 'Tesla Motors', 'email' => 'procure@tesla.com', 'phone' => '+1-555-0100', 'company_name' => 'Tesla Inc.', 'status' => Customer::STATUS_ACTIVE, 'revenue' => 45000.00],
            ['name' => 'Spotify Media', 'email' => 'sales@spotify.com', 'phone' => '+1-555-0101', 'company_name' => 'Spotify AB', 'status' => Customer::STATUS_ACTIVE, 'revenue' => 32000.00],
            ['name' => 'Airbnb Global', 'email' => 'team@airbnb.com', 'phone' => '+1-555-0102', 'company_name' => 'Airbnb Inc.', 'status' => Customer::STATUS_ACTIVE, 'revenue' => 28000.00],
            ['name' => 'Stripe Payments', 'email' => 'partners@stripe.com', 'phone' => '+1-555-0103', 'company_name' => 'Stripe Inc.', 'status' => Customer::STATUS_ACTIVE, 'revenue' => 55000.00],
            ['name' => 'Linear App', 'email' => 'biz@linear.app', 'phone' => '+1-555-0104', 'company_name' => 'Linear Orbit Inc.', 'status' => Customer::STATUS_LEAD, 'revenue' => 0.00],
            ['name' => 'Vercel Platform', 'email' => 'accounts@vercel.com', 'phone' => '+1-555-0105', 'company_name' => 'Vercel Inc.', 'status' => Customer::STATUS_ACTIVE, 'revenue' => 19000.00],
            ['name' => 'Supabase Corp', 'email' => 'contact@supabase.com', 'phone' => '+1-555-0106', 'company_name' => 'Supabase Pte.', 'status' => Customer::STATUS_CHURNED, 'revenue' => 5000.00],
            ['name' => 'HashiCorp Inc', 'email' => 'enterprise@hashicorp.com', 'phone' => '+1-555-0107', 'company_name' => 'HashiCorp', 'status' => Customer::STATUS_ACTIVE, 'revenue' => 62000.00],
        ];

        foreach ($sampleCustomers as $cust) {
            Customer::updateOrCreate(
                ['tenant_id' => $acme->id, 'email' => $cust['email']],
                $cust
            );
        }

        TenantContext::reset();

        // 2. Beta Innovations (Free Plan - near limit for testing limit tests)
        $beta = Tenant::updateOrCreate(
            ['slug' => 'beta'],
            [
                'name'     => 'Beta Innovations',
                'domain'   => 'beta.saas.test',
                'status'   => 'active',
                'settings' => ['timezone' => 'America/New_York'],
            ]
        );

        Subscription::updateOrCreate(
            ['tenant_id' => $beta->id],
            [
                'plan_id'   => $freePlan->id,
                'status'    => 'active',
                'starts_at' => now(),
                'ends_at'   => null,
            ]
        );

        TenantContext::setTenant($beta);

        User::updateOrCreate(
            ['email' => 'owner@beta.com'],
            [
                'tenant_id' => $beta->id,
                'name'      => 'Ben Beta (Owner)',
                'password'  => Hash::make('password'),
                'role'      => User::ROLE_OWNER,
                'status'    => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'member@beta.com'],
            [
                'tenant_id' => $beta->id,
                'name'      => 'Mia Beta (Member)',
                'password'  => Hash::make('password'),
                'role'      => User::ROLE_MEMBER,
                'status'    => 'active',
            ]
        );

        // 3 customers for Beta
        for ($i = 1; $i <= 3; $i++) {
            Customer::updateOrCreate(
                ['tenant_id' => $beta->id, 'email' => "lead{$i}@betacustomer.com"],
                [
                    'name'         => "Beta Client {$i}",
                    'phone'        => "+1-555-020{$i}",
                    'company_name' => "Beta Partner {$i}",
                    'status'       => Customer::STATUS_LEAD,
                    'revenue'      => 1200.00 * $i,
                ]
            );
        }

        TenantContext::reset();
    }
}
