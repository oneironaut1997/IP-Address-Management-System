# IP Management System - Module Implementation Plan

## Module: IP Address Management System (Full Application)

**Document Version:** 1.0  
**Date:** 2026-02-01  
**Author:** Architect  
**Status:** Ready for Technical Design

---

## 1. Module Overview

### 1.1 Purpose
This document breaks down the IP Address Management System implementation into actionable, granular tasks with clear dependencies, acceptance criteria, and risk assessments. It serves as the master implementation guide for the 5-day development sprint.

### 1.2 Scope
This module encompasses:
- **Gateway Service**: API entry point, JWT validation, request routing
- **Auth Service**: User authentication, role management, session tracking
- **IP Management Service**: IP CRUD operations, audit logging, authorization
- **Frontend Application**: Vue 3 + TypeScript UI with full feature set

### 1.3 Related Documents
- [Project Scope](../project_scope.md)
- [Risk Register](../risk_register.md)
- [Communication Plan](../communication_plan.md)
- [Implementation Plan](../implementation_plan.md)
- [Architecture Overview](../architecture_overview.md) (Created by designer-t1)

---

## 2. Phases and Tasks

### Phase 1: Infrastructure & Setup (Day 1)

**Phase Goal:** Install required packages for all services and get Docker environment running.

**Risk Assessment:** Medium - Docker networking could cause delays

---

#### Task 1.1: Gateway Service Package Installation

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-1.1 |
| **Description** | Install JWT authentication package in Gateway service |
| **Inputs** | Existing Laravel scaffolding in `services/gateway/` |
| **Outputs** | tymon/jwt-auth package installed and configured |
| **DoD** | - `composer require tymon/jwt-auth` executed successfully<br>- Package appears in composer.json<br>- No dependency conflicts |
| **Estimated Time** | 0.5 hours |
| **Dependencies** | None |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Low - Standard package installation |
| **Commit Message** | `feat(gateway): install jwt-auth package` |

**Implementation Steps:**
1. Navigate to `services/gateway`
2. Execute `composer require tymon/jwt-auth`
3. Verify installation in composer.json
4. Commit changes

---

#### Task 1.2: Auth Service Package Installation

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-1.2 |
| **Description** | Install JWT, Redis, and permission packages in Auth service |
| **Inputs** | Existing Laravel scaffolding in `services/auth-service/` |
| **Outputs** | tymon/jwt-auth, predis/predis, spatie/laravel-permission installed |
| **DoD** | - All packages installed successfully<br>- JWT secret generated<br>- Redis client configured |
| **Estimated Time** | 0.5 hours |
| **Dependencies** | IPMS-1.1 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Low - Standard package installation |
| **Commit Message** | `feat(auth): install jwt, redis, and permission packages` |

**Implementation Steps:**
1. Navigate to `services/auth-service`
2. Execute `composer require tymon/jwt-auth predis/predis spatie/laravel-permission`
3. Execute `php artisan jwt:secret`
4. Commit changes

---

#### Task 1.3: IP Management Service Package Installation

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-1.3 |
| **Description** | Install JWT, activity log, permission, and IP validation packages |
| **Inputs** | Existing Laravel scaffolding in `services/ip-management/` |
| **Outputs** | All required packages installed and configured |
| **DoD** | - tymon/jwt-auth installed<br>- spatie/laravel-activitylog installed<br>- spatie/laravel-permission installed<br>- rlanvin/php-ip installed<br>- JWT secret generated |
| **Estimated Time** | 0.5 hours |
| **Dependencies** | IPMS-1.2 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Low - Standard package installation |
| **Commit Message** | `feat(ip): install jwt, activity log, permission, and php-ip packages` |

**Implementation Steps:**
1. Navigate to `services/ip-management`
2. Execute `composer require tymon/jwt-auth spatie/laravel-activitylog spatie/laravel-permission rlanvin/php-ip`
3. Execute `php artisan jwt:secret`
4. Commit changes

---

