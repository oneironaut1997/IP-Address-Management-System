# ADR-003: Audit Logging with Spatie ActivityLog

## Status

**Accepted**

## Context

The system requires comprehensive audit logging that:
- Tracks all IP address changes (create, update, delete)
- Records login/logout events
- Stores who made changes and when
- Maintains immutable history
- Supports session-based and lifetime queries

Options considered:
1. **Custom Audit Implementation**: Build from scratch
2. **Spatie Laravel ActivityLog**: Proven package for activity tracking
3. **Database Triggers**: MySQL-level change capture

## Decision

We will use **Spatie Laravel ActivityLog** (`spatie/laravel-activitylog`) for audit logging.

## Consequences

### Positive
- **Proven Solution**: Battle-tested, widely used in Laravel community
- **Automatic Tracking**: Easy to log model changes with traits
- **Flexible Storage**: JSON column for storing old/new values
- **Query Support**: Built-in methods for querying activity
- **Customizable**: Can add custom events beyond model changes
- **Well Maintained**: Regular updates, good documentation

### Negative
- **Additional Dependency**: Another package to manage
- **Database Schema**: Requires activity_log table
- **Learning Curve**: Need to understand package conventions
- **Limited Customization**: Some features may need workarounds

## Implementation

### Activity Logging Pattern
```php
// Log model changes
activity()
    ->causedBy($user)
    ->performedOn($ipAddress)
    ->withProperties([
        'old' => $ipAddress->getOriginal(),
        'new' => $ipAddress->getChanges()
    ])
    ->log('ip.updated');
```

### Logged Events
| Event | Source | Data Stored |
|-------|--------|-------------|
| ip.created | IPController | New IP values, creator |
| ip.updated | IPController | Old/new values, modifier |
| ip.deleted | IPController | Deleted values, deleter |
| auth.login | Auth events | User, IP, timestamp |
| auth.logout | Auth events | User, timestamp |

## Alternatives Considered

### Custom Implementation
- **Pros**: Full control, no dependencies, tailored to exact needs
- **Cons**: Development time, testing burden, maintenance overhead
- **Decision**: Rejected due to time constraints and proven alternative

### MySQL Triggers
- **Pros**: Database-level, cannot be bypassed by application
- **Cons**: Hard to maintain, limited context (no user info), harder to query
- **Decision**: Rejected for application-level solution with more context

### Laravel Auditing (owen-it)
- **Pros**: Similar to Spatie, good features
- **Cons**: Less community adoption, fewer documentation examples
- **Decision**: Rejected in favor of more popular Spatie package

## Rationale

Spatie ActivityLog was chosen because:
1. Reduces development time significantly
2. Proven in production environments
3. Integrates seamlessly with Laravel Eloquent
4. Meets all audit requirements (who, what, when)
5. Active maintenance and community support

## Security Considerations

| Concern | Mitigation |
|---------|------------|
| Audit tampering | No DELETE endpoint for audit logs |
| Audit bypass | Log in controller, not just model events |
| Data exposure | Audit logs only accessible to super_admin |

## Date

2026-02-01

## Author

Designer-T1
