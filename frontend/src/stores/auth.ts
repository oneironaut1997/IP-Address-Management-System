/**
 * Auth Store
 *
 * Pinia store for managing authentication state and operations.
 * Uses the Composition API (setup stores) pattern.
 *
 * SECURITY: This store uses localStorage for token storage.
 * Tokens are stored and sent via Authorization header with Bearer scheme.
 *
 * @package Stores
 */

import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import axios from 'axios'
import api from '@/api/client'
import type { User, LoginCredentials, RegistrationData, AuthResponse, APIResponse, APIError } from '@/types'

/**
 * Storage keys for authentication tokens
 */
const ACCESS_TOKEN_KEY = 'access_token'
const REFRESH_TOKEN_KEY = 'refresh_token'

/**
 * Store tokens in localStorage
 */
function setTokens(tokens: AuthResponse): void {
  localStorage.setItem(ACCESS_TOKEN_KEY, tokens.access_token)
  localStorage.setItem(REFRESH_TOKEN_KEY, tokens.refresh_token)
}

/**
 * Clear tokens from localStorage
 */
function clearTokens(): void {
  localStorage.removeItem(ACCESS_TOKEN_KEY)
  localStorage.removeItem(REFRESH_TOKEN_KEY)
}

/**
 * Get access token from localStorage
 */
function getAccessToken(): string | null {
  return localStorage.getItem(ACCESS_TOKEN_KEY)
}

/**
 * Extract user-friendly error message from API errors
 *
 * @param err - The caught error
 * @param defaultMessage - Default message if no specific error found
 * @returns User-friendly error message
 */
function extractErrorMessage(err: unknown, defaultMessage: string): string {
  if (axios.isAxiosError(err)) {
    const apiError = err.response?.data?.error as APIError | undefined
    if (apiError?.message) {
      return apiError.message
    }
    if (apiError?.code) {
      // Map common error codes to user-friendly messages
      const errorMessages: Record<string, string> = {
        'INVALID_CREDENTIALS': 'Invalid email or password. Please try again.',
        'USER_NOT_FOUND': 'User account not found.',
        'TOKEN_EXPIRED': 'Your session has expired. Please log in again.',
        'TOKEN_INVALID': 'Invalid authentication token.',
        'UNAUTHORIZED': 'You are not authorized to perform this action.',
        'VALIDATION_ERROR': 'Please check your input and try again.',
      }
      return errorMessages[apiError.code] || apiError.code
    }
    if (err.code === 'ECONNABORTED') {
      return 'Request timed out. Please check your connection and try again.'
    }
    if (!err.response) {
      return 'Network error. Please check your connection and try again.'
    }
    if (err.response.status >= 500) {
      return 'Server error. Please try again later.'
    }
  }
  if (err instanceof Error) {
    return err.message
  }
  return defaultMessage
}

/**
 * Auth Store
 *
 * Manages authentication state including:
 * - Current user
 * - Authentication status
 * - Role-based permissions
 * - Login/logout operations
 *
 * NOTE: Tokens are stored in localStorage and sent via Authorization header.
 * User data is fetched from the server on each request.
 */
export const useAuthStore = defineStore('auth', () => {
  // State - only in memory, never persisted
  const user = ref<User | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters (Computed)
  const isAuthenticated = computed(() => !!user.value)
  const isSuperAdmin = computed(() => user.value?.role === 'super_admin')

  // Actions

  /**
   * Login user with credentials
   *
   * @param credentials - Login credentials (email and password)
   * @throws Error on login failure
   */
  async function login(credentials: LoginCredentials): Promise<void> {
    loading.value = true
    error.value = null

    try {
      // Post to login endpoint - tokens returned in response
      const { data } = await api.post<APIResponse<AuthResponse>>('/auth/login', credentials)

      if (!data.success) {
        throw new Error(data.error?.message || 'Login failed')
      }

      // Store tokens in localStorage
      if (data.data) {
        setTokens(data.data)
      }

      // Fetch user info after successful login
      await fetchUser()
    } catch (err: unknown) {
      const message = extractErrorMessage(err, 'Login failed. Please try again.')
      error.value = message
      throw new Error(message)
    } finally {
      loading.value = false
    }
  }

  /**
   * Register a new user
   *
   * @param data - Registration data
   * @throws Error on registration failure
   */
  async function register(registrationData: RegistrationData): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await api.post('/auth/register', registrationData)
    } catch (err: unknown) {
      const message = extractErrorMessage(err, 'Registration failed. Please try again.')
      error.value = message
      throw new Error(message)
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch current user information
   *
   * @throws Error if fetch fails
   */
  async function fetchUser(): Promise<void> {
    try {
      const { data } = await api.get<APIResponse<{ user: User }>>('/auth/me')
      
      if (!data.data?.user) {
        throw new Error('No user data received')
      }
      
      // User data is kept only in memory for security
      user.value = data.data.user
    } catch (err: unknown) {
      user.value = null
      throw err
    }
  }

  /**
   * Logout the current user
   *
   * Calls the logout endpoint and clears local state.
   * Also clears tokens from localStorage.
   */
  async function logout(): Promise<void> {
    try {
      await api.post('/auth/logout')
    } catch (err) {
      // Log error but don't throw - we still want to clear local state
      if (axios.isAxiosError(err)) {
        console.error('Logout API error:', err.message)
      }
    } finally {
      clearAuthState()
    }
  }

  /**
   * Clear authentication state
   *
   * Removes user data from memory and tokens from localStorage.
   */
  function clearAuthState(): void {
    user.value = null
    clearTokens()
  }

  /**
   * Initialize auth state
   *
   * Attempts to verify session with server on app startup.
   * This ensures the session is valid before allowing access.
   *
   * @returns Promise<boolean>
   */
  async function initializeAuth(): Promise<boolean> {
    try {
      await fetchUser()
      return true
    } catch {
      clearAuthState()
      return false
    }
  }

  /**
   * Verify authentication status with the server
   *
   * Use this to verify the session is still valid
   * after page reload or when coming back from idle.
   *
   * @returns Promise<boolean>
   */
  async function verifyAuth(): Promise<boolean> {
    try {
      await fetchUser()
      return true
    } catch {
      clearAuthState()
      return false
    }
  }

  return {
    // State
    user,
    loading,
    error,
    // Getters
    isAuthenticated,
    isSuperAdmin,
    // Actions
    login,
    register,
    logout,
    fetchUser,
    initializeAuth,
    clearAuthState,
    verifyAuth,
  }
})
