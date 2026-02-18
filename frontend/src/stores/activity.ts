/**
 * Activity Store
 *
 * Pinia store for managing activity log state and operations.
 * Uses the Composition API (setup stores) pattern.
 *
 * Supports unified activity logs from both auth-service (login/logout)
 * and ip-management (ip.created, ip.updated, ip.deleted).
 *
 * @package Stores
 */

import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '@/api/client'
import type { AuditLog, APIResponse, AuditLogType } from '@/types'

/**
 * Activity Store
 *
 * Manages activity log state including:
 * - List of all activity logs (auth + IP activities)
 * - Loading and error states
 * - Filtering capabilities by type and event
 */
export const useActivityStore = defineStore('activity', () => {
  // State
  const logs = ref<AuditLog[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const eventFilter = ref<string>('')
  const typeFilter = ref<AuditLogType>('all')
  const meta = ref<{
    current_page: number
    per_page: number
    total: number
    auth_count: number
    ip_count: number
    type: AuditLogType
  } | null>(null)

  // Getters (Computed)
  const filteredLogs = computed(() => {
    let result = logs.value

    // Filter by type
    if (typeFilter.value !== 'all') {
      result = result.filter((log) => (log.type || 'auth') === typeFilter.value)
    }

    // Filter by event type
    if (eventFilter.value) {
      result = result.filter((log) =>
        log.event_type.toLowerCase().includes(eventFilter.value.toLowerCase())
      )
    }

    return result
  })

  const eventTypes = computed(() => {
    const types = new Set<string>()
    logs.value.forEach((log) => types.add(log.event_type))
    return Array.from(types).sort()
  })

  const authLogs = computed(() =>
    logs.value.filter((log) => (log.type || 'auth') === 'auth')
  )

  const ipLogs = computed(() =>
    logs.value.filter((log) => log.type === 'ip')
  )

  // Actions

  /**
   * Fetch all activity logs (both auth and IP activities)
   *
   * @param type - Filter by type: 'auth', 'ip', or 'all' (default: 'all')
   * @throws Error on fetch failure
   */
  async function fetchAllLogs(type: AuditLogType = 'all'): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const { data } = await api.get<APIResponse<AuditLog[]>>('/audit/logs', {
        params: { type },
      })
      logs.value = data.data ?? []
      // Store metadata from response
      if (data.meta) {
        meta.value = data.meta as unknown as typeof meta.value
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Failed to fetch activity logs'
      error.value = message
      throw new Error(message)
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch only authentication logs
   *
   * @throws Error on fetch failure
   */
  async function fetchAuthLogs(): Promise<void> {
    await fetchAllLogs('auth')
  }

  /**
   * Fetch only IP management activity logs
   *
   * @throws Error on fetch failure
   */
  async function fetchIPLogs(): Promise<void> {
    await fetchAllLogs('ip')
  }

  /**
   * Set event type filter
   *
   * @param filter - Event type to filter by
   */
  function setEventFilter(filter: string): void {
    eventFilter.value = filter
  }

  /**
   * Set type filter (auth, ip, or all)
   *
   * @param type - The type filter to apply
   */
  function setTypeFilter(type: AuditLogType): void {
    typeFilter.value = type
  }

  /**
   * Clear all filters
   */
  function clearFilter(): void {
    eventFilter.value = ''
    typeFilter.value = 'all'
  }

  /**
   * Clear all state
   */
  function clearState(): void {
    logs.value = []
    error.value = null
    eventFilter.value = ''
    typeFilter.value = 'all'
    meta.value = null
  }

  return {
    // State
    logs,
    loading,
    error,
    eventFilter,
    typeFilter,
    meta,
    // Getters
    filteredLogs,
    eventTypes,
    authLogs,
    ipLogs,
    // Actions
    fetchAllLogs,
    fetchAuthLogs,
    fetchIPLogs,
    setEventFilter,
    setTypeFilter,
    clearFilter,
    clearState,
  }
})
