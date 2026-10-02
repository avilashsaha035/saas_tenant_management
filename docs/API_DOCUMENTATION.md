# SaaS Subscription & Tenant Management API Documentation

**Base URL:** `http://localhost/api/v1` (or your local virtual host / `php artisan serve` URL, e.g. `http://127.0.0.1:8000/api/v1`)  
**Standard Headers:**
```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <your_api_token>
```

---

## Standard API Response Envelope

Every endpoint adheres to a standardized JSON response structure:

### Success Response
```json
{
  "success": true,
  "message": "Resource retrieved successfully.",
  "data": { ... },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 68
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

## 1. Authentication & Onboarding

### 1.1 Register Tenant & Company
- **Method:** `POST`
- **Endpoint:** `/auth/register-tenant`
- **Auth:** Public
- **Request Body:**
```json
{
  "company_name": "Innovatech Solutions",
  "company_slug": "innovatech",
  "domain": "innovatech.example.com",
  "owner_name": "John Doe",
  "email": "john@innovatech.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "plan_slug": "starter"
}
```
- **Response (201 Created):**
```json
{
  "success": true,
  "message": "Tenant registered successfully with active subscription.",
  "data": {
    "tenant": {
      "id": 3,
      "name": "Innovatech Solutions",
      "slug": "innovatech",
      "domain": "innovatech.example.com",
      "status": "active"
    },
    "user": {
      "id": 7,
      "tenant_id": 3,
      "name": "John Doe",
      "email": "john@innovatech.com",
      "role": "owner",
      "status": "active"
    },
    "token": "1|qW84gKljv..."
  }
}
```

### 1.2 User Login
- **Method:** `POST`
- **Endpoint:** `/auth/login`
- **Auth:** Public
- **Request Body:**
```json
{
  "email": "owner@acme.com",
  "password": "password"
}
```
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "token": "2|hJk3498...",
    "user": {
      "id": 1,
      "name": "Alice Acme (Owner)",
      "email": "owner@acme.com",
      "role": "owner"
    },
    "tenant": {
      "id": 1,
      "name": "Acme Corporation",
      "slug": "acme"
    }
  }
}
```

### 1.3 User Logout
- **Method:** `POST`
- **Endpoint:** `/auth/logout`
- **Auth:** Required (Bearer Token)
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Successfully logged out.",
  "data": null
}
```

### 1.4 Get Profile
- **Method:** `GET`
- **Endpoint:** `/auth/me`
- **Auth:** Required
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Profile retrieved successfully.",
  "data": {
    "user": {
      "id": 1,
      "name": "Alice Acme (Owner)",
      "email": "owner@acme.com",
      "role": "owner"
    },
    "tenant": {
      "id": 1,
      "name": "Acme Corporation",
      "slug": "acme"
    }
  }
}
```

---

## 2. Subscription Plans & Management

### 2.1 List Available Plans
- **Method:** `GET`
- **Endpoint:** `/plans`
- **Auth:** Public
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Plans retrieved successfully.",
  "data": [
    {
      "id": 1,
      "name": "Free",
      "slug": "free",
      "price": 0,
      "currency": "USD",
      "billing_cycle": "monthly",
      "features": {
        "max_users": 2,
        "max_customers": 10,
        "has_advanced_analytics": false,
        "rate_limit_per_minute": 30
      }
    },
    {
      "id": 3,
      "name": "Pro",
      "slug": "pro",
      "price": 79,
      "currency": "USD",
      "billing_cycle": "monthly",
      "features": {
        "max_users": 20,
        "max_customers": 1000,
        "has_advanced_analytics": true,
        "rate_limit_per_minute": 120
      }
    }
  ]
}
```

### 2.2 Get Current Tenant Subscription
- **Method:** `GET`
- **Endpoint:** `/subscriptions/current`
- **Auth:** Required
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Active subscription retrieved.",
  "data": {
    "id": 1,
    "status": "active",
    "is_active": true,
    "starts_at": "2026-09-02T12:00:00Z",
    "ends_at": "2027-09-02T12:00:00Z",
    "plan": {
      "name": "Pro",
      "slug": "pro",
      "price": 79
    }
  }
}
```

### 2.3 Change / Upgrade Subscription Plan
- **Method:** `POST`
- **Endpoint:** `/subscriptions/change-plan`
- **Auth:** Required (`owner` role only)
- **Request Body:**
```json
{
  "plan_slug": "enterprise"
}
```
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Subscription successfully upgraded/changed to 'Enterprise'.",
  "data": {
    "id": 5,
    "status": "active",
    "plan": {
      "name": "Enterprise",
      "slug": "enterprise",
      "price": 249
    }
  }
}
```

### 2.4 Cancel Subscription
- **Method:** `POST`
- **Endpoint:** `/subscriptions/cancel`
- **Auth:** Required (`owner` role only)
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Subscription cancelled successfully.",
  "data": {
    "status": "canceled"
  }
}
```

---

## 3. Customer Management (CRM APIs)

### 3.1 List Customers with Filtering, Search & Pagination
- **Method:** `GET`
- **Endpoint:** `/customers`
- **Auth:** Required
- **Query Parameters:**
  - `page`: Page number (default: 1)
  - `per_page`: Number of records per page (default: 15, max: 100)
  - `search`: Keyword searched across `name`, `email`, `company_name`, `phone`
  - `status`: Filter by `lead`, `active`, or `churned`
  - `min_revenue`: Filter by minimum customer revenue
  - `sort_by`: Sort column (`created_at`, `name`, `status`, `revenue`)
  - `sort_dir`: Sort direction (`asc`, `desc`)
