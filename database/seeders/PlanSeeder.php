<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name'          => 'Free',
                'slug'          => 'free',
                'description'   => 'Basic plan for individuals and tiny teams testing the waters.',
                'price'         => 0.00,
                'currency'      => 'USD',
                'billing_cycle' => 'monthly',
                'features'      => [
                    'max_users'              => 2,
                    'max_customers'          => 10,
                    'has_advanced_analytics' => false,
                    'has_api_access'         => false,
                    'rate_limit_per_minute'  => 30,
                    'custom_features'        => ['community_support' => true],
                ],
            ],
            [
                'name'          => 'Starter',
                'slug'          => 'starter',
                'description'   => 'Essential tools for growing small businesses.',
                'price'         => 29.00,
                'currency'      => 'USD',
                'billing_cycle' => 'monthly',
                'features'      => [
                    'max_users'              => 5,
                    'max_customers'          => 100,
                    'has_advanced_analytics' => false,
                    'has_api_access'         => false,
                    'rate_limit_per_minute'  => 60,
                    'custom_features'        => ['email_support' => true],
                ],
            ],
            [
                'name'          => 'Pro',
                'slug'          => 'pro',
                'description'   => 'Powerful capabilities and advanced analytics for scaling teams.',
                'price'         => 79.00,
                'currency'      => 'USD',
                'billing_cycle' => 'monthly',
                'features'      => [
                    'max_users'              => 20,
                    'max_customers'          => 1000,
                    'has_advanced_analytics' => true,
                    'has_api_access'         => true,
                    'rate_limit_per_minute'  => 120,
                    'custom_features'        => ['priority_support' => true, 'custom_exports' => true],
                ],
            ],
            [
                'name'          => 'Enterprise',
                'slug'          => 'enterprise',
                'description'   => 'Unlimited scale, dedicated support, and enterprise performance.',
                'price'         => 249.00,
                'currency'      => 'USD',
                'billing_cycle' => 'monthly',
                'features'      => [
                    'max_users'              => 999999,
                    'max_customers'          => 999999,
                    'has_advanced_analytics' => true,
                    'has_api_access'         => true,
                    'rate_limit_per_minute'  => 300,
                    'custom_features'        => ['dedicated_manager' => true, 'custom_sla' => true],
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $featureData = $planData['features'];
            unset($planData['features']);

            $plan = Plan::updateOrCreate(['slug' => $planData['slug']], $planData);
            PlanFeature::updateOrCreate(['plan_id' => $plan->id], $featureData);
        }
    }
}
