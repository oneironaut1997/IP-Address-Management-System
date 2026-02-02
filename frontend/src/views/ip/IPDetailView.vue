<script setup lang="ts">
/**
 * IP Detail View
 *
 * Displays detailed information about a specific IP address
 * with options to edit or view history.
 *
 * @package Views/IP
 */

import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useIPStore } from '@/stores/ip'
import EditIPForm from '@/components/forms/EditIPForm.vue'
import type { IPAddress } from '@/types'
import {
  ArrowLeft,
  History,
  Pencil,
  Loader2,
  Globe,
  User,
  Calendar,
  Clock,
  FileText,
} from 'lucide-vue-next'

const route = useRoute()
const authStore = useAuthStore()
const ipStore = useIPStore()

const showEditModal = ref(false)

const ip = computed<IPAddress | null>(() => {
  if (ipStore.currentIP?.id === route.params.id) {
    return ipStore.currentIP
  }
  return ipStore.ips.find((i) => i.id === route.params.id) || null
})

const isOwner = computed(() => {
  if (!ip.value || !authStore.user) return false
  return ip.value.user_id === authStore.user.id
})

const canEdit = computed(() => {
  return isOwner.value || authStore.isSuperAdmin
})

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function handleUpdated(): void {
  showEditModal.value = false
}

onMounted(() => {
  if (ip.value) {
    ipStore.setCurrentIP(ip.value)
  } else if (!ipStore.ips.length) {
    ipStore.fetchIPs()
  }
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="flex items-center gap-4">
        <router-link
          to="/ip"
          class="inline-flex items-center justify-center w-10 h-10 rounded-lg border hover:bg-muted transition-colors"
        >
          <ArrowLeft class="w-5 h-5" />
        </router-link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight">IP Address Details</h1>
          <p class="text-muted-foreground">View and manage IP address information</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <router-link
          :to="`/ip/${route.params.id}/history`"
          class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2.5 text-sm font-medium hover:bg-muted transition-colors"
        >
          <History class="w-4 h-4" />
          View History
        </router-link>
        <button
          v-if="canEdit"
          class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
          @click="showEditModal = true"
        >
          <Pencil class="w-4 h-4" />
          Edit
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div
      v-if="ipStore.loading"
      class="flex flex-col items-center justify-center py-12 rounded-xl border bg-card"
    >
      <Loader2 class="w-8 h-8 animate-spin text-primary mb-3" />
      <p class="text-muted-foreground">Loading IP details...</p>
    </div>

    <!-- IP Details -->
    <div v-else-if="ip" class="space-y-6">
      <!-- IP Header Card -->
      <div class="rounded-xl border bg-card p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
          <div class="w-16 h-16 rounded-2xl bg-primary/10 flex items-center justify-center">
            <Globe class="w-8 h-8 text-primary" />
          </div>
          <div class="flex-1">
            <div class="flex flex-wrap items-center gap-3">
              <h2 class="text-2xl font-mono font-bold">{{ ip.ip_address }}</h2>
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="ip.type === 'ipv4' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'"
              >
                {{ ip.type.toUpperCase() }}
              </span>
            </div>
            <p class="text-muted-foreground mt-1">{{ ip.label }}</p>
          </div>
        </div>
      </div>

      <!-- Details Grid -->
      <div class="grid gap-4 sm:grid-cols-2">
        <!-- Owner -->
        <div class="rounded-xl border bg-card p-6">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
              <User class="w-5 h-5 text-primary" />
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Owner</p>
              <p class="font-medium">{{ isOwner ? 'You' : 'Another user' }}</p>
            </div>
          </div>
          <span
            v-if="isOwner"
            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-primary/10 text-primary"
          >
            Owner
          </span>
        </div>

        <!-- Created -->
        <div class="rounded-xl border bg-card p-6">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
              <Calendar class="w-5 h-5 text-primary" />
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Created</p>
              <p class="font-medium">{{ formatDate(ip.created_at) }}</p>
            </div>
          </div>
        </div>

        <!-- Last Updated -->
        <div class="rounded-xl border bg-card p-6">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
              <Clock class="w-5 h-5 text-primary" />
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Last Updated</p>
              <p class="font-medium">{{ formatDate(ip.updated_at) }}</p>
            </div>
          </div>
        </div>

        <!-- Comment -->
        <div v-if="ip.comment" class="rounded-xl border bg-card p-6">
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
              <FileText class="w-5 h-5 text-primary" />
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Comment</p>
            </div>
          </div>
          <p class="text-sm">{{ ip.comment }}</p>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="rounded-xl border bg-muted/30 p-6">
        <h3 class="font-semibold mb-3">Quick Actions</h3>
        <div class="flex flex-wrap gap-2">
          <router-link
            :to="`/ip/${ip.id}/history`"
            class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium hover:bg-muted transition-colors"
          >
            <History class="w-4 h-4" />
            View Full History
          </router-link>
        </div>
      </div>
    </div>

    <!-- Not Found -->
    <div
      v-else
      class="flex flex-col items-center justify-center py-12 rounded-xl border bg-card"
    >
      <div class="w-16 h-16 rounded-full bg-muted flex items-center justify-center mb-4">
        <Globe class="w-8 h-8 text-muted-foreground" />
      </div>
      <h3 class="text-lg font-medium mb-1">IP Address Not Found</h3>
      <p class="text-muted-foreground text-sm mb-4">The requested IP address could not be found</p>
      <router-link
        to="/ip"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
      >
        <ArrowLeft class="w-4 h-4" />
        Back to IP List
      </router-link>
    </div>

    <!-- Edit Modal -->
    <EditIPForm
      v-if="showEditModal"
      :ip="ip"
      @close="showEditModal = false"
      @updated="handleUpdated"
    />
  </div>
</template>