- **Sample Request:** `GET /customers?status=active&search=tesla&per_page=10`
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Customers retrieved successfully.",
  "data": [
    {
      "id": 1,
      "tenant_id": 1,
      "name": "Tesla Motors",
      "email": "procure@tesla.com",
      "phone": "+1-555-0100",
      "company_name": "Tesla Inc.",
      "status": "active",
      "revenue": 45000,
      "notes": null,
      "created_at": "2026-10-02T06:37:03.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 1
  }
}
```

### 3.2 Create Customer (with Plan Limit Enforcement)
- **Method:** `POST`
- **Endpoint:** `/customers`
- **Auth:** Required (`owner`, `admin`, `manager`)
- **Request Body:**
```json
{
  "name": "Stripe Payments",
  "email": "enterprise@stripe.com",
  "phone": "+1-555-4001",
  "company_name": "Stripe Inc.",
  "status": "active",
  "revenue": 85000,
  "notes": "Key enterprise integration partner."
}
```
- **Response (201 Created):**
```json
{
  "success": true,
  "message": "Customer created successfully.",
  "data": {
    "id": 9,
    "name": "Stripe Payments",
    "email": "enterprise@stripe.com",
    "status": "active",
    "revenue": 85000
  }
}
```
- **Error Response (403 Forbidden - Limit Exceeded):**
```json
{
  "success": false,
  "message": "Subscription limit exceeded for feature 'max_customers'. Current usage: 10, Allowed limit: 10. Please upgrade your subscription plan to add more.",
  "errors": {
    "feature": "max_customers",
    "current_usage": 10,
    "allowed_limit": 10,
    "action_required": "upgrade_plan"
  }
}
```

### 3.3 Get Customer Details
- **Method:** `GET`
- **Endpoint:** `/customers/{id}`
- **Auth:** Required

### 3.4 Update Customer
- **Method:** `PUT` / `PATCH`
- **Endpoint:** `/customers/{id}`
- **Auth:** Required (`owner`, `admin`, `manager`)

### 3.5 Delete Customer
- **Method:** `DELETE`
- **Endpoint:** `/customers/{id}`
- **Auth:** Required (`owner`, `admin`)
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Customer deleted successfully.",
  "data": null
}
```

---

## 4. Team User Management (RBAC)

### 4.1 List Team Users
- **Method:** `GET`
- **Endpoint:** `/users`
- **Auth:** Required (`owner`, `admin`, `manager`)

### 4.2 Invite / Create Team Member (with User Limit Enforcement)
- **Method:** `POST`
- **Endpoint:** `/users`
- **Auth:** Required (`owner`, `admin`)
- **Request Body:**
```json
{
  "name": "Sarah Connor",
  "email": "sarah@acme.com",
  "password": "TempPassword123!",
  "role": "manager",
  "status": "active"
}
```
- **Response (201 Created):**
```json
{
  "success": true,
  "message": "Team member created successfully.",
  "data": {
    "id": 5,
    "name": "Sarah Connor",
    "email": "sarah@acme.com",
    "role": "manager",
    "status": "active"
  }
}
```

---

## 5. Dashboard Analytics & Real-Time Usage

### 5.1 Aggregated Dashboard Analytics (Redis Cached)
- **Method:** `GET`
- **Endpoint:** `/dashboard/analytics`
- **Auth:** Required
- **Query Parameters:**
  - `refresh=true`: (Optional) Forces cache invalidation and fresh database computation.
- **Response (200 OK - Served via Redis in ~2ms):**
```json
{
  "success": true,
  "message": "Dashboard analytics retrieved successfully (served via Redis cache).",
  "data": {
    "tenant": {
      "id": 1,
      "name": "Acme Corporation",
      "slug": "acme",
      "status": "active"
    },
    "subscription": {
      "plan_name": "Pro",
      "status": "active",
      "billing_cycle": "monthly",
      "price": 79,
      "days_remaining": 335
    },
    "quota_usage": {
      "users": {
        "current": 4,
        "limit": 20,
        "percentage_used": 20,
        "is_limit_reached": false
      },
      "customers": {
        "current": 8,
        "limit": 1000,
        "percentage_used": 0.8,
        "is_limit_reached": false
      }
    },
    "metrics": {
      "total_customers": 8,
      "active_customers": 6,
      "lead_customers": 1,
      "churned_customers": 1,
      "total_revenue": 246000,
      "avg_revenue": 30750
    },
    "recent_activities": [
      {
        "id": 1,
        "action": "customer_created",
        "description": "Created customer 'Tesla Motors' (procure@tesla.com).",
        "created_at": "2026-10-02T06:37:03Z"
      }
    ],
    "cached_at": "2026-10-02T06:40:15Z"
  }
}
```

### 5.2 Real-time Quota Usage
- **Method:** `GET`
- **Endpoint:** `/dashboard/usage`
- **Auth:** Required
- **Response (200 OK):**
```json
{
  "success": true,
  "message": "Subscription usage summary retrieved.",
  "data": {
    "plan": {
      "name": "Pro",
      "slug": "pro",
      "price": 79
    },
    "users": {
      "current": 4,
      "limit": 20,
      "remaining": 16,
      "percentage": 20
    },
    "customers": {
      "current": 8,
      "limit": 1000,
      "remaining": 992,
      "percentage": 0.8
    },
    "features": {
      "has_advanced_analytics": true,
      "has_api_access": true,
      "rate_limit_per_minute": 120
    }
  }
}
```
