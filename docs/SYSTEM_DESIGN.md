# System Design & Architectural Decisions

## 1. High-Level Architecture Overview

This application is built as an enterprise-grade, multi-tenant SaaS backend API following Clean Architecture and Domain-Driven design principles within the Laravel 11/12 framework.

```
┌───────────────────────────────────────────────────────────┐
│                     Client Requests                       │
│    (REST API Consumers / Frontend SPA / Mobile Apps)      │
└─────────────────────────────┬─────────────────────────────┘
                              │
                              ▼
┌───────────────────────────────────────────────────────────┐
│                    HTTP Middleware Layer                  │
│   • ThrottleRequests (Dynamic Rate Limiting per Plan)    │
│   • IdentifyTenant (Binds TenantContext from Token/Host)  │
│   • Authenticate (Sanctum Bearer Token Validation)       │
│   • CheckRole (RBAC Permission Enforcement)              │
│   • EnsureFeatureLimit (Subscription Quota Guard)         │
└─────────────────────────────┬─────────────────────────────┘
                              │
                              ▼
┌───────────────────────────────────────────────────────────┐
│                  Presentation Layer (API)                 │
│   • FormRequests (Strict Validation & Input Sanitation)   │
│   • Policies & Gates (Granular Authorization)             │
│   • Controllers (Thin Orchestration only)                 │
│   • API Resources (Consistent JSON Output Formatting)     │
└─────────────────────────────┬─────────────────────────────┘
                              │
                              ▼
┌───────────────────────────────────────────────────────────┐
│                    Business Service Layer                 │
│   • TenantService (Registration, Company Profiles)        │
│   • SubscriptionService (Plans, Upgrades, Downgrades)     │
│   • FeatureLimitService (Quota Enforcement Engine)        │
│   • CustomerService (CRM Business Logic & Cache Inval.)   │
│   • UserService (Team Member Management & Roles)          │
│   • AnalyticsService (Metric Calculation & Aggregation)   │
└──────────────┬─────────────────────────────┬──────────────┘
               │                             │
               ▼                             ▼
┌──────────────────────────────┐ ┌──────────────────────────┐
│       Repository Layer       │ │    Asynchronous Queues   │
│ • CustomerRepositoryInterface│ │ • SendTenantWelcomeJob   │
│ • Eloquent Implementations   │ │ • RecalculateTenantUsage │
└──────────────┬───────────────┘ └──────────────────────────┘
               │
               ▼
┌──────────────────────────────┐ ┌──────────────────────────┐
│   Relational Storage (MySQL) │ │ In-Memory Caching (Redis)│
│ • Scoped by `tenant_id`      │ │ • Scoped Keys per Tenant │
│ • Compound Indexes           │ │ • Atomic Cache Eviction  │
└──────────────────────────────┘ └──────────────────────────┘
```

---

## 2. SOLID Principles Applied

### 2.1 Single Responsibility Principle (SRP)
- **Controllers** only accept HTTP requests and return standard API responses.
- **FormRequests** (`RegisterTenantRequest`, `StoreCustomerRequest`) encapsulate all input validation logic.
- **Services** (`CustomerService`, `SubscriptionService`) house the pure business logic and cache invalidation rules.
- **Repositories** (`CustomerRepository`) deal exclusively with data access queries.

### 2.2 Open/Closed Principle (OCP)
- Quota and feature checking is decoupled behind `FeatureLimitServiceInterface`. Adding new feature limits or plan rules (e.g. storage capacity or custom webhooks) requires adding logic to the service without altering existing controllers or database repositories.

### 2.3 Liskov Substitution Principle (LSP)
- All concrete services implement explicit contracts (`CustomerServiceInterface`, `TenantServiceInterface`, `SubscriptionServiceInterface`). Any implementation can be swapped (e.g. for mock implementations during automated testing) without breaking dependent classes.

### 2.4 Interface Segregation Principle (ISP)
- Rather than one bloated monolithic repository, we create focused, purpose-specific contracts (`CustomerRepositoryInterface`, `FeatureLimitServiceInterface`).

### 2.5 Dependency Inversion Principle (DIP)
- High-level modules (Controllers and Services) depend upon abstractions (Interfaces bound in [`AppServiceProvider.php`](file:///c:/projects/saas_tenant_management/app/Providers/AppServiceProvider.php)) rather than concrete classes.

---

## 3. Design Patterns Applied

1. **Repository Pattern:** Separates database querying logic from business rules, allowing uniform testing and decoupled persistence.
2. **Service Layer Pattern:** Encapsulates transaction management, quota validations, and event dispatches in dedicated domain services.
3. **Strategy / Specification Pattern:** In `FeatureLimitService`, each plan feature check is encapsulated in distinct methods allowing flexible rule evaluation.
4. **Global Scope Pattern:** `TenantScope` and `BelongsToTenant` automatically append tenant constraints across Eloquent models.
5. **Context Singleton Pattern:** `TenantContext` provides a thread-safe, static registry for the currently resolved tenant across the request lifecycle.
6. **Observer / Event Pattern:** Entity updates trigger cache eviction and background jobs.

---

## 4. Multi-Tenancy Architecture Comparison & Trade-Offs

| Approach | Pros | Cons | Decision |
|---|---|---|---|
| **Database-per-Tenant** | Total physical data isolation. | Expensive infrastructure; migration complexity across 1000s of DBs; connection exhaustion. | Rejected for this scale. |
| **Schema-per-Tenant** (PostgreSQL schemas) | Good logical separation. | Complex cross-tenant migrations; table limits in MySQL; high maintenance. | Rejected for MySQL. |
| **Shared Database with Discriminator (`tenant_id`)** | **Extremely cost-effective; instant tenant provisioning; simple cross-tenant aggregation; connection pooling friendly.** | Requires rigorous column-level scoping and testing. | **Selected & Implemented with Eloquent Global Scope & Policies.** |

---

## 5. Security & Access Control (RBAC)

### 5.1 Role Matrix
| Role | View Customers | Create/Edit Customers | Delete Customers | Manage Team Users | Upgrade / Cancel Subscription | Manage Company Settings |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Owner** | Yes | Yes | Yes | Yes | Yes | Yes |
| **Admin** | Yes | Yes | Yes | Yes | No | No |
| **Manager** | Yes | Yes | No | View only | No | No |
| **Member** | View only | No | No | No | No | No |

### 5.2 Dynamic Rate Limiting per Plan
To prevent API abuse and provide tiered Quality of Service (QoS):
- **Free Plan:** 30 requests / minute
- **Starter Plan:** 60 requests / minute
- **Pro Plan:** 120 requests / minute
- **Enterprise Plan:** 300 requests / minute
- Implemented dynamically via Laravel's `RateLimiter` resolving the tenant's active plan limit.

---

## 6. Horizontal Scalability Roadmap

1. **Read/Write DB Splitting:** Configure MySQL primary for writes and replicas for read queries (`sticky` session support in `config/database.php`).
2. **Tenant Sharding by ID Hash:** When scaling beyond 50,000 tenants, partition tenants into database clusters using a hash of `tenant_id`.
3. **Redis Cluster & Sentinel:** Multi-node Redis cluster with automatic failover for caching and queue management.
