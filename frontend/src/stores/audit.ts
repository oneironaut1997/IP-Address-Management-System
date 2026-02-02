/**
 * Audit Store
 *
 * Pinia store for managing audit log state and operations.
 * Uses the Composition API (setup stores) pattern.
 *
 * @package Stores
 */

import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '@/api/client'
import type { AuditLog, APIResponse } from '@/types'

/**
 * Audit Store
 *
 * Manages audit log state including:
 * - List of all audit logs
 * - Loading and error states
 * - Filtering capabilities
 */
export const useAuditStore = defineStore('audit', () => {
  // State
  const logs = ref<AuditLog[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const eventFilter = ref<string>('')

  // Getters (Computed)
  const filteredLogs = computed(() => {
    if (!eventFilter.value) return logs.value
    return logs.value.filter((log) =>
      log.event_type.toLowerCase().includes(eventFilter.value.toLowerCase())
    )
  })

  const eventTypes = computed(() => {
    const types = new Set<string>()
    logs.value.forEach((log) => types.add(log.event_type))
    return Array.from(types).sort()
  })

  // Actions

  /**
   * Fetch all audit logs
   *
   * @throws Error on fetch failure
   */
  async function fetchAllLogs(): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const { data } = await api.get<APIResponse<AuditLog[]>>('/audit/logs')
      logs.value = data.data ?? [];
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Failed to fetch audit logs'
      error.value = message
      throw new Error(message)
    } finally {
      loading.value = false
    }
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
   * Clear event filter
   */
  function clearFilter(): void {
    eventFilter.value = ''
  }

  /**
   * Clear all state
   */
  function clearState(): void {
    logs.value = []
    error.value = null
    eventFilter.value = ''
  }

  return {
    // State
    logs,
    loading,
    error,
    eventFilter,
    // Getters
    filteredLogs,
    eventTypes,
    // Actions
    fetchAllLogs,
    setEventFilter,
    clearFilter,
    clearState,
  }
})
