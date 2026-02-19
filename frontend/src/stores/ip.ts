/**
 * IP Store
 *
 * Pinia store for managing IP address state and operations.
 * Uses the Composition API (setup stores) pattern.
 *
 * @package Stores
 */

import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '@/api/client'
import type { IPAddress, IPHistory, IPFormData, APIResponse, IPType } from '@/types'

/**
 * Pagination metadata from API response
 */
interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
  from: number | null
  to: number | null
  ipv4_count: number
  ipv6_count: number
}

/**
 * IP Store
 *
 * Manages IP address state including:
 * - List of all IP addresses
 * - Currently selected IP
 * - IP change history
 * - CRUD operations
 * - Pagination support
 * - Search and filter support
 */
export const useIPStore = defineStore('ip', () => {
  // State
  const ips = ref<IPAddress[]>([])
  const currentIP = ref<IPAddress | null>(null)
  const history = ref<IPHistory[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref<PaginationMeta>({
    current_page: 1,
    per_page: 20,
    total: 0,
    last_page: 1,
    from: null,
    to: null,
    ipv4_count: 0,
    ipv6_count: 0,
  })

  // Filter state
  const searchQuery = ref('')
  const typeFilter = ref<IPType | ''>('')

  // Getters (Computed)
  // Helper to ensure ips.value is always an array
  const getIPArray = (): IPAddress[] => {
    if (!Array.isArray(ips.value)) {
      console.warn('IP store: ips.value is not an array, resetting to empty array')
      return []
    }
    return ips.value
  }

  const ipv4Addresses = computed(() => getIPArray().filter((ip) => ip.type === 'ipv4'))
  const ipv6Addresses = computed(() => getIPArray().filter((ip) => ip.type === 'ipv6'))
  const totalCount = computed(() => pagination.value.total)
  const hasActiveFilters = computed(() => searchQuery.value !== '' || typeFilter.value !== '')

  // Actions

  /**
   * Fetch IP addresses with pagination and optional filters
   *
   * @param page - Page number (default: 1)
   * @param perPage - Items per page (default: 20, max: 100)
   * @param search - Search query for IP, label, or comment
   * @param type - Filter by IP type (ipv4/ipv6)
   * @throws Error on fetch failure
   */
  async function fetchIPs(
    page = 1,
    perPage = 20,
    search?: string,
    type?: IPType | ''
  ): Promise<void> {
    loading.value = true
    error.value = null

    // Use provided params or fall back to stored filter state
    const searchParam = search ?? searchQuery.value
    const typeParam = type ?? typeFilter.value

    try {
      const params: Record<string, unknown> = {
        page,
        per_page: Math.min(Math.max(perPage, 1), 100),
      }

      // Add search filter if provided
      if (searchParam) {
        params.search = searchParam
      }

      // Add type filter if provided
      if (typeParam) {
        params.type = typeParam
      }

      const { data } = await api.get<APIResponse<IPAddress[]>>('/ip', { params })

      // Validate and ensure data.data is an array
      // Handle nested Laravel pagination response: { success: true, data: { data: [...], meta: {...} } }
      if (data.success && data.data && typeof data.data === 'object' && 'data' in data.data && Array.isArray(data.data.data)) {
        ips.value = data.data.data
      } else if (data.success && Array.isArray(data.data)) {
        // Direct array response: { success: true, data: [...] }
        ips.value = data.data
      } else if (data.success && !data.data) {
        // API returned success but no data
        ips.value = []
        console.warn('IP store: API returned success but no data')
      } else if (!data.success && data.error) {
        // API returned an error
        throw new Error(data.error.message || 'API returned error')
      } else {
        // Unexpected response format
        ips.value = []
        console.warn('IP store: Unexpected API response format', data)
      }
      
      // Update pagination metadata if available (handle both direct and nested formats)
      let metaData = data.meta
      // Handle nested Laravel pagination: data.data is an object with meta property
      if (!metaData && data.data && typeof data.data === 'object' && !Array.isArray(data.data)) {
        metaData = (data.data as { meta?: typeof data.meta }).meta
      }
      if (metaData) {
        const meta = metaData as Record<string, unknown>
        pagination.value = {
          current_page: (meta.current_page as number) ?? 1,
          per_page: (meta.per_page as number) ?? 20,
          total: (meta.total as number) ?? 0,
          last_page: (meta.last_page as number) ?? 1,
          from: (meta.from as number | null) ?? null,
          to: (meta.to as number | null) ?? null,
          ipv4_count: (meta.ipv4_count as number) ?? 0,
          ipv6_count: (meta.ipv6_count as number) ?? 0,
        }
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Failed to fetch IPs'
      error.value = message
      throw new Error(message)
    } finally {
      loading.value = false
    }
  }

  /**
   * Create a new IP address
   *
   * @param ipData - Partial IP address data
   * @throws Error on creation failure
   */
  async function createIP(ipData: Partial<IPAddress> | IPFormData): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await api.post('/ip', ipData)
      await fetchIPs()
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Failed to create IP'
      error.value = message
      throw new Error(message)
    } finally {
      loading.value = false
    }
  }

  /**
   * Update an existing IP address
   *
   * @param id - IP address UUID
   * @param data - Partial IP data to update
   * @throws Error on update failure
   */
  async function updateIP(id: string, data: Partial<IPAddress>): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await api.put(`/ip/${id}`, data)
      await fetchIPs()

      // Update currentIP if it's the one being updated
      if (currentIP.value?.id === id) {
        currentIP.value = { ...currentIP.value, ...data }
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Failed to update IP'
      error.value = message
      throw new Error(message)
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete an IP address
   *
   * @param id - IP address UUID
   * @throws Error on deletion failure
   */
  async function deleteIP(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      await api.delete(`/ip/${id}`)
      await fetchIPs()

      // Clear currentIP if it's the one being deleted
      if (currentIP.value?.id === id) {
        currentIP.value = null
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Failed to delete IP'
      error.value = message
      throw new Error(message)
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch history for a specific IP address
   *
   * @param id - IP address UUID
   * @throws Error on fetch failure
   */
  async function fetchHistory(id: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const { data } = await api.get<APIResponse<IPHistory[]>>(`/ip/${id}/history`)

      // Validate and ensure data.data is an array
      if (data.success && Array.isArray(data.data)) {
        history.value = data.data
      } else if (data.success && !data.data) {
        // API returned success but no data
        history.value = []
      } else if (!data.success && data.error) {
        throw new Error(data.error.message || 'API returned error')
      } else {
        // Unexpected response format
        history.value = []
        console.warn('IP store: Unexpected history API response format', data)
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Failed to fetch history'
      error.value = message
      throw new Error(message)
    } finally {
      loading.value = false
    }
  }

  /**
   * Set the current IP
   *
   * @param ip - IP address or null to clear
   */
  function setCurrentIP(ip: IPAddress | null): void {
    currentIP.value = ip
  }

  /**
   * Change to a specific page
   *
   * @param page - Page number to navigate to
   */
  async function goToPage(page: number): Promise<void> {
    await fetchIPs(page, pagination.value.per_page, searchQuery.value, typeFilter.value)
  }

  /**
   * Change the number of items per page
   *
   * @param perPage - New items per page value
   */
  async function changePageSize(perPage: number): Promise<void> {
    await fetchIPs(1, perPage, searchQuery.value, typeFilter.value) // Reset to first page when changing page size
  }

  /**
   * Refresh the current page
   */
  async function refresh(): Promise<void> {
    await fetchIPs(pagination.value.current_page, pagination.value.per_page, searchQuery.value, typeFilter.value)
  }

  /**
   * Set search query and fetch results
   *
   * @param search - Search query string
   */
  async function setSearch(search: string): Promise<void> {
    searchQuery.value = search
    await fetchIPs(1, pagination.value.per_page, search, typeFilter.value)
  }

  /**
   * Set type filter and fetch results
   *
   * @param type - IP type filter (ipv4/ipv6 or empty for all)
   */
  async function setTypeFilter(type: IPType | ''): Promise<void> {
    typeFilter.value = type
    await fetchIPs(1, pagination.value.per_page, searchQuery.value, type)
  }

  /**
   * Clear all filters and fetch results
   */
  async function clearFilters(): Promise<void> {
    searchQuery.value = ''
    typeFilter.value = ''
    await fetchIPs(1, pagination.value.per_page, '', '')
  }

  /**
   * Clear all state
   */
  function clearState(): void {
    ips.value = []
    currentIP.value = null
    history.value = []
    error.value = null
    searchQuery.value = ''
    typeFilter.value = ''
    pagination.value = {
      current_page: 1,
      per_page: 20,
      total: 0,
      last_page: 1,
      from: null,
      to: null,
      ipv4_count: 0,
      ipv6_count: 0,
    }
  }

  return {
    // State
    ips,
    currentIP,
    history,
    loading,
    error,
    pagination,
    searchQuery,
    typeFilter,
    // Getters
    ipv4Addresses,
    ipv6Addresses,
    totalCount,
    hasActiveFilters,
    // Actions
    fetchIPs,
    createIP,
    updateIP,
    deleteIP,
    fetchHistory,
    setCurrentIP,
    goToPage,
    changePageSize,
    refresh,
    setSearch,
    setTypeFilter,
    clearFilters,
    clearState,
  }
})