#### Task 1.4: Frontend Package Installation

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-1.4 |
| **Description** | Install Axios and Vue Toastification packages |
| **Inputs** | Existing Vue scaffolding in `frontend/` |
| **Outputs** | axios and vue-toastification packages installed |
| **DoD** | - Both packages in package.json<br>- No dependency conflicts<br>- Node modules installed |
| **Estimated Time** | 0.5 hours |
| **Dependencies** | IPMS-1.3 |
| **Suggested Assignee** | code (TypeScript/Vue) |
| **Risk** | Low - Standard npm installation |
| **Commit Message** | `feat(frontend): install axios and toastification` |

**Implementation Steps:**
1. Navigate to `frontend`
2. Execute `npm install axios vue-toastification`
3. Verify installation in package.json
4. Commit changes

---

#### Task 1.5: Docker Compose Configuration

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-1.5 |
| **Description** | Create docker-compose.yml with all services, databases, and Redis |
| **Inputs** | Project structure, existing Laravel services |
| **Outputs** | Working docker-compose.yml file |
| **DoD** | - All 6 services defined (gateway, auth-service, ip-management, auth-db, ip-db, redis)<br>- Proper port mappings configured<br>- Service dependencies defined<br>- Persistent volumes for databases<br>- Root .gitignore created |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-1.4 |
| **Suggested Assignee** | devops-engineer-t2 / code |
| **Risk** | Medium - Docker networking configuration |
| **Commit Message** | `chore: add docker-compose with mysql and redis services` |
| **Status** | ✅ Completed |

**Implementation Steps:**
1. Create `docker-compose.yml` at project root
2. Define all services with appropriate ports
3. Configure MySQL and Redis services
4. Set up volume persistence
5. Create root `.gitignore`
6. Commit changes

---

#### Task 1.6: Database Configuration & Networking

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-1.6 |
| **Description** | Configure MySQL connections and verify Docker networking |
| **Inputs** | docker-compose.yml, service .env.example files |
| **Outputs** | Configured .env files, running containers |
| **DoD** | - MySQL connections configured in all services<br>- Database drivers set to mysql<br>- Containers start without errors<br>- Inter-container networking tested<br>- Redis connectivity verified |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-1.5 |
| **Suggested Assignee** | devops-engineer-t2 / code |
| **Risk** | High - RISK-003: Docker networking issues |
| **Commit Message** | `chore: configure mysql connections and verify docker networking` |
| **Status** | ✅ Completed |

**Implementation Steps:**
1. Update `.env` files in each service with MySQL config
2. Set `DB_CONNECTION=mysql`
3. Run `docker-compose up -d`
4. Verify all containers are running
5. Test connectivity between services
6. Commit environment configuration

---

### Phase 2: Auth Service Implementation (Day 2)

**Phase Goal:** Complete authentication service with JWT, roles, and tests.

**Risk Assessment:** High - JWT refresh complexity

---

#### Task 2.1: User Migrations

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-2.1 |
| **Description** | Create users and user_sessions table migrations |
| **Inputs** | Auth service database connection |
| **Outputs** | Migration files for users and user_sessions |
| **DoD** | - users table with UUID primary key<br>- email, password, role fields<br>- user_sessions table with token tracking<br>- Foreign key relationships defined<br>- Migrations run successfully |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-1.6 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Low - Standard migration creation |
| **Commit Message** | `feat(auth): create users and user_sessions migrations` |

**Schema Requirements:**
```php
// users table
- id: uuid (primary)
- email: string (unique)
- password: string
- role: enum ['regular', 'super_admin']
- email_verified_at: timestamp (nullable)
- timestamps

// user_sessions table
- id: uuid (primary)
- user_id: foreign_uuid
- token_jti: string
- ip_address: string
- user_agent: text
- last_activity: timestamp
- expires_at: timestamp
- timestamps
```

---

#### Task 2.2: User Model & JWT Configuration

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-2.2 |
| **Description** | Create User model with UUID trait and configure JWT |
| **Inputs** | Migrations from IPMS-2.1 |
| **Outputs** | User model, JWT config, permission config |
| **DoD** | - User model uses UUID trait<br>- JWT configured in config/jwt.php<br>- Spatie permissions configured<br>- Model implements JWTSubject |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-2.1 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Medium - JWT configuration can be complex |
| **Commit Message** | `feat(auth): configure user model, jwt, and permissions` |

