/**
 * Auth Store
 *
 * Pinia store for managing authentication state and operations.
 * Uses the Composition API (setup stores) pattern.
 *
 * @package Stores
 */

import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import axios, { AxiosError } from 'axios'
import api, { setAuthTokens, clearAuthTokens } from '@/api/client'
import type { User, LoginCredentials, RegistrationData, AuthResponse, APIResponse, APIError } from '@/types'

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
 */
export const useAuthStore = defineStore('auth', () => {
  // State
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
      const { data } = await api.post<APIResponse<AuthResponse>>('/auth/login', credentials)

      if (!data.data) {
        throw new Error('No authentication data received')
      }

      // Store tokens
      setAuthTokens(data.data.access_token, data.data.refresh_token)

      // Fetch user info
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
      
      user.value = data.data.user
      // Persist user to localStorage for persistence across reloads
      localStorage.setItem('user', JSON.stringify(data.data.user))
    } catch (err: unknown) {
      user.value = null
      throw err
    }
  }

  /**
   * Logout the current user
   *
   * Calls the logout endpoint and clears local state.
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
   * Removes user data and tokens from memory and storage.
   */
  function clearAuthState(): void {
    user.value = null
    clearAuthTokens()
    localStorage.removeItem('user')
  }

  /**
   * Initialize auth state from localStorage
   *
   * Attempts to restore the session on app startup.
   */
  function initializeAuth(): void {
    const storedUser = localStorage.getItem('user')
    if (storedUser) {
      try {
        user.value = JSON.parse(storedUser) as User
      } catch {
        clearAuthState()
      }
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
  }
})
