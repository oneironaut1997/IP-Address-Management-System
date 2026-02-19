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
 * Pagination metadata from API response
 */
interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
  auth_count: number
  ip_count: number
  type: AuditLogType
}

/**
 * Activity Store
 *
 * Manages activity log state including:
 * - List of all activity logs (auth + IP activities)
 * - Loading and error states
 * - Filtering capabilities by type, event, search, and date range
 * - Pagination support
 */
export const useActivityStore = defineStore('activity', () => {
  // State
  const logs = ref<AuditLog[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const eventFilter = ref<string>('')
  const typeFilter = ref<AuditLogType>('all')
  const searchQuery = ref('')
  const dateFrom = ref('')
  const dateTo = ref('')
  const pagination = ref<PaginationMeta>({
    current_page: 1,
    per_page: 20,
    total: 0,
    last_page: 1,
    auth_count: 0,
    ip_count: 0,
    type: 'all',
  })

  // Getters (Computed)
  // Helper to ensure logs.value is always an array
  const getLogsArray = (): AuditLog[] => {
    if (!Array.isArray(logs.value)) {
      console.warn('Activity store: logs.value is not an array, resetting to empty array')
      return []
    }
    return logs.value
  }

  // filteredLogs now just returns the logs array since filtering is done server-side
  const filteredLogs = computed(() => getLogsArray())

  const eventTypes = computed(() => {
    const types = new Set<string>()
    getLogsArray().forEach((log) => {
      if (log.event_type && typeof log.event_type === 'string') {
        types.add(log.event_type)
      }
    })
    return Array.from(types).sort()
  })

  const authLogs = computed(() =>
    getLogsArray().filter((log) => (log.type || 'auth') === 'auth')
  )

  const ipLogs = computed(() =>
    getLogsArray().filter((log) => log.type === 'ip')
  )

  const totalCount = computed(() => pagination.value.total)
  const hasActiveFilters = computed(() =>
    eventFilter.value !== '' ||
    typeFilter.value !== 'all' ||
    searchQuery.value !== '' ||
    dateFrom.value !== '' ||
    dateTo.value !== ''
  )

  // Actions

  /**
   * Fetch all activity logs (both auth and IP activities) with pagination and filters
   *
   * @param type - Filter by type: 'auth', 'ip', or 'all' (default: 'all')
   * @param page - Page number (default: 1)
   * @param perPage - Items per page (default: 20, max: 100)
   * @param search - Search query for event, description, user_id
   * @param from - Filter from date (ISO format)
   * @param to - Filter to date (ISO format)
   * @throws Error on fetch failure
   */
  async function fetchAllLogs(
    type: AuditLogType = 'all',
    page = 1,
    perPage = 20,
    search?: string,
    from?: string,
    to?: string
  ): Promise<void> {
    loading.value = true
    error.value = null

    // Use provided params or fall back to stored filter state
    const searchParam = search ?? searchQuery.value
    const fromParam = from ?? dateFrom.value
    const toParam = to ?? dateTo.value

    try {
      const params: Record<string, unknown> = {
        type,
        page,
        per_page: Math.min(Math.max(perPage, 1), 100),
      }

      // Add event filter if set
      if (eventFilter.value) {
        params.event = eventFilter.value
      }

      // Add search filter if provided
      if (searchParam) {
        params.search = searchParam
      }

      // Add date range filters if provided
      if (fromParam) {
        params.from = fromParam
      }
      if (toParam) {
        params.to = toParam
      }

      const { data } = await api.get<APIResponse<AuditLog[]>>('/audit/logs', { params })

      // Validate and ensure data.data is an array
      // Handle nested Laravel pagination response: { success: true, data: { data: [...], meta: {...} } }
      if (data.success && data.data && typeof data.data === 'object' && 'data' in data.data && Array.isArray(data.data.data)) {
        logs.value = data.data.data
      } else if (data.success && Array.isArray(data.data)) {
        // Direct array response: { success: true, data: [...] }
        logs.value = data.data
      } else if (data.success && !data.data) {
        // API returned success but no data
        logs.value = []
        console.warn('Activity store: API returned success but no data')
      } else if (!data.success && data.error) {
        throw new Error(data.error.message || 'API returned error')
      } else {
        // Unexpected response format
        logs.value = []
        console.warn('Activity store: Unexpected API response format', data)
      }

      // Store metadata from response (handle both direct and nested formats)
      let metaData = data.meta as Record<string, unknown> | undefined
      if (!metaData && data.data && typeof data.data === 'object' && !Array.isArray(data.data)) {
        const nestedData = data.data as Record<string, unknown>
        if ('meta' in nestedData) {
          metaData = nestedData.meta as Record<string, unknown>
        }
      }
      if (metaData) {
        pagination.value = {
          current_page: (metaData.current_page as number) ?? 1,
          per_page: (metaData.per_page as number) ?? 20,
          total: (metaData.total as number) ?? 0,
          last_page: (metaData.last_page as number) ?? 1,
          auth_count: (metaData.auth_count as number) ?? 0,
          ip_count: (metaData.ip_count as number) ?? 0,
          type: (metaData.type as AuditLogType) ?? type,
        }
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
   * @param page - Page number (default: 1)
   * @param perPage - Items per page (default: 20)
   * @throws Error on fetch failure
   */
  async function fetchAuthLogs(page = 1, perPage = 20): Promise<void> {
    await fetchAllLogs('auth', page, perPage)
  }

  /**
   * Fetch only IP management activity logs
   *
   * @param page - Page number (default: 1)
   * @param perPage - Items per page (default: 20)
   * @throws Error on fetch failure
   */
  async function fetchIPLogs(page = 1, perPage = 20): Promise<void> {
    await fetchAllLogs('ip', page, perPage)
  }

  /**
   * Set event type filter and fetch results
   *
   * @param filter - Event type to filter by
   */
  async function setEventFilter(filter: string): Promise<void> {
    eventFilter.value = filter
    await fetchAllLogs(typeFilter.value, 1, pagination.value.per_page, searchQuery.value, dateFrom.value, dateTo.value)
  }

  /**
   * Set type filter (auth, ip, or all) and fetch results
   *
   * @param type - The type filter to apply
   */
  async function setTypeFilter(type: AuditLogType): Promise<void> {
    typeFilter.value = type
    await fetchAllLogs(type, 1, pagination.value.per_page, searchQuery.value, dateFrom.value, dateTo.value)
  }

  /**
   * Set search query and fetch results
   *
   * @param search - Search query string
   */
  async function setSearch(search: string): Promise<void> {
    searchQuery.value = search
    await fetchAllLogs(typeFilter.value, 1, pagination.value.per_page, search, dateFrom.value, dateTo.value)
  }

  /**
   * Set date range filters and fetch results
   *
   * @param from - From date (ISO format)
   * @param to - To date (ISO format)
   */
  async function setDateRange(from: string, to: string): Promise<void> {
    dateFrom.value = from
    dateTo.value = to
    await fetchAllLogs(typeFilter.value, 1, pagination.value.per_page, searchQuery.value, from, to)
  }

  /**
   * Change to a specific page
   *
   * @param page - Page number to navigate to
   */
  async function goToPage(page: number): Promise<void> {
    await fetchAllLogs(typeFilter.value, page, pagination.value.per_page, searchQuery.value, dateFrom.value, dateTo.value)
  }

  /**
   * Change the number of items per page
   *
   * @param perPage - New items per page value
   */
  async function changePageSize(perPage: number): Promise<void> {
    await fetchAllLogs(typeFilter.value, 1, perPage, searchQuery.value, dateFrom.value, dateTo.value) // Reset to first page
  }

  /**
   * Refresh the current page
   */
  async function refresh(): Promise<void> {
    await fetchAllLogs(typeFilter.value, pagination.value.current_page, pagination.value.per_page, searchQuery.value, dateFrom.value, dateTo.value)
  }

  /**
   * Clear all filters and fetch results
   */
  async function clearFilters(): Promise<void> {
    eventFilter.value = ''
    typeFilter.value = 'all'
    searchQuery.value = ''
    dateFrom.value = ''
    dateTo.value = ''
    await fetchAllLogs('all', 1, pagination.value.per_page, '', '', '')
  }

  /**
   * Clear all filters (legacy alias for backward compatibility)
   */
  function clearFilter(): void {
    clearFilters()
  }

  /**
   * Clear all state
   */
  function clearState(): void {
    logs.value = []
    error.value = null
    eventFilter.value = ''
    typeFilter.value = 'all'
    searchQuery.value = ''
    dateFrom.value = ''
    dateTo.value = ''
    pagination.value = {
      current_page: 1,
      per_page: 20,
      total: 0,
      last_page: 1,
      auth_count: 0,
      ip_count: 0,
      type: 'all',
    }
  }

  return {
    // State
    logs,
    loading,
    error,
    eventFilter,
    typeFilter,
    searchQuery,
    dateFrom,
    dateTo,
    pagination,
    // Getters
    filteredLogs,
    eventTypes,
    authLogs,
    ipLogs,
    totalCount,
    hasActiveFilters,
    // Actions
    fetchAllLogs,
    fetchAuthLogs,
    fetchIPLogs,
    setEventFilter,
    setTypeFilter,
    setSearch,
    setDateRange,
    goToPage,
    changePageSize,
    refresh,
    clearFilters,
    clearFilter,
    clearState,
  }
})