---

#### Task 2.3: Authentication Endpoints

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-2.3 |
| **Description** | Implement login, register, logout, refresh, and me endpoints |
| **Inputs** | User model, JWT configuration |
| **Outputs** | AuthController with all endpoints |
| **DoD** | - POST /api/auth/register working<br>- POST /api/auth/login returns JWT + refresh token<br>- POST /api/auth/logout invalidates tokens<br>- POST /api/auth/refresh rotates tokens<br>- GET /api/auth/me returns user info<br>- Redis used for refresh token storage |
| **Estimated Time** | 2 hours |
| **Dependencies** | IPMS-2.2 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | High - RISK-002: JWT refresh token complexity |
| **Commit Message** | `feat(auth): implement login, register, logout, and refresh endpoints` |

**Token Logic:**
```php
// Store refresh token in Redis
Redis::setex("refresh:{$token}", 604800, $userId); // 7 days

// On refresh: validate, rotate, return new tokens
```

---

#### Task 2.4: Role System & Seeding

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-2.4 |
| **Description** | Create roles, permissions, and database seeders |
| **Inputs** | Auth endpoints from IPMS-2.3 |
| **Outputs** | Roles, seeders, and events |
| **DoD** | - Spatie roles configured<br>- RolesSeeder with regular and super_admin<br>- DatabaseSeeder with initial super_admin user<br>- UserLoggedIn event created<br>- UserLoggedOut event created |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-2.3 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Low - Standard Spatie setup |
| **Commit Message** | `feat(auth): add roles, permissions, and database seeders` |

---

#### Task 2.5: Login/Logout Audit Events

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-2.5 |
| **Description** | Implement audit logging for login/logout events |
| **Inputs** | Events from IPMS-2.4 |
| **Outputs** | Audit log listeners and tracking |
| **DoD** | - LogUserLogin listener created<br>- LogUserLogout listener created<br>- Events fire on authentication<br>- Session ID tracked<br>- Login metadata stored (IP, user agent, timestamp) |
| **Estimated Time** | 0.5 hours |
| **Dependencies** | IPMS-2.4 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Low - Event-driven pattern |
| **Commit Message** | `feat(auth): implement login/logout audit logging` |

---

#### Task 2.6: Auth Service Tests

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-2.6 |
| **Description** | Create feature tests for authentication endpoints |
| **Inputs** | All auth endpoints from IPMS-2.3-2.5 |
| **Outputs** | Test suite for auth features |
| **DoD** | - RegisterTest with validation tests<br>- LoginTest with credential tests<br>- TokenRefreshTest with rotation tests<br>- LogoutTest with invalidation tests<br>- All tests passing |
| **Estimated Time** | 2 hours |
| **Dependencies** | IPMS-2.5 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Medium - Test setup can be time-consuming |
| **Commit Message** | `test(auth): add feature tests for authentication endpoints` |

---

### Phase 3: IP Management Service Implementation (Day 3)

**Phase Goal:** Complete IP management with CRUD, authorization, and audit logging.

**Risk Assessment:** Medium - Audit log implementation

---

#### Task 3.1: Database Schema

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-3.1 |
| **Description** | Create IP addresses, history, and audit logs migrations |
| **Inputs** | IP service database connection |
| **Outputs** | Migration files for IP tables |
| **DoD** | - ip_addresses table with UUID and soft deletes<br>- ip_history table for audit trail<br>- audit_logs table for user activity<br>- All foreign keys configured<br>- Migrations run successfully |
| **Estimated Time** | 1.5 hours |
| **Dependencies** | IPMS-1.6 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Low - Schema defined in implementation plan |
| **Commit Message** | `feat(ip): create ip_addresses, ip_history, and audit_logs migrations` |

