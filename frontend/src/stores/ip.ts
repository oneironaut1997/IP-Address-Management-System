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
import type { IPAddress, IPHistory, IPFormData, APIResponse } from '@/types'

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
  })

  // Getters (Computed)
  const ipv4Addresses = computed(() => ips.value.filter((ip) => ip.type === 'ipv4'))
  const ipv6Addresses = computed(() => ips.value.filter((ip) => ip.type === 'ipv6'))
  const totalCount = computed(() => pagination.value.total)

  // Actions

  /**
   * Fetch IP addresses with pagination
   *
   * @param page - Page number (default: 1)
   * @param perPage - Items per page (default: 20, max: 100)
   * @throws Error on fetch failure
   */
  async function fetchIPs(page = 1, perPage = 20): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const { data } = await api.get<APIResponse<IPAddress[]>>('/ip', {
        params: {
          page,
          per_page: Math.min(Math.max(perPage, 1), 100),
        },
      })
      ips.value = data.data ?? []
      
      // Update pagination metadata if available
      if (data.meta) {
        pagination.value = {
          current_page: data.meta.current_page ?? 1,
          per_page: data.meta.per_page ?? 20,
          total: data.meta.total ?? 0,
          last_page: data.meta.last_page ?? 1,
          from: data.meta.from ?? null,
          to: data.meta.to ?? null,
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
      history.value = data.data ?? []
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
   * Clear all state
   */
  function clearState(): void {
    ips.value = []
    currentIP.value = null
    history.value = []
    error.value = null
  }

  return {
    // State
    ips,
    currentIP,
    history,
    loading,
    error,
    pagination,
    // Getters
    ipv4Addresses,
    ipv6Addresses,
    totalCount,
    // Actions
    fetchIPs,
    createIP,
    updateIP,
    deleteIP,
    fetchHistory,
    setCurrentIP,
    clearState,
  }
})
