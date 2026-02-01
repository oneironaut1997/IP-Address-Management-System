# ADR-002: JWT Authentication with Refresh Tokens

## Status

**Accepted**

## Context

The system requires authentication that:
- Works across multiple microservices
- Is stateless (for scalability)
- Supports automatic token refresh
- Integrates with the API Gateway pattern

Options considered:
1. **Session-based Authentication**: Server-side sessions stored in Redis
2. **JWT (JSON Web Tokens)**: Stateless tokens with signature verification
3. **OAuth 2.0**: Token-based with external provider support

## Decision

We will use **JWT Authentication with Refresh Token Rotation**:
- Access tokens: 1-hour expiration
- Refresh tokens: 7-day expiration, stored in Redis
- Library: `tymon/jwt-auth` for Laravel

## Consequences

### Positive
- **Stateless**: No server-side session storage needed for validation
- **Cross-Service**: Easy to validate in Gateway and forward to services
- **Standard**: JWT is industry standard for API authentication
- **Scalable**: No shared session storage required
- **Mobile-Friendly**: Works well with SPA and mobile clients

### Negative
- **Token Size**: JWTs are larger than session IDs
- **Cannot Revoke Immediately**: Must wait for token expiry (mitigated by short access token life)
- **Clock Synchronization**: Requires reasonable clock sync between services
- **Complexity**: Refresh token rotation adds implementation complexity
- **Storage**: Refresh tokens require secure storage (Redis)

## Implementation Details

### Token Flow
```
1. User logs in → receives access_token + refresh_token
2. Access token stored in memory (Pinia store)
3. Refresh token stored in localStorage
4. Axios interceptor adds access token to requests
5. On 401, use refresh token to get new access token
6. Rotate refresh token on each use (security best practice)
```

### Redis Storage
```
Key: refresh:{token_jti}
Value: user_id
TTL: 604800 seconds (7 days)
```

## Alternatives Considered

### Session-Based (Redis Sessions)
- **Pros**: Simple to implement, immediate revocation
- **Cons**: Requires shared session storage, harder to scale across services
- **Decision**: Rejected for stateless microservices

### OAuth 2.0 with PKCE
- **Pros**: Industry standard for authorization, supports external providers
- **Cons**: Overkill for this use case, adds unnecessary complexity
- **Decision**: Rejected due to timeline constraints

### Simple JWT without Refresh
- **Pros**: Simpler implementation
- **Cons**: Long-lived tokens are security risk, short-lived tokens poor UX
- **Decision**: Rejected in favor of refresh token pattern

## Rationale

JWT with refresh tokens provides the best balance of:
- Security (short-lived access tokens)
- User Experience (automatic refresh)
- Scalability (stateless validation)
- Implementation complexity (manageable with proven libraries)

## Risks

| Risk | Mitigation |
|------|------------|
| JWT secret exposure | Store in .env, never commit, rotate if exposed |
| Refresh token theft | Use HTTPS, short expiry, rotate on use |
| Clock skew | Use reasonable expiry windows (1 hour) |

## Date

2026-02-01

## Author

Designer-T1
