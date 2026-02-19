<script setup lang="ts">
/**
 * IP List View
 *
 * Displays a paginated table of all IP addresses with actions for viewing,
 * editing (if owner/admin), and deleting (admin only).
 * Includes search and filter functionality (backend-side).
 *
 * @package Views/IP
 */

import { ref, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { useIPStore } from '@/stores/ip'
import AddIPForm from '@/components/forms/AddIPForm.vue'
import EditIPForm from '@/components/forms/EditIPForm.vue'
import IPTable from '@/components/tables/IPTable.vue'
import PaginationControls from '@/components/common/PaginationControls.vue'
import type { IPAddress } from '@/types'
import {
  Plus,
  Loader2,
  Globe,
  Server,
  AlertTriangle,
  X,
  Trash2,
  Search,
  RefreshCw,
} from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const ipStore = useIPStore()
const toast = useToast()

const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const selectedIP = ref<IPAddress | null>(null)
const ipToDelete = ref<IPAddress | null>(null)

// Local filter state for debounced search
const localSearchQuery = ref('')
let searchTimeout: ReturnType<typeof setTimeout> | null = null

function openEditModal(ip: IPAddress): void {
  selectedIP.value = ip
  showEditModal.value = true
}

function confirmDelete(ip: IPAddress): void {
  ipToDelete.value = ip
  showDeleteModal.value = true
}

function handleCreated(): void {
  // Refresh is handled by store
}

function handleUpdated(): void {
  selectedIP.value = null
}

async function handleDelete(): Promise<void> {
  if (!ipToDelete.value) return

  try {
    await ipStore.deleteIP(ipToDelete.value.id)
    toast.success('IP address deleted successfully')
    showDeleteModal.value = false
    ipToDelete.value = null
  } catch {
    toast.error('Failed to delete IP address')
  }
}

/**
 * Handle page change from pagination component
 */
function handlePageChange(page: number): void {
  ipStore.goToPage(page)
}

/**
 * Handle page size change from pagination component
 */
function handlePageSizeChange(pageSize: number): void {
  ipStore.changePageSize(pageSize)
}

/**
 * Handle search input with debounce
 */
function handleSearchInput(): void {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  searchTimeout = setTimeout(() => {
    ipStore.setSearch(localSearchQuery.value)
  }, 300)
}

/**
 * Handle type filter change
 */
async function handleTypeFilterChange(): Promise<void> {
  await ipStore.setTypeFilter(ipStore.typeFilter)
}

/**
 * Clear all filters
 */
async function clearFilters(): Promise<void> {
  localSearchQuery.value = ''
  await ipStore.clearFilters()
}

/**
 * Refresh IP list
 */
async function refreshIPs(): Promise<void> {
  await ipStore.refresh()
}

// Watch for type filter changes
watch(() => ipStore.typeFilter, () => {
  handleTypeFilterChange()
})

onMounted(() => {
  ipStore.fetchIPs()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">IP Addresses</h1>
        <p class="text-muted-foreground">Manage and view all IP addresses in the system</p>
      </div>
      <button
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground ring-offset-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
        @click="showAddModal = true"
      >
        <Plus class="w-4 h-4" />
        Add IP Address
      </button>
    </div>

    <!-- Stats -->
    <div class="grid gap-4 sm:grid-cols-3">
      <div class="rounded-xl border bg-card p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
            <Globe class="w-5 h-5 text-primary" />
          </div>
          <div>
            <p class="text-2xl font-bold">{{ ipStore.totalCount }}</p>
            <p class="text-sm text-muted-foreground">Total IPs</p>
          </div>
        </div>
      </div>
      <div class="rounded-xl border bg-card p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
            <Globe class="w-5 h-5 text-emerald-500" />
          </div>
          <div>
            <p class="text-2xl font-bold">{{ ipStore.pagination.ipv4_count }}</p>
            <p class="text-sm text-muted-foreground">IPv4 Addresses</p>
          </div>
        </div>
      </div>
      <div class="rounded-xl border bg-card p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
            <Server class="w-5 h-5 text-blue-500" />
          </div>
          <div>
            <p class="text-2xl font-bold">{{ ipStore.pagination.ipv6_count }}</p>
            <p class="text-sm text-muted-foreground">IPv6 Addresses</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-end gap-4 p-4 rounded-xl border bg-card">
      <!-- Search Input -->
      <div class="flex-1 min-w-[200px]">
        <label class="text-sm font-medium mb-2 block">Search</label>
        <div class="relative">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <input
            v-model="localSearchQuery"
            type="text"
            placeholder="Search IP, label, or comment..."
            class="w-full rounded-lg border bg-background pl-9 pr-4 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            @input="handleSearchInput"
          />
        </div>
      </div>

      <!-- Type Filter -->
      <div class="min-w-[150px]">
        <label class="text-sm font-medium mb-2 block">IP Type</label>
        <div class="relative">
          <Server class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <select
            v-model="ipStore.typeFilter"
            class="w-full rounded-lg border bg-background pl-9 pr-4 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          >
            <option value="">All Types</option>
            <option value="ipv4">IPv4</option>
            <option value="ipv6">IPv6</option>
          </select>
        </div>
      </div>

      <!-- Clear Filters Button -->
      <button
        v-if="ipStore.hasActiveFilters"
        class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium hover:bg-muted transition-colors"
        @click="clearFilters"
      >
        <X class="w-4 h-4" />
        Clear
      </button>

      <!-- Refresh Button -->
      <button
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
        :disabled="ipStore.loading"
        @click="refreshIPs"
      >
        <RefreshCw :class="cn('w-4 h-4', ipStore.loading && 'animate-spin')" />
        {{ ipStore.loading ? 'Refreshing...' : 'Refresh' }}
      </button>
    </div>

    <!-- Loading State -->
    <div
      v-if="ipStore.loading && !ipStore.ips.length"
      class="flex flex-col items-center justify-center py-12 rounded-xl border bg-card"
    >
      <Loader2 class="w-8 h-8 animate-spin text-primary mb-3" />
      <p class="text-muted-foreground">Loading IP addresses...</p>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!ipStore.ips.length"
      class="flex flex-col items-center justify-center py-12 rounded-xl border bg-card"
    >
      <Globe class="w-12 h-12 text-muted-foreground/50 mb-4" />
      <h3 class="text-lg font-medium mb-1">No IP Addresses Found</h3>
      <p class="text-muted-foreground text-sm mb-4">
        {{ ipStore.hasActiveFilters ? 'Try adjusting your search or filters' : 'Get started by adding your first IP address' }}
      </p>
      <button
        v-if="!ipStore.hasActiveFilters"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
        @click="showAddModal = true"
      >
        <Plus class="w-4 h-4" />
        Add IP Address
      </button>
      <button
        v-else
        class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium hover:bg-muted transition-colors"
        @click="clearFilters"
      >
        <X class="w-4 h-4" />
        Clear Filters
      </button>
    </div>

    <!-- IP Table with Pagination -->
    <template v-else>
      <IPTable
        :ips="ipStore.ips"
        @edit="openEditModal"
        @delete="confirmDelete"
      />

      <!-- Pagination Controls -->
      <PaginationControls
        :current-page="ipStore.pagination.current_page"
        :total-items="ipStore.pagination.total"
        :per-page="ipStore.pagination.per_page"
        @page-change="handlePageChange"
        @page-size-change="handlePageSizeChange"
      />
    </template>

    <!-- Add IP Modal -->
    <AddIPForm
      v-if="showAddModal"
      @close="showAddModal = false"
      @created="handleCreated"
    />

    <!-- Edit IP Modal -->
    <EditIPForm
      v-if="showEditModal"
      :ip="selectedIP"
      @close="showEditModal = false"
      @updated="handleUpdated"
    />

    <!-- Delete Confirmation Modal -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
      @click.self="showDeleteModal = false"
    >
      <div class="w-full max-w-md rounded-xl bg-card border shadow-lg">
        <div class="flex items-center justify-between p-4 border-b">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-destructive/10 flex items-center justify-center">
              <AlertTriangle class="w-5 h-5 text-destructive" />
            </div>
            <div>
              <h3 class="font-semibold">Confirm Delete</h3>
              <p class="text-sm text-muted-foreground">This action cannot be undone</p>
            </div>
          </div>
          <button
            class="p-2 rounded-lg hover:bg-muted transition-colors"
            @click="showDeleteModal = false"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
        <div class="p-4">
          <p class="text-sm text-muted-foreground">
            Are you sure you want to delete the IP address
            <span class="font-mono font-medium text-foreground">{{ ipToDelete?.ip_address }}</span>?
          </p>
        </div>
        <div class="flex items-center justify-end gap-2 p-4 border-t bg-muted/50">
          <button
            class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium border bg-background hover:bg-muted transition-colors"
            :disabled="ipStore.loading"
            @click="showDeleteModal = false"
          >
            Cancel
          </button>
          <button
            class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium bg-destructive text-destructive-foreground hover:bg-destructive/90 transition-colors"
            :disabled="ipStore.loading"
            @click="handleDelete"
          >
            <Loader2 v-if="ipStore.loading" class="w-4 h-4 animate-spin" />
            <Trash2 v-else class="w-4 h-4" />
            {{ ipStore.loading ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
