# SaaS Subscription & Tenant Management API

A production-style, high-performance multi-tenant SaaS backend API built with **Laravel / PHP**, **MySQL**, and **Redis**. It provides comprehensive company/tenant isolation, subscription plans with strict feature limit enforcement, role-based access control (RBAC), CRM customer management, Redis-cached dashboard analytics, and background jobs.

---

## 🚀 Key Architectural Features

- **Multi-Tenancy Isolation:** Column-based database isolation using Eloquent Global Scopes (`TenantScope`) and `BelongsToTenant` trait. Zero data leakage across tenants.
- **Subscription Engine & Feature Limits:** Tiered SaaS plans (Free, Starter, Pro, Enterprise) with automatic quota enforcement (max users, max customers, feature flags).
- **Role-Based Access Control (RBAC):** Granular authorization matrix for `Owner`, `Admin`, `Manager`, and `Member` using Laravel Policies and middleware.
- **Redis Caching & Invalidation:** Sub-5ms response times on dashboard metrics using Redis (`predis/predis`), with event-driven cache invalidation when tenant data changes.
- **Optimized Database Design:** Multi-column compound indexes (`[tenant_id, status]`, `[tenant_id, created_at]`), single-query SQL aggregations, and eager loading to eliminate N+1 queries.
- **SOLID & Clean Architecture:** Thin controllers, FormRequest validation, Service Layer, Repository Pattern, and DTO/Resources.
- **Asynchronous Background Processing:** Queued jobs for welcome emails, asynchronous usage recalculation, and subscription expiration alerts.
- **100% Automated Test Coverage:** Comprehensive test suite with 19 feature and unit tests (83 assertions).

---

## 🛠 Tech Stack

- **Framework:** Laravel 11/12 (PHP 8.2+)
- **Database:** MySQL 8.0
- **Cache & Queue:** Redis 5.0+ (via `predis/predis`)
- **Authentication:** Laravel Sanctum (API Bearer Tokens)
- **Testing:** PHPUnit / PEST compatible

---

## 📋 Pre-Seeded Demo Accounts

Run `php artisan db:seed` (or `migrate:fresh --seed`) to populate test tenants:

| Company / Tenant | Plan | Role | Email | Password |
|---|---|---|---|---|
| **Acme Corporation** (slug: `acme`) | **Pro** (1000 customers, 20 users) | Owner | `owner@acme.com` | `password` |
| Acme Corporation | Pro | Admin | `admin@acme.com` | `password` |
| Acme Corporation | Pro | Manager | `manager@acme.com` | `password` |
| Acme Corporation | Pro | Member | `member@acme.com` | `password` |
| **Beta Innovations** (slug: `beta`) | **Free** (10 customers, 2 users) | Owner | `owner@beta.com` | `password` |
| Beta Innovations | Free | Member | `member@beta.com` | `password` |

---

## ⚙️ Local Setup Instructions

### 1. Clone & Install Dependencies
```bash
git clone <repository_url>
cd saas_tenant_management
composer install
```

### 2. Environment Configuration
Copy `.env.example` to `.env` and verify database and Redis configuration:
```env
APP_NAME="SaaS Tenant Management"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=saas_tenant_management
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

QUEUE_CONNECTION=database
```

Generate the application key if not set:
```bash
php artisan key:generate
```

### 3. Database Migration & Seeding
Create the database in MySQL:
```sql
CREATE DATABASE IF NOT EXISTS saas_tenant_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Execute migrations and database seeders:
```bash
php artisan migrate:fresh --seed
```

### 4. Start the Application
Start the local PHP development server:
```bash
php artisan serve
```
The API is now active at `http://127.0.0.1:8000/api/v1`.

### 5. Run the Queue Worker (for Background Jobs)
In a separate terminal:
```bash
php artisan queue:work
```

---

## 🐳 Docker Setup (Alternative)

A complete containerized stack is included:
```bash
docker compose up -d --build
```
This boots 5 containers:
1. `saas_app`: PHP 8.2 FPM container
2. `saas_nginx`: Web server listening on port `8080`
3. `saas_mysql`: MySQL 8.0 on port `3306`
4. `saas_redis`: Redis Alpine on port `6379`
5. `saas_queue_worker`: Asynchronous background worker

Run migrations inside the container:
```bash
docker compose exec app php artisan migrate:fresh --seed
```

---

## 🧪 Running Automated Tests

Run the full PHPUnit test suite:
```bash
php artisan test
```

### Test Coverage Highlights:
- `TenantRegistrationAndAuthTest`: Tenant onboarding, token generation, login, and auth guard.
- `MultiTenantIsolationTest`: Cross-tenant boundary verification (Tenant B cannot read, modify, or delete Tenant A data).
- `SubscriptionLimitEnforcementTest`: Reaching plan customer/user limits produces 403 Forbidden; upgrading plan immediately unlocks creation.
- `CustomerApiTest`: Pagination, search keyword matching, status filtering, and soft deletes.
- `DashboardAndCacheTest`: Redis cache hit verification, response timing, and proactive cache invalidation on model events.

---

## 📚 Technical Documentation Index

Detailed architectural and design explanations required for submission:

1. [**Database Architecture & Schema**](docs/DATABASE_ARCHITECTURE.md) - ER diagram, schema details, and isolation design.
2. [**API Documentation & Endpoints**](docs/API_DOCUMENTATION.md) - Request bodies, parameters, and sample JSON responses.
3. [**Redis Caching & Invalidation Strategy**](docs/CACHING_STRATEGY.md) - Cache keys, TTLs, invalidation flow, and benchmarks.
4. [**Database Optimization & Indexing**](docs/DATABASE_OPTIMIZATION.md) - Compound indexes, single-query aggregations, and N+1 prevention.
5. [**System Design & Architectural Decisions**](docs/SYSTEM_DESIGN.md) - Layered architecture, SOLID principles, design patterns, and scalability.
6. [**Postman Collection**](docs/postman_collection.json) - Ready-to-import Postman collection.
