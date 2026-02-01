# IP Address Management System - Implementation Plan

**Test:** Senior Fullstack Developer Practical Test  
**Tech Stack:** Laravel (all services), Vue 3 + TypeScript, MySQL, Redis  
**Timeline:** 5 Days (Accelerated)

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Day 1: Project Setup & Infrastructure](#day-1-project-setup--infrastructure)
3. [Day 2: Auth Service](#day-2-auth-service)
4. [Day 3: IP Management Service](#day-3-ip-management-service)
5. [Day 4: Gateway & Frontend Foundation](#day-4-gateway--frontend-foundation)
6. [Day 5: Frontend Completion & Testing](#day-5-frontend-completion--testing)
7. [Commit Strategy](#commit-strategy)
8. [Risk Mitigation](#risk-mitigation)
9. [Success Criteria Checklist](#success-criteria-checklist)

---

## Architecture Overview

### Service Structure

| Service | Framework | Purpose | Database |
|---------|-----------|---------|----------|
| **Gateway** | Laravel | Request routing, JWT validation | None |
| **Auth Service** | Laravel | Authentication, user management | MySQL |
| **IP Management Service** | Laravel | IP CRUD, audit logging | MySQL |
| **Frontend** | Vue 3 + TypeScript | User interface | None |

### Infrastructure

| Component | Technology |
|-----------|------------|
| Auth Database | MySQL |
| IP Database | MySQL |
| Audit Database | MySQL (separate table) |
| Session/Cache | Redis |
| Containerization | Docker + Docker Compose |

### Communication Flow

```
Vue Frontend (Port 5173)
    │
    ▼ HTTPS/JSON
Gateway Service (Port 8000)
    ├──────▶ Auth Service (Port 8001)
    └──────▶ IP Management Service (Port 8002)
```

---

## Day 1: Project Setup & Infrastructure

**Goal:** Install required packages for all services and get Docker environment running.

**⚠️ IMPORTANT:** Each task MUST be committed before proceeding to the next task. Do not start Task X+1 until Task X is committed.

### Task 1.1: Gateway Service Setup (0.5 hours)

Install required package:
```bash
cd services/gateway
composer require tymon/jwt-auth
```

**→ COMMIT:** `feat(gateway): install jwt-auth package`
```bash
git add .
git commit -m "feat(gateway): install jwt-auth package"
```

### Task 1.2: Auth Service Setup (0.5 hours)

Install required packages:
```bash
cd services/auth-service
composer require tymon/jwt-auth predis/predis spatie/laravel-permission
php artisan jwt:secret
```

**→ COMMIT:** `feat(auth): install jwt, redis, and permission packages`
```bash
git add .
git commit -m "feat(auth): install jwt, redis, and permission packages"
```

### Task 1.3: IP Management Service Setup (0.5 hours)

Install required packages:
```bash
cd services/ip-management
composer require tymon/jwt-auth spatie/laravel-activitylog spatie/laravel-permission rlanvin/php-ip
php artisan jwt:secret
```

**→ COMMIT:** `feat(ip): install jwt, activity log, permission, and php-ip packages`
```bash
git add .
git commit -m "feat(ip): install jwt, activity log, permission, and php-ip packages"
```

### Task 1.4: Frontend Setup (0.5 hours)

Install required packages:
```bash
cd frontend
npm install axios vue-toastification
```

**→ COMMIT:** `feat(frontend): install axios and toastification`
```bash
git add .
git commit -m "feat(frontend): install axios and toastification"
```

### Task 1.5: Docker Compose Configuration (1 hour)

Create [`docker-compose.yml`](docker-compose.yml) with services:
- `gateway` - Laravel (Port 8000)
- `auth-service` - Laravel (Port 8001)
- `ip-management` - Laravel (Port 8002)
- `auth-db` - MySQL for Auth Service
- `ip-db` - MySQL for IP Management Service
- `redis` - Redis for sessions and refresh tokens

Create root `.gitignore` and commit:
```bash
git add docker-compose.yml .gitignore
git commit -m "chore: add docker-compose with mysql and redis services"
```

**→ COMMIT:** `chore: add docker-compose with mysql and redis services`
```bash
git add docker-compose.yml .gitignore
git commit -m "chore: add docker-compose with mysql and redis services"
```

### Task 1.6: Database Configuration & Networking (1 hour)

**Tasks:**
- [ ] Configure MySQL connections in each service `.env`
- [ ] Update database drivers: `DB_CONNECTION=mysql`
- [ ] Set up persistent volumes in docker-compose
- [ ] Start all containers: `docker-compose up -d`
- [ ] Test inter-container networking
- [ ] Verify all containers start without errors

**→ COMMIT:** `chore: configure mysql connections and verify docker networking`
```bash
git add .
git commit -m "chore: configure mysql connections and verify docker networking"
```

---

## Day 2: Auth Service

**Goal:** Complete authentication service with JWT, roles, and tests.

### Task 2.1: User Migrations (1 hour)

Create migrations:
```php
// database/migrations/xxxx_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('email')->unique();
    $table->string('password');
    $table->enum('role', ['regular', 'super_admin'])->default('regular');
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamps();
});

// database/migrations/xxxx_create_user_sessions_table.php
Schema::create('user_sessions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained();
    $table->string('token_jti');
    $table->string('ip_address');
    $table->text('user_agent');
    $table->timestamp('last_activity');
    $table->timestamp('expires_at');
    $table->timestamps();
});
```

**→ COMMIT:** `feat(auth): create users and user_sessions migrations`
```bash
git add .
git commit -m "feat(auth): create users and user_sessions migrations"
```

### Task 2.2: User Model & JWT Configuration (1 hour)

**Tasks:**
- [ ] Create User model with UUID trait
- [ ] Configure JWT in `config/jwt.php` and `.env`
- [ ] Configure Spatie permissions in `config/permission.php`

**→ COMMIT:** `feat(auth): configure user model, jwt, and permissions`
```bash
git add .
git commit -m "feat(auth): configure user model, jwt, and permissions"
```

### Task 2.3: Authentication Endpoints (2 hours)

Create [`AuthController`](services/auth-service/app/Http/Controllers/AuthController.php) with endpoints:

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/register` | User registration |
| POST | `/api/auth/login` | JWT + refresh token issuance |
| POST | `/api/auth/logout` | Token invalidation |
| POST | `/api/auth/refresh` | Token refresh |
| GET | `/api/auth/me` | Current user info |

**Token Refresh Logic:**
```php
// Store refresh token in Redis
Redis::setex("refresh:{$token}", 604800, $userId); // 7 days

// On refresh: validate, rotate, return new tokens
```

**→ COMMIT:** `feat(auth): implement login, register, logout, and refresh endpoints`
```bash
git add .
git commit -m "feat(auth): implement login, register, logout, and refresh endpoints"
```

### Task 2.4: Role System & Seeding (1 hour)

**Tasks:**
- [ ] Create default roles using Spatie in `RolesSeeder`
- [ ] Seed initial super_admin user in `DatabaseSeeder`
- [ ] Create `app/Events/UserLoggedIn.php`
- [ ] Create `app/Events/UserLoggedOut.php`

**→ COMMIT:** `feat(auth): add roles, permissions, and database seeders`
```bash
git add .
git commit -m "feat(auth): add roles, permissions, and database seeders"
```

### Task 2.4b: Login/Logout Audit Events (0.5 hours)

Create AuditLog model and event listeners:
- [`app/Listeners/LogUserLogin.php`](services/auth-service/app/Listeners/LogUserLogin.php)
- [`app/Listeners/LogUserLogout.php`](services/auth-service/app/Listeners/LogUserLogout.php)

**Tasks:**
- [ ] Fire audit events on login/logout
- [ ] Track user session ID for session-based queries
- [ ] Store login metadata (IP, user agent, timestamp)

**→ COMMIT:** `feat(auth): implement login/logout audit logging`
```bash
git add .
git commit -m "feat(auth): implement login/logout audit logging"
```

### Task 2.5: Auth Service Tests (2 hours)

Create test files:
- [`tests/Feature/Auth/RegisterTest.php`](services/auth-service/tests/Feature/Auth/RegisterTest.php)
- [`tests/Feature/Auth/LoginTest.php`](services/auth-service/tests/Feature/Auth/LoginTest.php)
- [`tests/Feature/Auth/TokenRefreshTest.php`](services/auth-service/tests/Feature/Auth/TokenRefreshTest.php)
- [`tests/Feature/Auth/LogoutTest.php`](services/auth-service/tests/Feature/Auth/LogoutTest.php)

**→ COMMIT:** `test(auth): add feature tests for authentication endpoints`
```bash
git add .
git commit -m "test(auth): add feature tests for authentication endpoints"
```

---

## Day 3: IP Management Service

**Goal:** Complete IP management with CRUD, authorization, and audit logging.

### Task 3.1: Database Schema (1.5 hours)

Create migrations:

```php
// IP Addresses Table
Schema::create('ip_addresses', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained();
    $table->string('ip_address');
    $table->string('label');
    $table->text('comment')->nullable();
    $table->enum('type', ['ipv4', 'ipv6']);
    $table->timestamps();
    $table->softDeletes();
});

// IP History Table (Audit Log)
Schema::create('ip_history', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('ip_address_id')->constrained();
    $table->foreignUuid('modified_by')->constrained('users');
    $table->json('old_values');
    $table->json('new_values');
    $table->enum('action', ['created', 'updated', 'deleted']);
    $table->timestamps();
});

// Audit Logs Table (for user activity)
Schema::create('audit_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained();
    $table->string('event_type');
    $table->string('entity_type');
    $table->uuid('entity_id');
    $table->json('metadata');
    $table->string('session_id');
    $table->timestamps();
});
```

**→ COMMIT:** `feat(ip): create ip_addresses, ip_history, and audit_logs migrations`
```bash
git add .
git commit -m "feat(ip): create ip_addresses, ip_history, and audit_logs migrations"
```

### Task 3.2: Models & Configuration (1 hour)

**Tasks:**
- [ ] Create [`IPAddress`](services/ip-management/app/Models/IPAddress.php) model with UUID trait
- [ ] Create [`IPHistory`](services/ip-management/app/Models/IPHistory.php) model
- [ ] Create [`AuditLog`](services/ip-management/app/Models/AuditLog.php) model
- [ ] Configure Activity Log for MySQL in `config/activitylog.php`
- [ ] Configure JWT in `config/jwt.php`

**→ COMMIT:** `feat(ip): add ip models and configure activity log`
```bash
git add .
git commit -m "feat(ip): add ip models and configure activity log"
```

### Task 3.3: IP CRUD Controller (2 hours)

Create [`IPController`](services/ip-management/app/Http/Controllers/IPController.php) with endpoints:

| Method | Endpoint | Access |
|--------|----------|--------|
| GET | `/api/ip` | All users |
| POST | `/api/ip` | All users |
| PUT | `/api/ip/{id}` | Owner or super_admin |
| DELETE | `/api/ip/{id}` | super_admin only |
| GET | `/api/ip/{id}/history` | All users |

**IP Validation:**
```php
use Rlanvin\Phpip\Ip;

// Validate IPv4 or IPv6
try {
    $ip = Ip::create($request->ip_address);
    $type = $ip->getVersion(); // 4 or 6
} catch (\InvalidArgumentException $e) {
    return response()->json(['error' => 'Invalid IP address'], 400);
}
```

**→ COMMIT:** `feat(ip): implement ip crud endpoints with validation`
```bash
git add .
git commit -m "feat(ip): implement ip crud endpoints with validation"
```

### Task 3.4: Authorization Policies (1 hour)

Create [`IPAddressPolicy`](services/ip-management/app/Policies/IPAddressPolicy.php):

```php
public function update(User $user, IPAddress $ipAddress): bool
{
    return $user->id === $ipAddress->user_id || $user->hasRole('super_admin');
}

public function delete(User $user, IPAddress $ipAddress): bool
{
    return $user->hasRole('super_admin');
}

public function view(User $user, IPAddress $ipAddress): bool
{
    return true; // All users can view all IPs
}
```

**Tasks:**
- [ ] Register policy in `AuthServiceProvider`
- [ ] Apply middleware to routes in `routes/api.php`

**→ COMMIT:** `feat(ip): add authorization policies and middleware`
```bash
git add .
git commit -m "feat(ip): add authorization policies and middleware"
```

### Task 3.5: Audit Logging (1 hour)

```php
// Log all IP changes
Activity::causedBy($user)
    ->performedOn($ipAddress)
    ->withProperties(['old' => $old, 'new' => $new])
    ->log('ip.updated');
```

**Tasks:**
- [ ] Track IP changes per user session
- [ ] Track IP changes over lifetime
- [ ] Ensure no delete endpoint for audit logs

**→ COMMIT:** `feat(ip): implement audit logging with activity log`
```bash
git add .
git commit -m "feat(ip): implement audit logging with activity log"
```

### Task 3.6: IP Service Tests (1.5 hours)

Create test files:
- [`tests/Feature/IP/CreateIPTest.php`](services/ip-management/tests/Feature/IP/CreateIPTest.php)
- [`tests/Feature/IP/UpdateIPTest.php`](services/ip-management/tests/Feature/IP/UpdateIPTest.php)
- [`tests/Feature/IP/DeleteIPTest.php`](services/ip-management/tests/Feature/IP/DeleteIPTest.php)
- [`tests/Feature/IP/AuthorizationTest.php`](services/ip-management/tests/Feature/IP/AuthorizationTest.php)
- [`tests/Feature/IP/AuditLoggingTest.php`](services/ip-management/tests/Feature/IP/AuditLoggingTest.php)

**→ COMMIT:** `test(ip): add feature tests for ip crud and authorization`
```bash
git add .
git commit -m "test(ip): add feature tests for ip crud and authorization"
```

---

## Day 4: Gateway & Frontend Foundation

**Goal:** Connect services through Gateway and build frontend foundation.

### Task 4.1: Gateway JWT Middleware (1 hour)

Create [`JwtMiddleware`](services/gateway/app/Http/Middleware/JwtMiddleware.php):

```php
public function handle($request, Closure $next)
{
    try {
        $user = JWTAuth::parseToken()->authenticate();
        
        // Forward user context to backend services
        $request->headers->set('X-User-ID', $user->id);
        $request->headers->set('X-User-Role', $user->role);
        
    } catch (\Exception $e) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    return $next($request);
}
```

**→ COMMIT:** `feat(gateway): implement jwt validation middleware`
```bash
git add .
git commit -m "feat(gateway): implement jwt validation middleware"
```

### Task 4.2: Gateway Proxy Controllers (1.5 hours)

Create controllers:
- [`AuthProxyController`](services/gateway/app/Http/Controllers/AuthProxyController.php) - Proxies to auth-service
- [`IPProxyController`](services/gateway/app/Http/Controllers/IPProxyController.php) - Proxies to ip-management

**Proxy Logic:**
```php
// Forward requests with proper headers
$response = Http::withHeaders([
    'Authorization' => $request->header('Authorization'),
    'X-User-ID' => $request->header('X-User-ID'),
    'X-User-Role' => $request->header('X-User-Role'),
])->$method("http://{$service}:8000/{$path}", $request->all());
```

**→ COMMIT:** `feat(gateway): add proxy controllers for auth and ip services`
```bash
git add .
git commit -m "feat(gateway): add proxy controllers for auth and ip services"
```

### Task 4.3: Gateway Routes & CORS (1 hour)

Configure [`routes/api.php`](services/gateway/routes/api.php):

```php
Route::group(['middleware' => 'jwt'], function () {
    // Auth Service Routes
    Route::post('auth/login', [AuthProxyController::class, 'login']);
    Route::post('auth/register', [AuthProxyController::class, 'register']);
    Route::post('auth/refresh', [AuthProxyController::class, 'refresh']);
    Route::post('auth/logout', [AuthProxyController::class, 'logout']);
    Route::get('auth/me', [AuthProxyController::class, 'me']);
    
    // IP Service Routes
    Route::any('ip/{path?}', [IPProxyController::class, 'handle'])
        ->where('path', '.*');
});
```

Configure CORS in [`config/cors.php`](services/gateway/config/cors.php) for Vue frontend.

**→ COMMIT:** `feat(gateway): configure routes and cors for frontend`
```bash
git add .
git commit -m "feat(gateway): configure routes and cors for frontend"
```

### Task 4.4: Frontend Dependencies (0.5 hours)

```bash
cd frontend
npm install pinia vue-router axios vue-toastification
```

**Folder Structure:**
```
frontend/src/
├── api/              # Axios instances, interceptors
├── components/       # Reusable UI components
│   ├── common/
│   ├── forms/
│   └── tables/
├── composables/      # useAuth, useIP, useAudit
├── layouts/          # AdminLayout, AuthLayout
├── router/           # Vue Router with guards
├── stores/           # Pinia stores
│   ├── auth.ts
│   ├── ip.ts
│   └── audit.ts
├── types/            # TypeScript interfaces
│   └── index.ts
├── views/            # Pages
│   ├── auth/
│   ├── dashboard/
│   ├── ip/
│   └── audit/
└── utils/            # Validators, formatters
```

**→ COMMIT:** `feat(frontend): install pinia, router, axios, and toastification`
```bash
git add .
git commit -m "feat(frontend): install pinia, router, axios, and toastification"
```

### Task 4.5: TypeScript Types (1 hour)

Create [`types/index.ts`](frontend/src/types/index.ts):

```typescript
export interface User {
  id: string;
  email: string;
  role: 'regular' | 'super_admin';
  created_at: string;
}

export interface IPAddress {
  id: string;
  user_id: string;
  ip_address: string;
  label: string;
  comment?: string;
  type: 'ipv4' | 'ipv6';
  created_at: string;
  updated_at: string;
}

export interface IPHistory {
  id: string;
  ip_address_id: string;
  modified_by: string;
  old_values: Record<string, unknown>;
  new_values: Record<string, unknown>;
  action: 'created' | 'updated' | 'deleted';
  created_at: string;
}

export interface AuditLog {
  id: string;
  user_id: string;
  event_type: string;
  entity_type: string;
  entity_id: string;
  metadata: Record<string, unknown>;
  session_id: string;
  created_at: string;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface AuthResponse {
  access_token: string;
  refresh_token: string;
  token_type: string;
  expires_in: number;
}
```

**→ COMMIT:** `feat(frontend): add typescript interfaces for all entities`
```bash
git add .
git commit -m "feat(frontend): add typescript interfaces for all entities"
```

### Task 4.6: API Client with Interceptors (1 hour)

Create [`api/client.ts`](frontend/src/api/client.ts):

```typescript
import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor - add token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor - handle 401 & refresh
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;
    
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      
      try {
        const refreshToken = localStorage.getItem('refresh_token');
        const response = await axios.post(
          `${api.defaults.baseURL}/auth/refresh`,
          {},
          { headers: { Authorization: `Bearer ${refreshToken}` } }
        );
        
        const { access_token, refresh_token } = response.data;
        localStorage.setItem('access_token', access_token);
        localStorage.setItem('refresh_token', refresh_token);
        
        originalRequest.headers.Authorization = `Bearer ${access_token}`;
        return api(originalRequest);
      } catch (refreshError) {
        localStorage.clear();
        window.location.href = '/login';
        return Promise.reject(refreshError);
      }
    }
    
    return Promise.reject(error);
  }
);

export default api;
```

**→ COMMIT:** `feat(frontend): implement api client with auth interceptors`
```bash
git add .
git commit -m "feat(frontend): implement api client with auth interceptors"
```

### Task 4.7: Auth Store & Views (1.5 hours)

Create [`stores/auth.ts`](frontend/src/stores/auth.ts):

```typescript
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/api/client';
import type { User, LoginCredentials, AuthResponse } from '@/types';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const isAuthenticated = computed(() => !!user.value);
  const isSuperAdmin = computed(() => user.value?.role === 'super_admin');
  
  async function login(credentials: LoginCredentials): Promise<void> {
    const { data } = await api.post<AuthResponse>('/auth/login', credentials);
    localStorage.setItem('access_token', data.access_token);
    localStorage.setItem('refresh_token', data.refresh_token);
    await fetchUser();
  }
  
  async function fetchUser(): Promise<void> {
    const { data } = await api.get<User>('/auth/me');
    user.value = data;
  }
  
  async function logout(): Promise<void> {
    await api.post('/auth/logout');
    localStorage.clear();
    user.value = null;
  }
  
  return { user, isAuthenticated, isSuperAdmin, login, logout, fetchUser };
});
```

Create views:
- [`views/auth/LoginView.vue`](frontend/src/views/auth/LoginView.vue)
- [`views/auth/RegisterView.vue`](frontend/src/views/auth/RegisterView.vue)

**→ COMMIT:** `feat(frontend): add auth store, login and register views`
```bash
git add .
git commit -m "feat(frontend): add auth store, login and register views"
```

---

## Day 5: Frontend Completion & Testing

**Goal:** Complete all frontend views, testing, Docker optimization, and documentation.

### Task 5.1: IP Store (1 hour)

Create [`stores/ip.ts`](frontend/src/stores/ip.ts):

```typescript
import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/api/client';
import type { IPAddress, IPHistory } from '@/types';

export const useIPStore = defineStore('ip', () => {
  const ips = ref<IPAddress[]>([]);
  const currentIP = ref<IPAddress | null>(null);
  const history = ref<IPHistory[]>([]);
  
  async function fetchIPs(): Promise<void> {
    const { data } = await api.get<IPAddress[]>('/ip');
    ips.value = data;
  }
  
  async function createIP(ipData: Partial<IPAddress>): Promise<void> {
    await api.post('/ip', ipData);
    await fetchIPs();
  }
  
  async function updateIP(id: string, label: string): Promise<void> {
    await api.put(`/ip/${id}`, { label });
    await fetchIPs();
  }
  
  async function deleteIP(id: string): Promise<void> {
    await api.delete(`/ip/${id}`);
    await fetchIPs();
  }
  
  async function fetchHistory(id: string): Promise<void> {
    const { data } = await api.get<IPHistory[]>(`/ip/${id}/history`);
    history.value = data;
  }
  
  return { ips, currentIP, history, fetchIPs, createIP, updateIP, deleteIP, fetchHistory };
});
```

**→ COMMIT:** `feat(frontend): add ip management pinia store`
```bash
git add .
git commit -m "feat(frontend): add ip management pinia store"
```

### Task 5.2: IP Management Views (2 hours)

Create components:
- [`views/ip/IPListView.vue`](frontend/src/views/ip/IPListView.vue) - Table with pagination
- [`components/forms/AddIPForm.vue`](frontend/src/components/forms/AddIPForm.vue) - Create IP modal
- [`components/forms/EditIPForm.vue`](frontend/src/components/forms/EditIPForm.vue) - Edit label modal
- [`views/ip/IPHistoryView.vue`](frontend/src/views/ip/IPHistoryView.vue) - Change history

**→ COMMIT:** `feat(frontend): add ip list, forms, and history views`
```bash
git add .
git commit -m "feat(frontend): add ip list, forms, and history views"
```

### Task 5.3: Audit Dashboard (1 hour)

Create [`stores/audit.ts`](frontend/src/stores/audit.ts):

```typescript
import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/api/client';
import type { AuditLog } from '@/types';

export const useAuditStore = defineStore('audit', () => {
  const logs = ref<AuditLog[]>([]);
  
  async function fetchAllLogs(): Promise<void> {
    const { data } = await api.get<AuditLog[]>('/audit/logs');
    logs.value = data;
  }
  
  return { logs, fetchAllLogs };
});
```

Create [`views/audit/AuditDashboardView.vue`](frontend/src/views/audit/AuditDashboardView.vue) with super_admin route guard.

**→ COMMIT:** `feat(frontend): add audit dashboard with super admin guard`
```bash
git add .
git commit -m "feat(frontend): add audit dashboard with super admin guard"
```

### Task 5.4: UI/UX Polish (1 hour)

**Tasks:**
- [ ] Add responsive design with Tailwind CSS
- [ ] Add loading spinners on async operations
- [ ] Add toast notifications for success/error
- [ ] Add form validation with error messages
- [ ] Add empty states for lists
- [ ] Add confirmation dialogs for delete actions

**→ COMMIT:** `style(frontend): add responsive design, loading states, and notifications`
```bash
git add .
git commit -m "style(frontend): add responsive design, loading states, and notifications"
```

### Task 5.5: Frontend Tests (1 hour)

```bash
npm install -D vitest @vue/test-utils playwright
```

**Tests:**
- [ ] Component tests for forms
- [ ] Store tests (Pinia)
- [ ] E2E tests for critical flows:
  - Login → Dashboard → Logout
  - Create IP → Edit IP → View History

**→ COMMIT:** `test(frontend): add component, store, and e2e tests`
```bash
git add .
git commit -m "test(frontend): add component, store, and e2e tests"
```

### Task 5.6: Docker Optimization (1 hour)

Create optimized Dockerfiles:

```dockerfile
# services/auth-service/Dockerfile
FROM php:8.2-fpm-alpine AS base

# Install extensions
RUN docker-php-ext-install pdo pdo_mysql

# Composer stage
FROM base AS composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader

# Production stage
FROM base AS production
WORKDIR /var/www
COPY --from=composer /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "port=8000"]
```

**Tasks:**
- [ ] Optimize all service Dockerfiles with multi-stage builds
- [ ] Add health checks for containers
- [ ] Configure production environment variables

**→ COMMIT:** `chore: optimize dockerfiles with multi-stage builds and health checks`
```bash
git add .
git commit -m "chore: optimize dockerfiles with multi-stage builds and health checks"
```

### Task 5.7: Documentation (0.5 hours)

Update [`README.md`](README.md) with:

1. **Project Overview** - Architecture diagram, tech stack summary
2. **Prerequisites** - Docker & Docker Compose, Node.js, Composer
3. **Installation Instructions:**
   ```bash
   git clone <repo>
   cd ip-management-system
   cp .env.example .env
   docker-compose up -d
   docker exec -it auth-service php artisan migrate --seed
   docker exec -it ip-service php artisan migrate
   ```
4. **API Documentation** - Authentication, IP Management, Audit endpoints
5. **Development Guide** - Running tests, code style, Git workflow

**→ COMMIT:** `docs: add comprehensive readme with installation and api docs`
```bash
git add .
git commit -m "docs: add comprehensive readme with installation and api docs"
```

### Task 5.8: Final Review (0.5 hours)

**Final Checklist:**
- [ ] Code review and cleanup
- [ ] Verify Git commit history (20+ meaningful commits)
- [ ] Run full E2E test suite
- [ ] Verify all requirements are met
- [ ] Test Docker setup on clean environment
- [ ] Push to public GitHub/GitLab repository

**→ COMMIT:** `chore: final review and submission`
```bash
git add .
git commit -m "chore: final review and submission"
```

---

## Commit Strategy

### Day 1 Commits
| Task | Commit Message |
|------|---------------|
| 1.1 | `feat(gateway): install jwt-auth package` |
| 1.2 | `feat(auth): install jwt, redis, and permission packages` |
| 1.3 | `feat(ip): install jwt, activity log, permission, and php-ip packages` |
| 1.4 | `feat(frontend): install axios and toastification` |
| 1.5 | `chore: add docker-compose with mysql and redis services` |
| 1.6 | `chore: configure mysql connections and verify docker networking` |

### Day 2 Commits
| Task | Commit Message |
|------|---------------|
| 2.1 | `feat(auth): create users and user_sessions migrations` |
| 2.2 | `feat(auth): configure user model, jwt, and permissions` |
| 2.3 | `feat(auth): implement login, register, logout, and refresh endpoints` |
| 2.4 | `feat(auth): add roles, permissions, and database seeders` |
| 2.4b | `feat(auth): implement login/logout audit logging` |
| 2.5 | `test(auth): add feature tests for authentication endpoints` |

### Day 3 Commits
| Task | Commit Message |
|------|---------------|
| 3.1 | `feat(ip): create ip_addresses, ip_history, and audit_logs migrations` |
| 3.2 | `feat(ip): add ip models and configure activity log` |
| 3.3 | `feat(ip): implement ip crud endpoints with validation` |
| 3.4 | `feat(ip): add authorization policies and middleware` |
| 3.5 | `feat(ip): implement audit logging with activity log` |
| 3.6 | `test(ip): add feature tests for ip crud and authorization` |

### Day 4 Commits
| Task | Commit Message |
|------|---------------|
| 4.1 | `feat(gateway): implement jwt validation middleware` |
| 4.2 | `feat(gateway): add proxy controllers for auth and ip services` |
| 4.3 | `feat(gateway): configure routes and cors for frontend` |
| 4.4 | `feat(frontend): install pinia, router, axios, and toastification` |
| 4.5 | `feat(frontend): add typescript interfaces for all entities` |
| 4.6 | `feat(frontend): implement api client with auth interceptors` |
| 4.7 | `feat(frontend): add auth store, login and register views` |

### Day 5 Commits
| Task | Commit Message |
|------|---------------|
| 5.1 | `feat(frontend): add ip management pinia store` |
| 5.2 | `feat(frontend): add ip list, forms, and history views` |
| 5.3 | `feat(frontend): add audit dashboard with super admin guard` |
| 5.4 | `style(frontend): add responsive design, loading states, and notifications` |
| 5.5 | `test(frontend): add component, store, and e2e tests` |
| 5.6 | `chore: optimize dockerfiles with multi-stage builds and health checks` |
| 5.7 | `docs: add comprehensive readme with installation and api docs` |
| 5.8 | `chore: final review and submission` |

---

## Risk Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Aggressive timeline | High | Focus on core CRUD + Auth, minimize polish features |
| JWT refresh complexity | High | Use proven library, test early Day 2 |
| Docker networking issues | Medium | Verify connectivity Day 1 end |
| Scope creep | High | Strictly prioritize MVP features |
| Audit log implementation | Medium | Use Spatie Activity Log (battle-tested) |
| CORS issues | Medium | Configure CORS early in Gateway Day 4 |

**Simplifications for 5-Day Timeline:**
1. Skip advanced UI features (dark mode, animations)
2. Minimal test coverage - focus on critical paths
3. Use default Laravel/Vue styling where possible
4. Skip complex deployment configurations
5. Focus on functional requirements over polish

---

## Success Criteria Checklist

### Functional Requirements
- [ ] 3 separate microservices (Gateway, Auth, IP) - all using Laravel
- [ ] Vue frontend communicates only through Gateway
- [ ] JWT authentication with automatic refresh
- [ ] Role-based access (regular vs super_admin)
- [ ] IP CRUD with IPv4/IPv6 validation
- [ ] Only owners can edit their IPs
- [ ] Only super_admin can delete IPs
- [ ] All users can view all IPs
- [ ] Immutable audit log (no delete endpoint)
- [ ] Audit dashboard accessible only by super_admin
- [ ] Audit log tracks login/logout events
- [ ] Audit log tracks IP changes within user session and over lifetime
- [ ] Audit log tracks user changes within session and over lifetime

### Technical Requirements
- [ ] Each service has independent database
- [ ] Frontend strictly uses TypeScript
- [ ] All services dockerized
- [ ] Docker Compose for orchestration
- [ ] README with installation instructions
- [ ] Public Git repository
- [ ] Multiple Git commits showing process (20+ commits)

### Bonus (If Time Permits)
- [ ] Unit tests for backend
- [ ] E2E tests for frontend
- [ ] Clean, intuitive UI/UX
- [ ] Well-designed architecture

---

## Package Summary

### All Laravel Services (Gateway, Auth, IP Management)
```bash
composer require \
  tymon/jwt-auth \
  spatie/laravel-permission

# Auth Service only:
composer require predis/predis

# IP Management only:
composer require spatie/laravel-activitylog rlanvin/php-ip
```

### Frontend
```bash
npm install \
  axios \
  vue-toastification \
  pinia \
  vue-router
```