**Schema Requirements:**
```php
// ip_addresses
- id: uuid (primary)
- user_id: foreign_uuid
- ip_address: string
- label: string
- comment: text (nullable)
- type: enum ['ipv4', 'ipv6']
- timestamps
- softDeletes

// ip_history
- id: uuid (primary)
- ip_address_id: foreign_uuid
- modified_by: foreign_uuid (users)
- old_values: json
- new_values: json
- action: enum ['created', 'updated', 'deleted']
- timestamps
```

---

#### Task 3.2: Models & Configuration

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-3.2 |
| **Description** | Create IP models and configure Activity Log |
| **Inputs** | Migrations from IPMS-3.1 |
| **Outputs** | IPAddress, IPHistory, AuditLog models |
| **DoD** | - IPAddress model with UUID trait<br>- IPHistory model with relationships<br>- AuditLog model<br>- Activity Log configured for MySQL<br>- JWT configured in config/jwt.php |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-3.1 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Low - Model creation |
| **Commit Message** | `feat(ip): add ip models and configure activity log` |

---

#### Task 3.3: IP CRUD Controller

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-3.3 |
| **Description** | Implement IP management CRUD endpoints with validation |
| **Inputs** | Models from IPMS-3.2 |
| **Outputs** | IPController with all endpoints |
| **DoD** | - GET /api/ip lists all IPs<br>- POST /api/ip creates IP with validation<br>- PUT /api/ip/{id} updates IP label<br>- DELETE /api/ip/{id} deletes IP<br>- GET /api/ip/{id}/history shows changes<br>- IPv4/IPv6 validation using php-ip |
| **Estimated Time** | 2 hours |
| **Dependencies** | IPMS-3.2 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Medium - Validation logic |
| **Commit Message** | `feat(ip): implement ip crud endpoints with validation` |

**Validation Logic:**
```php
use Rlanvin\Phpip\Ip;

try {
    $ip = Ip::create($request->ip_address);
    $type = $ip->getVersion(); // 4 or 6
} catch (\InvalidArgumentException $e) {
    return response()->json(['error' => 'Invalid IP address'], 400);
}
```

---

#### Task 3.4: Authorization Policies

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-3.4 |
| **Description** | Create and register IP address authorization policies |
| **Inputs** | IPController from IPMS-3.3 |
| **Outputs** | IPAddressPolicy with rules |
| **DoD** | - IPAddressPolicy created<br>- update() allows owner or super_admin<br>- delete() allows super_admin only<br>- view() allows all authenticated users<br>- Policy registered in AuthServiceProvider<br>- Middleware applied to routes |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-3.3 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Low - Policy-based authorization |
| **Commit Message** | `feat(ip): add authorization policies and middleware` |

**Policy Rules:**
```php
public function update(User $user, IPAddress $ipAddress): bool
{
    return $user->id === $ipAddress->user_id || $user->hasRole('super_admin');
}

public function delete(User $user, IPAddress $ipAddress): bool
{
    return $user->hasRole('super_admin');
}
```

---

#### Task 3.5: Audit Logging

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-3.5 |
| **Description** | Implement comprehensive audit logging for all IP changes |
| **Inputs** | Policies from IPMS-3.4 |
| **Outputs** | Audit logging integration |
| **DoD** | - Track IP changes per user session<br>- Track IP changes over lifetime<br>- Use Activity Log for change tracking<br>- No delete endpoint for audit logs<br>- All CRUD operations logged |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-3.4 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Medium - RISK-005: Audit log complexity |
| **Commit Message** | `feat(ip): implement audit logging with activity log` |

---

#### Task 3.6: IP Service Tests

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-3.6 |
| **Description** | Create feature tests for IP CRUD, authorization, and audit |
| **Inputs** | All IP service features from IPMS-3.1-3.5 |
| **Outputs** | Complete test suite |
| **DoD** | - CreateIPTest with validation<br>- UpdateIPTest with authorization<br>- DeleteIPTest with super_admin requirement<br>- AuthorizationTest for all policy rules<br>- AuditLoggingTest for change tracking<br>- All tests passing |
| **Estimated Time** | 1.5 hours |
| **Dependencies** | IPMS-3.5 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Medium - Test coverage requirements |
| **Commit Message** | `test(ip): add feature tests for ip crud and authorization` |

