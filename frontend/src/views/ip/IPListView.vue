<script setup lang="ts">
/**
 * IP List View
 *
 * Displays a table of all IP addresses with actions for viewing,
 * editing (if owner/admin), and deleting (admin only).
 *
 * @package Views/IP
 */

import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useIPStore } from '@/stores/ip'
import AddIPForm from '@/components/forms/AddIPForm.vue'
import EditIPForm from '@/components/forms/EditIPForm.vue'
import type { IPAddress } from '@/types'
import {
  Plus,
  Eye,
  History,
  Pencil,
  Trash2,
  Loader2,
  Globe,
  Server,
  AlertTriangle,
  X,
} from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const authStore = useAuthStore()
const ipStore = useIPStore()

const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const selectedIP = ref<IPAddress | null>(null)
const ipToDelete = ref<IPAddress | null>(null)

function isCurrentUser(userId: string): boolean {
  return authStore.user?.id === userId
}

function canEdit(ip: IPAddress): boolean {
  return isCurrentUser(ip.user_id) || authStore.isSuperAdmin
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

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
    showDeleteModal.value = false
    ipToDelete.value = null
  } catch {
    // Error is handled by store
  }
}

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
            <p class="text-2xl font-bold">{{ ipStore.ipv4Addresses.length }}</p>
            <p class="text-sm text-muted-foreground">IPv4</p>
          </div>
        </div>
      </div>
      <div class="rounded-xl border bg-card p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
            <Server class="w-5 h-5 text-blue-500" />
          </div>
          <div>
            <p class="text-2xl font-bold">{{ ipStore.ipv6Addresses.length }}</p>
            <p class="text-sm text-muted-foreground">IPv6</p>
          </div>
        </div>
      </div>
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
      <h3 class="text-lg font-medium mb-1">No IP Addresses</h3>
      <p class="text-muted-foreground text-sm mb-4">Get started by adding your first IP address</p>
      <button
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
        @click="showAddModal = true"
      >
        <Plus class="w-4 h-4" />
        Add IP Address
      </button>
    </div>

    <!-- IP Table -->
    <div v-else class="rounded-xl border bg-card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b bg-muted/50">
              <th class="px-4 py-3 text-left font-medium text-muted-foreground">IP Address</th>
              <th class="px-4 py-3 text-left font-medium text-muted-foreground">Label</th>
              <th class="px-4 py-3 text-left font-medium text-muted-foreground">Type</th>
              <th class="px-4 py-3 text-left font-medium text-muted-foreground">Owner</th>
              <th class="px-4 py-3 text-left font-medium text-muted-foreground">Created</th>
              <th class="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr
              v-for="ip in ipStore.ips"
              :key="ip.id"
              class="hover:bg-muted/50 transition-colors"
            >
              <td class="px-4 py-3">
                <router-link
                  :to="`/ip/${ip.id}`"
                  class="font-mono font-medium text-primary hover:underline"
                >
                  {{ ip.ip_address }}
                </router-link>
              </td>
              <td class="px-4 py-3">{{ ip.label }}</td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                  :class="ip.type === 'ipv4' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'"
                >
                  {{ ip.type.toUpperCase() }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="cn(
                    'text-sm',
                    isCurrentUser(ip.user_id) ? 'text-primary font-medium' : 'text-muted-foreground'
                  )"
                >
                  {{ isCurrentUser(ip.user_id) ? 'You' : 'User' }}
                </span>
              </td>
              <td class="px-4 py-3 text-muted-foreground">{{ formatDate(ip.created_at) }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <router-link
                    :to="`/ip/${ip.id}`"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-muted transition-colors"
                    title="View Details"
                  >
                    <Eye class="w-4 h-4 text-muted-foreground" />
                  </router-link>
                  <router-link
                    :to="`/ip/${ip.id}/history`"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-muted transition-colors"
                    title="View History"
                  >
                    <History class="w-4 h-4 text-muted-foreground" />
                  </router-link>
                  <button
                    v-if="canEdit(ip)"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-muted transition-colors"
                    title="Edit"
                    @click="openEditModal(ip)"
                  >
                    <Pencil class="w-4 h-4 text-muted-foreground" />
                  </button>
                  <button
                    v-if="authStore.isSuperAdmin"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-destructive/10 transition-colors"
                    title="Delete"
                    @click="confirmDelete(ip)"
                  >
                    <Trash2 class="w-4 h-4 text-destructive" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

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
