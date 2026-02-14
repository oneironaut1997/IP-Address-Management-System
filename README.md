# IP Address Management System

A comprehensive full-stack IP Address Management System built with Laravel microservices, Vue 3, and Docker. This system demonstrates modern web development practices including JWT authentication, role-based access control, audit logging, and microservices architecture.

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [API Documentation](#api-documentation)
- [Development Guide](#development-guide)
- [Testing](#testing)
- [Project Structure](#project-structure)

## Architecture Overview

### System Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           Docker Environment                             │
│                                                                          │
│  ┌─────────────────┐      ┌──────────────────┐      ┌─────────────────┐ │
│  │   Vue Frontend  │──────▶  Gateway Service │──────▶  Auth Service   │ │
│  │   (Port 5173)   │      │   (Port 8000)    │      │   (Port 8001)   │ │
│  └─────────────────┘      └──────────────────┘      └─────────────────┘ │
│  │    Container    │      │     Container    │      │    Container    │ │
│  └─────────────────┘      └──────────────────┘      └─────────────────┘ │
│                                  │                                       │
│                                  ▼                                       │
│                           ┌──────────────────┐                          │
│                           │  IP Management   │                          │
│                           │  (Port 8002)     │                          │
│                           └──────────────────┘                          │
│                           │    Container     │                          │
│                           └──────────────────┘                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### Tech Stack

| Layer | Technology |
|-------|------------|
| **Frontend** | Vue 3 + TypeScript + Pinia |
| **API Gateway** | Laravel 12 |
| **Auth Service** | Laravel 12 + JWT + Redis |
| **IP Service** | Laravel 12 + Activity Log |
| **Databases** | MySQL 8.0 (separate per service) |
| **Cache** | Redis 7 |
| **Containerization** | Docker + Docker Compose |

### Key Features

- **JWT Authentication** with automatic token refresh
- **Role-based Access Control** (regular user, super_admin)
- **IP CRUD Operations** with IPv4/IPv6 validation
- **Unified Audit Logging** combining auth events and IP management activities
- **IP History Tracking** for all changes
- **Responsive UI** with toast notifications

### Architecture Patterns

The backend services follow industry-standard design patterns:

| Pattern | Implementation | Purpose |
|---------|---------------|---------|
| **Service Layer** | `AuthService`, `IPService` | Encapsulates business logic, separates concerns from controllers |
| **Form Request** | `LoginRequest`, `StoreIPRequest`, etc. | Validates and authorizes HTTP requests |
| **API Resource** | `UserResource`, `IPAddressResource`, etc. | Transforms models into consistent JSON responses |

```
┌─────────────────────────────────────────────────────────────────┐
│                        Request Flow                              │
│                                                                  │
│  HTTP Request → Form Request (validation) → Controller          │
│                                                    │              │
│                                                    ▼              │
│                                              Service Layer       │
│                                                    │              │
│                                                    ▼              │
│                                              API Resource        │
│                                                    │              │
│                                                    ▼              │
│                                              JSON Response       │
└─────────────────────────────────────────────────────────────────┘
```

## Prerequisites

- Docker Engine 24.x or higher
- Docker Compose 2.x or higher
- (Optional) Node.js 20+ and npm - only needed for local frontend development outside Docker
- (Optional) Composer 2.x - only needed for local PHP development outside Docker

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd ip-management-system
```

### 2. Environment Configuration

```bash
# Auth Service
cp services/auth-service/.env.example services/auth-service/.env

# IP Management Service
cp services/ip-management/.env.example services/ip-management/.env

# Gateway Service
cp services/gateway/.env.example services/gateway/.env
```

### 3. Start Docker Services

```bash
docker-compose up -d --build
```

### 4. Run Database Migrations

```bash
# Auth Service migrations with seed data
docker exec -it ipms-auth-service php artisan migrate:fresh --seed

# IP Management Service migrations
docker exec -it ipms-ip-management php artisan migrate:fresh

# Run the queue worker
docker-compose exec auth-service php artisan queue:work
```

### 5. Verify Installation

Once all containers are running, access the application:

| Service | URL | Description |
|---------|-----|-------------|
| **Frontend** | http://localhost:5173 | Vue.js 3 web interface |
| **Gateway API** | http://localhost:8000 | API Gateway endpoint |
| **Auth Service** | http://localhost:8001 | Authentication service (internal) |
| **IP Management** | http://localhost:8002 | IP management service (internal) |

All services are now running in Docker containers with automatic restart enabled.

### Default Credentials

After seeding, the following super admin account is available:
- **Email**: admin@example.com
- **Password**: password

## API Documentation

### Authentication Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/auth/register` | Register new user | No |
| POST | `/api/auth/login` | Login and get tokens | No |
| POST | `/api/auth/logout` | Logout and invalidate tokens | Yes |
| POST | `/api/auth/refresh` | Refresh access token | Yes (refresh token) |
| GET | `/api/auth/me` | Get current user info | Yes |

### IP Management Endpoints

| Method | Endpoint | Description | Auth Required | Role |
|--------|----------|-------------|---------------|------|
| GET | `/api/ip` | List all IP addresses | Yes | Any |
| POST | `/api/ip` | Create new IP address | Yes | Any |
| GET | `/api/ip/{id}` | Get IP address details | Yes | Any |
| PUT | `/api/ip/{id}` | Update IP address | Yes | Owner/Admin |
| DELETE | `/api/ip/{id}` | Delete IP address | Yes | Super Admin |
| GET | `/api/ip/{id}/history` | Get IP change history | Yes | Any |

### Audit Endpoints

| Method | Endpoint | Description | Auth Required | Role |
|--------|----------|-------------|---------------|------|
| GET | `/api/audit/logs` | Get unified audit logs (auth + IP activities) | Yes | Super Admin |

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `type` | string | 'all' | Filter by type: 'auth', 'ip', or 'all' |
| `event_type` | string | null | Filter by event type |
| `user_id` | string | null | Filter by user ID |
| `from` | datetime | null | Filter from date |
| `to` | datetime | null | Filter to date |
| `page` | integer | 1 | Page number |
| `per_page` | integer | 50 | Items per page (max 100) |

#### Response Example
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "type": "auth",
      "event_type": "login",
      "entity_type": "Session",
      "entity_id": "uuid",
      "user_id": "uuid",
      "metadata": { "ip_address": "...", "user_agent": "..." },
      "created_at": "2024-01-01T00:00:00Z"
    },
    {
      "id": "uuid",
      "type": "ip",
      "event_type": "ip.created",
      "entity_type": "IPAddress",
      "entity_id": "uuid",
      "user_id": "uuid",
      "metadata": { "ip": {...} },
      "created_at": "2024-01-01T00:05:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 100,
    "auth_count": 60,
    "ip_count": 40,
    "type": "all"
  }
}
```

### Request/Response Examples

#### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'
```

Response:
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

#### Create IP Address
```bash
curl -X POST http://localhost:8000/api/ip \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {access_token}" \
  -d '{
    "ip_address": "192.168.1.1",
    "label": "Office Network",
    "comment": "Main office subnet"
  }'
```

## Development Guide

### Running Tests

#### Backend Tests

```bash
# Gateway tests
docker exec -it ipms-gateway php artisan test

# Auth Service tests
docker exec -it ipms-auth-service php artisan test

# IP Management tests
docker exec -it ipms-ip-management php artisan test
```

#### Frontend Tests

You can run tests either inside the Docker container or locally:

**Option 1: Inside Docker Container**
```bash
# Run tests inside the running frontend container
docker exec -it ipms-frontend npm run test
```

**Option 2: Local Development (requires Node.js)**
```bash
cd frontend
npm install
npm run test
```

### Code Style

#### PHP (Laravel)
- Follow PSR-12 coding standards
- Use Laravel Pint for code formatting:
  ```bash
  docker exec -it ipms-auth-service ./vendor/bin/pint
  docker exec -it ipms-ip-management ./vendor/bin/pint
  ```

#### TypeScript/Vue
- Use ESLint and Prettier for formatting

  **Using Docker:**
  ```bash
  docker exec -it ipms-frontend npm run lint
  ```

  **Local Development:**
  ```bash
  cd frontend
  npm run lint
  npm run format
  ```

### Git Workflow

1. Create feature branches from `main`
2. Use conventional commit messages:
   - `feat:` New features
   - `fix:` Bug fixes
   - `test:` Test additions/changes
   - `docs:` Documentation changes
   - `chore:` Maintenance tasks

### Environment Variables

#### Auth Service (.env)
```env
DB_CONNECTION=mysql
DB_HOST=auth-db
DB_PORT=3306
DB_DATABASE=auth_service
DB_USERNAME=root
DB_PASSWORD=secret

JWT_SECRET=your-jwt-secret
REDIS_HOST=redis
```

#### IP Management Service (.env)
```env
DB_CONNECTION=mysql
DB_HOST=ip-db
DB_PORT=3306
DB_DATABASE=ip_management
DB_USERNAME=root
DB_PASSWORD=secret

JWT_SECRET=your-jwt-secret
REDIS_HOST=redis
```

#### Gateway Service (.env)
```env
JWT_SECRET=your-jwt-secret
AUTH_SERVICE_URL=http://auth-service:8000
IP_SERVICE_URL=http://ip-management:8000
```

## Project Structure

```
ip-management-system/
├── docker-compose.yml          # Docker orchestration
├── docker/                     # Docker configurations
│   ├── mysql/                  # MySQL initialization scripts
│   └── redis/                  # Redis configuration
├── frontend/                   # Vue 3 Frontend
│   ├── src/
│   │   ├── api/               # API client
│   │   ├── components/        # Vue components
│   │   ├── stores/            # Pinia stores
│   │   ├── types/             # TypeScript types
│   │   └── views/             # Vue views
│   └── package.json
└── services/                   # Backend services
    ├── auth-service/          # Authentication service
    │   └── app/
    │       ├── Http/
    │       │   ├── Controllers/   # HTTP request handlers
    │       │   ├── Requests/      # Form request validation
    │       │   └── Resources/     # API response transformers
    │       ├── Models/            # Eloquent models
    │       └── Services/          # Business logic layer
    ├── gateway/               # API Gateway
    └── ip-management/         # IP Management service
        └── app/
            ├── Http/
            │   ├── Controllers/   # HTTP request handlers
            │   ├── Requests/      # Form request validation
            │   └── Resources/     # API response transformers
            ├── Models/            # Eloquent models
            └── Services/          # Business logic layer
```

## Security Considerations

- JWT tokens expire after 1 hour (access) and 7 days (refresh)
- All passwords are hashed with bcrypt
- Audit logs are immutable (no delete endpoint)
- CORS configured for frontend only
- Role-based access control on all sensitive operations

## Troubleshooting

### Common Issues

1. **Database connection errors**: Ensure MySQL containers are healthy
   ```bash
   docker-compose ps
   ```

2. **JWT validation failures**: Check that all services use the same JWT_SECRET

3. **CORS errors**: Verify Gateway CORS configuration allows frontend origin

### Logs

```bash
# View service logs
docker-compose logs -f gateway
docker-compose logs -f auth-service
docker-compose logs -f ip-management
docker-compose logs -f frontend
```

## License

This project is for demonstration purposes.