---

### Phase 4: Gateway & Frontend Foundation (Day 4)

**Phase Goal:** Connect services through Gateway and build frontend foundation.

**Risk Assessment:** High - CORS, service integration

---

#### Task 4.1: Gateway JWT Middleware

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-4.1 |
| **Description** | Create JWT validation middleware for Gateway |
| **Inputs** | Gateway service, JWT configuration |
| **Outputs** | JwtMiddleware class |
| **DoD** | - JwtMiddleware validates tokens<br>- User context forwarded (X-User-ID, X-User-Role)<br>- Returns 401 for invalid tokens<br>- Integrated into Kernel |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-2.2 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Medium - Middleware integration |
| **Commit Message** | `feat(gateway): implement jwt validation middleware` |

---

#### Task 4.2: Gateway Proxy Controllers

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-4.2 |
| **Description** | Create proxy controllers for auth and IP services |
| **Inputs** | JwtMiddleware from IPMS-4.1 |
| **Outputs** | AuthProxyController and IPProxyController |
| **DoD** | - AuthProxyController routes to auth-service<br>- IPProxyController routes to ip-management<br>- Headers forwarded (Authorization, X-User-ID, X-User-Role)<br>- Error handling for service unavailability |
| **Estimated Time** | 1.5 hours |
| **Dependencies** | IPMS-4.1 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Medium - Service communication |
| **Commit Message** | `feat(gateway): add proxy controllers for auth and ip services` |

---

#### Task 4.3: Gateway Routes & CORS

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-4.3 |
| **Description** | Configure API routes and CORS for frontend communication |
| **Inputs** | Proxy controllers from IPMS-4.2 |
| **Outputs** | routes/api.php, config/cors.php |
| **DoD** | - All auth routes proxied<br>- All IP routes proxied<br>- JWT middleware applied<br>- CORS configured for Vue frontend<br>- Wildcard catch-all route for IP service |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-4.2 |
| **Suggested Assignee** | code (Laravel/PHP) |
| **Risk** | Medium - RISK-006: CORS configuration |
| **Commit Message** | `feat(gateway): configure routes and cors for frontend` |

---

#### Task 4.4: Frontend Dependencies

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-4.4 |
| **Description** | Install remaining frontend dependencies |
| **Inputs** | Existing Vue scaffolding |
| **Outputs** | Complete dependency set |
| **DoD** | - pinia installed<br>- vue-router installed<br>- Folder structure created (api, components, composables, layouts, stores, types, views)<br>- No conflicts |
| **Estimated Time** | 0.5 hours |
| **Dependencies** | IPMS-1.4 |
| **Suggested Assignee** | code (TypeScript/Vue) |
| **Risk** | Low - NPM installations |
| **Commit Message** | `feat(frontend): install pinia, router, axios, and toastification` |

---

#### Task 4.5: TypeScript Types

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-4.5 |
| **Description** | Create TypeScript interfaces for all entities |
| **Inputs** | API documentation |
| **Outputs** | types/index.ts |
| **DoD** | - User interface defined<br>- IPAddress interface defined<br>- IPHistory interface defined<br>- AuditLog interface defined<br>- LoginCredentials interface defined<br>- AuthResponse interface defined |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-4.4 |
| **Suggested Assignee** | code (TypeScript) |
| **Risk** | Low - Type definitions |
| **Commit Message** | `feat(frontend): add typescript interfaces for all entities` |

---

#### Task 4.6: API Client with Interceptors

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-4.6 |
| **Description** | Create axios client with authentication interceptors |
| **Inputs** | Types from IPMS-4.5 |
| **Outputs** | api/client.ts |
| **DoD** | - Axios instance with baseURL<br>- Request interceptor adds auth header<br>- Response interceptor handles 401<br>- Token refresh logic implemented<br>- Redirect to login on refresh failure |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-4.5 |
| **Suggested Assignee** | code (TypeScript) |
| **Risk** | High - RISK-002: Token refresh complexity |
| **Commit Message** | `feat(frontend): implement api client with auth interceptors` |

