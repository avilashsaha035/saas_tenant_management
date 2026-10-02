<?php

namespace App\Providers;

use App\Contracts\AnalyticsServiceInterface;
use App\Contracts\CustomerServiceInterface;
use App\Contracts\FeatureLimitServiceInterface;
use App\Contracts\SubscriptionServiceInterface;
use App\Contracts\TenantServiceInterface;
use App\Contracts\UserServiceInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Eloquent\CustomerRepository;
use App\Services\AnalyticsService;
use App\Services\CustomerService;
use App\Services\FeatureLimitService;
use App\Services\SubscriptionService;
use App\Services\TenantContext;
use App\Services\TenantService;
use App\Services\UserService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Repositories
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);

        // Bind Services
        $this->app->singleton(FeatureLimitServiceInterface::class, FeatureLimitService::class);
        $this->app->bind(CustomerServiceInterface::class, CustomerService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
        $this->app->bind(AnalyticsServiceInterface::class, AnalyticsService::class);
        $this->app->bind(TenantServiceInterface::class, TenantService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dynamic Rate Limiting based on Tenant Subscription Plan
        RateLimiter::for('api', function (Request $request) {
            $tenant = TenantContext::getTenant();
            $features = $tenant?->getPlanFeatures();
            $maxPerMinute = $features?->rate_limit_per_minute ?? 60;

            $key = $request->user()
                ? "tenant_{$request->user()->tenant_id}_user_{$request->user()->id}"
                : ($request->ip() ?? 'global');

            return Limit::perMinute($maxPerMinute)->by($key);
        });
    }
}
