# ADR-004: Separate Databases Per Service

## Status

**Accepted**

## Context

For a microservices architecture, we need to decide on the database strategy:
- Shared database with separate schemas?
- Separate databases per service?
- Shared database with table prefixes?

This decision affects data isolation, scalability, and maintenance.

## Decision

We will implement **separate MySQL databases per service**:
- `auth-db`: Auth Service (users, sessions, roles/permissions)
- `ip-db`: IP Management Service (ip_addresses, ip_history, audit_logs)

## Consequences

### Positive
- **True Service Independence**: Services can be deployed, scaled, and maintained independently
- **Technology Flexibility**: Each service could use different database types (though we're using MySQL for both)
- **Fault Isolation**: Database issues in one service don't affect others
- **Clear Data Ownership**: No confusion about which service owns which data
- **Security**: Database credentials can be service-specific
- **Performance**: No resource contention between services

### Negative
- **Data Consistency**: Cross-service transactions require Saga pattern (not implemented)
- **Backup Complexity**: Multiple databases to backup
- **Migration Management**: Separate migration sets per service
- **Resource Usage**: Multiple MySQL instances (mitigated by Docker)

## Implementation

### Docker Compose Configuration
```yaml
services:
  auth-db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: auth_service
      
  ip-db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: ip_management
```

### Connection Configuration
```php
// Auth Service .env
DB_CONNECTION=mysql
DB_HOST=auth-db
DB_DATABASE=auth_service

// IP Management Service .env
DB_CONNECTION=mysql
DB_HOST=ip-db
DB_DATABASE=ip_management
```

## Alternatives Considered

### Shared Database with Schemas
- **Pros**: Easier joins if needed, single backup, simpler migrations
- **Cons**: Reduced isolation, potential coupling, harder to scale independently
- **Decision**: Rejected for true microservices isolation

### Shared Database with Table Prefixes
- **Pros**: Simple to implement, single connection
- **Cons**: No real isolation, naming collisions possible
- **Decision**: Rejected as doesn't provide real separation

### Different Database Technologies
- **Pros**: Best tool for each job (e.g., PostgreSQL for Auth, MySQL for IP)
- **Cons**: Increased complexity, more technologies to learn and maintain
- **Decision**: Rejected for simplicity and consistency

## Rationale

Separate databases align with microservices best practices:
1. **Database Per Service** pattern ensures true independence
2. Docker Compose makes managing multiple databases easy
3. Clear boundaries prevent tight coupling
4. Future scalability options (different DB engines, sharding)

## Trade-offs

| Aspect | Impact | Mitigation |
|--------|--------|------------|
| Cross-service queries | Not possible | Use API calls or Gateway aggregation |
| Transactions | Limited | Implement Saga pattern if needed later |
| Backup | Multiple jobs | Docker volumes simplify backup |

## Date

2026-02-01

## Author

Designer-T1