---

#### Task 4.7: Auth Store & Views

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-4.7 |
| **Description** | Create auth store, login view, and register view |
| **Inputs** | API client from IPMS-4.6 |
| **Outputs** | stores/auth.ts, LoginView.vue, RegisterView.vue |
| **DoD** | - Auth store with login/logout/fetchUser<br>- isAuthenticated and isSuperAdmin computed<br>- LoginView with form<br>- RegisterView with form<br>- Form validation |
| **Estimated Time** | 1.5 hours |
| **Dependencies** | IPMS-4.6 |
| **Suggested Assignee** | code (TypeScript/Vue) |
| **Risk** | Medium - Form handling and validation |
| **Commit Message** | `feat(frontend): add auth store, login and register views` |

---

### Phase 5: Frontend Completion & Testing (Day 5)

**Phase Goal:** Complete all frontend views, testing, Docker optimization, and documentation.

**Risk Assessment:** Medium - Time constraints

---

#### Task 5.1: IP Store

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-5.1 |
| **Description** | Create IP management Pinia store |
| **Inputs** | Types and API client |
| **Outputs** | stores/ip.ts |
| **DoD** | - ips ref for IP list<br>- currentIP ref for selected IP<br>- history ref for audit trail<br>- fetchIPs, createIP, updateIP, deleteIP methods<br>- fetchHistory method<br>- Error handling |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-4.7 |
| **Suggested Assignee** | code (TypeScript/Vue) |
| **Risk** | Low - Store pattern |
| **Commit Message** | `feat(frontend): add ip management pinia store` |

---

#### Task 5.2: IP Management Views

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-5.2 |
| **Description** | Create IP list, forms, and history views |
| **Inputs** | IP store from IPMS-5.1 |
| **Outputs** | IPListView, AddIPForm, EditIPForm, IPHistoryView |
| **DoD** | - IPListView with table and pagination<br>- AddIPForm modal for creation<br>- EditIPForm modal for editing<br>- IPHistoryView showing changes<br>- Delete confirmation dialog |
| **Estimated Time** | 2 hours |
| **Dependencies** | IPMS-5.1 |
| **Suggested Assignee** | code (TypeScript/Vue) |
| **Risk** | Medium - UI complexity |
| **Commit Message** | `feat(frontend): add ip list, forms, and history views` |

---

#### Task 5.3: Audit Dashboard

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-5.3 |
| **Description** | Create audit store and super_admin dashboard |
| **Inputs** | Auth store for role checking |
| **Outputs** | stores/audit.ts, AuditDashboardView.vue |
| **DoD** | - Audit store with fetchAllLogs<br>- AuditDashboardView with table<br>- Super admin route guard<br>- Complete audit trail visibility |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-5.2 |
| **Suggested Assignee** | code (TypeScript/Vue) |
| **Risk** | Low - Similar to IP views |
| **Commit Message** | `feat(frontend): add audit dashboard with super admin guard` |

---

#### Task 5.4: UI/UX Polish

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-5.4 |
| **Description** | Add responsive design, loading states, and notifications |
| **Inputs** | All frontend views |
| **Outputs** | Polished UI with feedback |
| **DoD** | - Responsive design with Tailwind<br>- Loading spinners on async ops<br>- Toast notifications (success/error)<br>- Form validation messages<br>- Empty states<br>- Confirmation dialogs |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-5.3 |
| **Suggested Assignee** | code (TypeScript/Vue) |
| **Risk** | Low - Polishing work |
| **Commit Message** | `style(frontend): add responsive design, loading states, and notifications` |

---

#### Task 5.5: Frontend Tests

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-5.5 |
| **Description** | Add component, store, and E2E tests |
| **Inputs** | Complete frontend application |
| **Outputs** | Test suites |
| **DoD** | - vitest, @vue/test-utils, playwright installed<br>- Component tests for forms<br>- Store tests (Pinia)<br>- E2E tests for critical flows<br>- All tests passing |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-5.4 |
| **Suggested Assignee** | code (TypeScript) |
| **Risk** | Medium - Test setup time |
| **Commit Message** | `test(frontend): add component, store, and e2e tests` |

