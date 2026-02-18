/**
 * API Client
 *
 * Axios instance with authentication interceptors for handling JWT tokens
 * stored in localStorage and automatic token refresh on 401 responses.
 *
 * SECURITY: Tokens are stored in localStorage for Bearer token authentication.
 * The access token is sent via Authorization header with each request.
 *
 * @package API
 */

import axios, { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import type { AuthResponse, APIResponse } from '@/types'

/**
 * Storage keys for authentication tokens
 */
const ACCESS_TOKEN_KEY = 'access_token'
const REFRESH_TOKEN_KEY = 'refresh_token'

/**
 * Get access token from localStorage
 */
function getAccessToken(): string | null {
  return localStorage.getItem(ACCESS_TOKEN_KEY)
}

/**
 * Get refresh token from localStorage
 */
function getRefreshToken(): string | null {
  return localStorage.getItem(REFRESH_TOKEN_KEY)
}

/**
 * Store tokens in localStorage
 */
function setTokens(accessToken: string, refreshToken: string): void {
  localStorage.setItem(ACCESS_TOKEN_KEY, accessToken)
  localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken)
}

/**
 * Clear tokens from localStorage
 */
function clearTokens(): void {
  localStorage.removeItem(ACCESS_TOKEN_KEY)
  localStorage.removeItem(REFRESH_TOKEN_KEY)
}

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
 * Uses localStorage for token storage with Authorization header.
 */
const api = axios.create({
  baseURL,
  timeout: REQUEST_TIMEOUT,
  withCredentials: false, // Not using cookies, using localStorage instead
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

/**
 * Request Interceptor
 *
 * Adds the API version prefix to all URLs and adds Authorization header
 * with Bearer token from localStorage.
 */
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    // Add Authorization header with Bearer token
    const accessToken = getAccessToken()
    if (accessToken) {
      config.headers.Authorization = `Bearer ${accessToken}`
    }

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
 * Note: Tokens are stored in localStorage and sent via Authorization header.
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
      // Skip token refresh for auth endpoints (login, refresh, register)
      // These endpoints should return their own error messages, not trigger token refresh
      const isAuthEndpoint = originalRequest.url?.includes('/auth/login') || 
                            originalRequest.url?.includes('/auth/refresh') ||
                            originalRequest.url?.includes('/auth/register')
      
      // Skip if this is an auth endpoint - return original error
      if (isAuthEndpoint) {
        return Promise.reject(error)
      }

      // Skip if no access token exists - user is not logged in
      // Return original error instead of trying to refresh
      const accessToken = getAccessToken()
      if (!accessToken) {
        return Promise.reject(error)
      }

      originalRequest._retry = true

      try {
        // Attempt to refresh the token using the refresh token from localStorage
        const refreshToken = getRefreshToken()
        if (!refreshToken) {
          throw new Error('No refresh token available')
        }

        const response = await axios.post<APIResponse<AuthResponse>>(
          `${baseURL}/v1/auth/refresh`,
          {},
          {
            headers: {
              Authorization: `Bearer ${refreshToken}`,
            },
          }
        )

        // Store new tokens in localStorage
        if (response.data.success && response.data.data) {
          const { access_token, refresh_token } = response.data.data
          setTokens(access_token, refresh_token)

          // Retry the original request with new token
          originalRequest.headers.Authorization = `Bearer ${access_token}`
          return api(originalRequest)
        }

        throw new Error('Token refresh failed')
      } catch (refreshError) {
        // Token refresh failed, clear tokens and redirect to login
        clearTokens()
        // window.location.href = '/login'
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
