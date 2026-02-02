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
import api, { setAuthTokens, clearAuthTokens } from '@/api/client'
import type { User, LoginCredentials, RegistrationData, AuthResponse } from '@/types'

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
      const { data } = await api.post<AuthResponse>('/auth/login', credentials)

      // Store tokens
      setAuthTokens(data.access_token, data.refresh_token)

      // Fetch user info
      await fetchUser()
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Login failed'
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
  async function register(data: RegistrationData): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await api.post('/auth/register', data)
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Registration failed'
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
      const { data } = await api.get<User>('/auth/me')
      user.value = data
      // Persist user to localStorage for persistence across reloads
      localStorage.setItem('user', JSON.stringify(data))
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
      console.error(err);
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