---

#### Task 5.6: Docker Optimization

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-5.6 |
| **Description** | Optimize Dockerfiles with multi-stage builds |
| **Inputs** | All services |
| **Outputs** | Optimized Dockerfiles |
| **DoD** | - Multi-stage builds for all services<br>- Health checks for containers<br>- Production environment variables<br>- Smaller image sizes<br>- Faster builds |
| **Estimated Time** | 1 hour |
| **Dependencies** | IPMS-5.5 |
| **Suggested Assignee** | devops-engineer-t2 |
| **Risk** | Low - Dockerfile optimization |
| **Commit Message** | `chore: optimize dockerfiles with multi-stage builds and health checks` |

---

#### Task 5.7: Documentation

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-5.7 |
| **Description** | Create comprehensive README with installation and API docs |
| **Inputs** | Complete system |
| **Outputs** | README.md |
| **DoD** | - Project overview section<br>- Prerequisites listed<br>- Installation instructions<br>- API documentation<br>- Development guide<br>- Working on clean machine |
| **Estimated Time** | 0.5 hours |
| **Dependencies** | IPMS-5.6 |
| **Suggested Assignee** | documentation-writer-t2 |
| **Risk** | Low - Documentation |
| **Commit Message** | `docs: add comprehensive readme with installation and api docs` |

---

#### Task 5.8: Final Review

| Field | Details |
|-------|---------|
| **Task ID** | IPMS-5.8 |
| **Description** | Final code review and submission preparation |
| **Inputs** | All deliverables |
| **Outputs** | Submission-ready repository |
| **DoD** | - Code review completed<br>- 20+ meaningful commits verified<br>- Full E2E test suite run<br>- All requirements checklist confirmed<br>- Docker tested on clean environment<br>- Ready for public repository |
| **Estimated Time** | 0.5 hours |
| **Dependencies** | IPMS-5.7 |
| **Suggested Assignee** | quality-assurance-gate |
| **Risk** | Medium - RISK-001: Timeline pressure |
| **Commit Message** | `chore: final review and submission` |

---

## 3. Dependency Graph

```
Day 1: Infrastructure
├─ IPMS-1.1 (Gateway packages)
│  └─ IPMS-1.2 (Auth packages)
│     └─ IPMS-1.3 (IP packages)
│        └─ IPMS-1.4 (Frontend packages)
│           └─ IPMS-1.5 (Docker Compose)
│              └─ IPMS-1.6 (DB Config & Networking)
│                 ├─ IPMS-2.1 (Auth Migrations) ── Day 2
│                 └─ IPMS-3.1 (IP Migrations) ──── Day 3

Day 2: Auth Service
├─ IPMS-2.1 (Migrations)
│  └─ IPMS-2.2 (Models & JWT Config)
│     └─ IPMS-2.3 (Auth Endpoints)
│        ├─ IPMS-2.4 (Roles & Seeding)
│        └─ IPMS-4.1 (Gateway JWT Middleware) ──── Day 4
│           └─ ...
├─ IPMS-2.5 (Audit Events)
│  └─ IPMS-2.6 (Auth Tests)

Day 3: IP Service
├─ IPMS-3.1 (Migrations)
│  └─ IPMS-3.2 (Models)
│     └─ IPMS-3.3 (CRUD Controller)
│        └─ IPMS-3.4 (Authorization Policies)
│           └─ IPMS-3.5 (Audit Logging)
│              └─ IPMS-3.6 (IP Tests)
│                 └─ IPMS-4.2 (Gateway Proxy) ──── Day 4

Day 4: Gateway & Frontend Foundation
├─ IPMS-4.1 (JWT Middleware)
│  └─ IPMS-4.2 (Proxy Controllers)
│     └─ IPMS-4.3 (Routes & CORS)
├─ IPMS-4.4 (Frontend Dependencies)
│  └─ IPMS-4.5 (TypeScript Types)
│     └─ IPMS-4.6 (API Client)
│        └─ IPMS-4.7 (Auth Store & Views)
│           └─ IPMS-5.1 (IP Store) ─────────────── Day 5

Day 5: Frontend Completion
├─ IPMS-5.1 (IP Store)
│  └─ IPMS-5.2 (IP Views)
│     └─ IPMS-5.3 (Audit Dashboard)
│        └─ IPMS-5.4 (UI/UX Polish)
│           └─ IPMS-5.5 (Frontend Tests)
│              └─ IPMS-5.6 (Docker Optimization)
│                 └─ IPMS-5.7 (Documentation)
│                    └─ IPMS-5.8 (Final Review)
```

