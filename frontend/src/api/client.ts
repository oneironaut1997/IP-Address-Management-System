/**
 * API Client
 *
 * Axios instance with authentication interceptors for handling JWT tokens
 * via httpOnly cookies and automatic token refresh on 401 responses.
 *
 * SECURITY: Tokens are stored in httpOnly cookies (set by the backend)
 * instead of localStorage to prevent XSS attacks. The browser automatically
 * sends cookies with each request when using credentials: 'include'.
 *
 * @package API
 */

import axios, { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import type { AuthResponse, APIResponse } from '@/types'

/**
 * API Version - used for all requests
 */
const API_VERSION = 'v1'

/**
 * Extended Axios request configuration with retry flag
 */
interface ExtendedAxiosRequestConfig extends InternalAxiosRequestConfig {
  _retry?: boolean
}

/**
 * Base API URL from environment or default
 */
const baseURL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

/**
 * Request timeout in milliseconds (10 seconds)
 */
const REQUEST_TIMEOUT = 10000

/**
 * Axios instance with default configuration
 *
 * IMPORTANT: credentials: 'include' is required for cookies to be sent
 * with cross-origin requests. This is safe because:
 * 1. Tokens are stored in httpOnly cookies (not accessible to JS)
 * 2. The SameSite=Lax attribute prevents CSRF attacks
 * 3. The Secure flag is set in production (HTTPS only)
 */
const api = axios.create({
  baseURL,
  timeout: REQUEST_TIMEOUT,
  withCredentials: true, // Required for sending cookies with requests
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

/**
 * Request Interceptor
 *
 * Adds the API version prefix to all URLs and handles tokens via httpOnly cookies.
 */
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    // Add API version prefix to URL
    if (config.url && !config.url.startsWith('/')) {
      config.url = `/${config.url}`
    }
    if (config.url && !config.url.startsWith(`/${API_VERSION}/`) && config.url !== '/health') {
      // Only add version if not already present and not health check
      const prefix = config.url.startsWith('/') ? '' : '/'
      config.url = `${prefix}${API_VERSION}${config.url}`
    }
    return config
  },
  (error: AxiosError) => {
    return Promise.reject(error)
  }
)

/**
 * Response Interceptor
 *
 * Handles 401 responses by attempting to refresh the token.
 * If refresh fails, redirects to login page.
 *
 * Note: Since tokens are in httpOnly cookies, we can't directly
 * access them. However, the refresh endpoint accepts the refresh_token
 * cookie automatically.
 */
api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const originalRequest = error.config as ExtendedAxiosRequestConfig

    // If no config or already retrying, reject immediately
    if (!originalRequest || originalRequest._retry) {
      return Promise.reject(error)
    }

    // Handle 401 Unauthorized
    if (error.response?.status === 401) {
      originalRequest._retry = true

      try {
        // Attempt to refresh the token
        // The refresh token cookie is automatically included
        const response = await axios.post<APIResponse<AuthResponse>>(
          `${baseURL}/auth/refresh`,
          {},
          {
            withCredentials: true, // Important: required for cookie transmission
          }
        )

        // Tokens are now in httpOnly cookies, so we don't need to
        // store them in localStorage. The browser handles it.
        if (!response.data.success) {
          throw new Error('Token refresh failed')
        }

        // Retry the original request
        // Cookies will be automatically included
        return api(originalRequest)
      } catch (refreshError) {
        // Token refresh failed, redirect to login
        window.location.href = '/login'
        return Promise.reject(refreshError)
      }
    }

    return Promise.reject(error)
  }
)

/**
 * Check if user is authenticated
 *
 * This function attempts to check authentication status by
 * making a request to the /me endpoint. If it succeeds,
 * the user is authenticated.
 *
 * @returns Promise<boolean>
 */
export async function isAuthenticated(): Promise<boolean> {
  try {
    await api.get('/auth/me')
    return true
  } catch {
    return false
  }
}

export default api
