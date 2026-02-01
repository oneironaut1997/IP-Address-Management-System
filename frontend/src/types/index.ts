/**
 * TypeScript Type Definitions
 *
 * This file contains all TypeScript interfaces and types used throughout
 * the IP Address Management System frontend application.
 *
 * @package Types
 */

/**
 * User Roles
 * - regular: Standard user with limited permissions
 * - super_admin: Administrator with full system access
 */
export type UserRole = 'regular' | 'super_admin'

/**
 * IP Address Types
 * - ipv4: Internet Protocol version 4
 * - ipv6: Internet Protocol version 6
 */
export type IPType = 'ipv4' | 'ipv6'

/**
 * IP History Actions
 * - created: New IP address was created
 * - updated: Existing IP address was modified
 * - deleted: IP address was deleted
 */
export type IPHistoryAction = 'created' | 'updated' | 'deleted'

/**
 * User Entity
 *
 * Represents an authenticated user in the system.
 */
export interface User {
  /** Unique identifier (UUID) */
  id: string
  /** User's email address */
  email: string
  /** User's role for authorization */
  role: UserRole
  /** Timestamp when user was created */
  created_at: string
}

/**
 * IP Address Entity
 *
 * Represents an IP address record in the system.
 */
export interface IPAddress {
  /** Unique identifier (UUID) */
  id: string
  /** UUID of the user who owns this IP */
  user_id: string
  /** The actual IP address (IPv4 or IPv6) */
  ip_address: string
  /** Display label for the IP */
  label: string
  /** Optional comment/description */
  comment?: string
  /** IP protocol version */
  type: IPType
  /** Timestamp when record was created */
  created_at: string
  /** Timestamp when record was last updated */
  updated_at: string
}

/**
 * IP History Entity
 *
 * Represents a change history entry for an IP address.
 */
export interface IPHistory {
  /** Unique identifier (UUID) */
  id: string
  /** UUID of the IP address that was modified */
  ip_address_id: string
  /** UUID of the user who made the change */
  modified_by: string
  /** Previous values before modification */
  old_values: Record<string, unknown>
  /** New values after modification */
  new_values: Record<string, unknown>
  /** Type of action performed */
  action: IPHistoryAction
  /** Timestamp when the change occurred */
  created_at: string
}

/**
 * Audit Log Entity
 *
 * Represents a system audit log entry for compliance and tracking.
 */
export interface AuditLog {
  /** Unique identifier (UUID) */
  id: string
  /** UUID of the user who performed the action */
  user_id: string
  /** Type of event (e.g., 'login', 'ip.created', 'ip.updated') */
  event_type: string
  /** Type of entity affected (e.g., 'User', 'IPAddress') */
  entity_type: string
  /** UUID of the entity affected */
  entity_id: string
  /** Additional metadata about the event */
  metadata: Record<string, unknown>
  /** Session identifier for tracking */
  session_id: string
  /** Timestamp when the event occurred */
  created_at: string
}

/**
 * Login Credentials
 *
 * Data required for user authentication.
 */
export interface LoginCredentials {
  /** User's email address */
  email: string
  /** User's password */
  password: string
}

/**
 * Registration Data
 *
 * Data required for user registration.
 */
export interface RegistrationData {
  /** User's email address */
  email: string
  /** User's password */
  password: string
  /** Password confirmation (must match password) */
  password_confirmation: string
}

/**
 * Auth Response
 *
 * Response from authentication endpoints containing tokens.
 */
export interface AuthResponse {
  /** JWT access token for API requests */
  access_token: string
  /** Refresh token for obtaining new access tokens */
  refresh_token: string
  /** Token type (typically 'bearer') */
  token_type: string
  /** Token expiry time in seconds */
  expires_in: number
}

/**
 * API Error Response
 *
 * Standardized error response from the API.
 */
export interface APIError {
  /** Error code for programmatic handling */
  code: string
  /** Human-readable error message */
  message: string
  /** Additional error details (e.g., validation errors) */
  details?: Record<string, string[]>
}

/**
 * API Response Wrapper
 *
 * Standardized API response format.
 */
export interface APIResponse<T> {
  /** Whether the request was successful */
  success: boolean
  /** Response data (if successful) */
  data?: T
  /** Error information (if unsuccessful) */
  error?: APIError
  /** Response metadata */
  meta?: {
    /** Request timestamp */
    timestamp: string
    /** Request ID for tracing */
    request_id?: string
  }
}

/**
 * Paginated Response
 *
 * API response for paginated list endpoints.
 */
export interface PaginatedResponse<T> {
  /** Array of items for current page */
  data: T[]
  /** Pagination links */
  links: {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
  }
  /** Pagination metadata */
  meta: {
    current_page: number
    from: number
    last_page: number
    per_page: number
    to: number
    total: number
  }
}

/**
 * IP Form Data
 *
 * Data structure for IP address forms.
 */
export interface IPFormData {
  ip_address: string
  label: string
  comment?: string
}
