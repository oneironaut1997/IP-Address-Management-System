/**
 * API Client
 *
 * Axios instance with authentication interceptors for handling JWT tokens
 * and automatic token refresh on 401 responses.
 *
 * @package API
 */

import axios, { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import type { AuthResponse } from '@/types'

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
 * Axios instance with default configuration
 */
const api = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

/**
 * Request Interceptor
 *
 * Adds the Authorization header with the access token from localStorage
 * to every outgoing request.
 */
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('access_token')
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`
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
        const refreshToken = localStorage.getItem('refresh_token')

        if (!refreshToken) {
          throw new Error('No refresh token available')
        }

        // Attempt to refresh the token
        const response = await axios.post<AuthResponse>(
          `${baseURL}/auth/refresh`,
          {},
          {
            headers: {
              Authorization: `Bearer ${refreshToken}`,
            },
          }
        )

        const { access_token, refresh_token } = response.data.data

        // Store new tokens
        localStorage.setItem('access_token', access_token)
        localStorage.setItem('refresh_token', refresh_token)

        // Update the original request with the new token
        originalRequest.headers.Authorization = `Bearer ${access_token}`

        // Retry the original request
        return api(originalRequest)
      } catch (refreshError) {
        // Token refresh failed, clear storage and redirect to login
        localStorage.removeItem('access_token')
        localStorage.removeItem('refresh_token')
        localStorage.removeItem('user')

        // Redirect to login page
        // window.location.href = '/login'

        return Promise.reject(refreshError)
      }
    }

    return Promise.reject(error)
  }
)

/**
 * Set authentication tokens in localStorage
 *
 * @param accessToken - JWT access token
 * @param refreshToken - JWT refresh token
 */
export function setAuthTokens(accessToken: string, refreshToken: string): void {
  localStorage.setItem('access_token', accessToken)
  localStorage.setItem('refresh_token', refreshToken)
}

/**
 * Clear authentication tokens from localStorage
 */
export function clearAuthTokens(): void {
  localStorage.removeItem('access_token')
  localStorage.removeItem('refresh_token')
  localStorage.removeItem('user')
}

/**
 * Get the current access token
 *
 * @returns The access token or null
 */
export function getAccessToken(): string | null {
  return localStorage.getItem('access_token')
}

/**
 * Check if user is authenticated
 *
 * @returns True if access token exists
 */
export function isAuthenticated(): boolean {
  return !!getAccessToken()
}

export default api