---

## 4. Task Summary

### By Phase

| Phase | Tasks | Total Est. Hours | Critical Path |
|-------|-------|------------------|---------------|
| Day 1: Infrastructure | IPMS-1.1 to IPMS-1.6 | 4 hours | Yes |
| Day 2: Auth Service | IPMS-2.1 to IPMS-2.6 | 7.5 hours | Yes |
| Day 3: IP Service | IPMS-3.1 to IPMS-3.6 | 8 hours | Yes |
| Day 4: Gateway & Frontend | IPMS-4.1 to IPMS-4.7 | 6.5 hours | Yes |
| Day 5: Completion | IPMS-5.1 to IPMS-5.8 | 7 hours | Yes |
| **Total** | **29 Tasks** | **~33 hours** | - |

### By Assignee Mode

| Mode | Tasks | Focus Areas |
|------|-------|-------------|
| code (Laravel/PHP) | 14 | Backend services, migrations, controllers, policies |
| code (TypeScript/Vue) | 10 | Frontend stores, components, views, types |
| devops-engineer-t2 | 3 | Docker configuration, optimization |
| documentation-writer-t2 | 1 | README documentation |
| quality-assurance-gate | 1 | Final review and validation |

### By Risk Level

| Risk Level | Tasks | Primary Risk |
|------------|-------|--------------|
| High | 3 | RISK-002: JWT refresh complexity |
| Medium | 17 | Various integration risks |
| Low | 9 | Standard implementation |

---

## 5. Risk References

Each task references applicable risks from the Risk Register:

| Risk ID | Affected Tasks | Mitigation Applied |
|---------|----------------|-------------------|
| RISK-001 | All | Daily milestone checkpoints, strict prioritization |
| RISK-002 | IPMS-2.3, IPMS-4.6 | Early testing, proven libraries, fallback strategies |
| RISK-003 | IPMS-1.6, IPMS-1.5 | Health checks, service names, Docker networking |
| RISK-004 | All | Scope checklist, MVP focus, defer non-essentials |
| RISK-005 | IPMS-3.5 | Spatie Activity Log, phased implementation |
| RISK-006 | IPMS-4.3 | Early configuration, explicit origins, testing |
| RISK-010 | All phases | Backend-first approach, mocks for frontend |

---

## 6. Quality Gates

### Critical Success Factors

1. **All services start**: `docker-compose up -d` completes without errors
2. **Authentication works**: JWT tokens issued, validated, refreshed
3. **IP CRUD functional**: Full lifecycle from create to delete
4. **Audit trail complete**: All changes logged with session tracking
5. **Frontend connects**: Vue app communicates via Gateway
6. **Tests passing**: All feature and E2E tests pass
7. **20+ commits**: Meaningful Git history demonstrates process

### Go/No-Go Criteria Before Next Phase

| Phase | Criteria | Checkpoint |
|-------|----------|------------|
| Day 1 | All containers running, networking verified | IPMS-1.6 |
| Day 2 | All auth tests passing, tokens working | IPMS-2.6 |
| Day 3 | All IP tests passing, audit logging verified | IPMS-3.6 |
| Day 4 | Frontend connects to API, auth views functional | IPMS-4.7 |
| Day 5 | All requirements met, tests passing | IPMS-5.8 |

---

## 7. Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-02-01 | Architect | Initial module plan creation with all 29 tasks |
