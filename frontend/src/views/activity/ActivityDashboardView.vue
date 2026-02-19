<script setup lang="ts">
/**
 * Activity Dashboard View
 *
 * Displays system activity logs for super administrators.
 * Includes filtering by event type and detailed log information.
 *
 * @package Views/Activity
 */

import { ref, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useActivityStore } from '@/stores/activity'
import ActivityTable from '@/components/tables/ActivityTable.vue'
import type { AuditLog, AuditLogType } from '@/types'
import {
  Shield,
  Loader2,
  ScrollText,
  Filter,
  X,
  RefreshCw,
  Ban,
  Home,
  Clock,
  User,
  FileText,
  Code,
  Server,
} from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const authStore = useAuthStore()
const activityStore = useActivityStore()

const selectedFilter = ref('')
const selectedTypeFilter = ref<AuditLogType>('all')
const selectedLog = ref<AuditLog | null>(null)

function formatEventType(eventType: string | undefined | null): string {
  if (!eventType || typeof eventType !== 'string') {
    return 'Unknown Event'
  }
  return eventType
    .split('.')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

function getEventColor(eventType: string | undefined | null): string {
  if (!eventType || typeof eventType !== 'string') {
    return 'bg-gray-100 text-gray-700'
  }
  if (eventType.includes('login')) return 'bg-emerald-100 text-emerald-700'
  if (eventType.includes('logout')) return 'bg-amber-100 text-amber-700'
  if (eventType.includes('created')) return 'bg-blue-100 text-blue-700'
  if (eventType.includes('updated')) return 'bg-purple-100 text-purple-700'
  if (eventType.includes('deleted')) return 'bg-rose-100 text-rose-700'
  return 'bg-gray-100 text-gray-700'
}

function formatFullDate(dateString: string | undefined | null): string {
  if (!dateString || typeof dateString !== 'string') {
    return 'Invalid Date'
  }
  const date = new Date(dateString)
  if (isNaN(date.getTime())) {
    return 'Invalid Date'
  }
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

function applyFilter(): void {
  activityStore.setEventFilter(selectedFilter.value)
}

function clearFilter(): void {
  selectedFilter.value = ''
  selectedTypeFilter.value = 'all'
  activityStore.clearFilter()
}

function showDetails(log: AuditLog): void {
  selectedLog.value = log
}

async function refreshLogs(): Promise<void> {
  await activityStore.fetchAllLogs(selectedTypeFilter.value)
}

watch(selectedTypeFilter, () => {
  refreshLogs()
})

onMounted(() => {
  if (authStore.isSuperAdmin) {
    activityStore.fetchAllLogs()
  }
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <div class="flex items-center gap-3">
          <h1 class="text-2xl font-bold tracking-tight">Activity Dashboard</h1>
          <span
            v-if="authStore.isSuperAdmin"
            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-700"
          >
            <Shield class="w-3 h-3" />
            Super Admin
          </span>
        </div>
        <p class="text-muted-foreground">View system activity logs and user activity</p>
      </div>
    </div>

    <!-- Access Denied -->
    <div
      v-if="!authStore.isSuperAdmin"
      class="flex flex-col items-center justify-center py-12 rounded-xl border bg-card"
    >
      <div class="w-16 h-16 rounded-full bg-destructive/10 flex items-center justify-center mb-4">
        <Ban class="w-8 h-8 text-destructive" />
      </div>
      <h3 class="text-lg font-medium mb-1">Access Denied</h3>
      <p class="text-muted-foreground text-sm mb-4">This page is only accessible to super administrators</p>
      <router-link
        to="/dashboard"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
      >
        <Home class="w-4 h-4" />
        Back to Dashboard
      </router-link>
    </div>

    <!-- Activity Content -->
    <template v-else>
      <!-- Filters -->
      <div class="flex flex-wrap items-end gap-4 p-4 rounded-xl border bg-card">
        <div class="min-w-[150px]">
          <label class="text-sm font-medium mb-2 block">Source Type</label>
          <div class="relative">
            <Server class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <select
              v-model="selectedTypeFilter"
              class="w-full rounded-lg border bg-background pl-9 pr-4 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            >
              <option value="all">All Sources</option>
              <option value="auth">Authentication</option>
              <option value="ip">IP Management</option>
            </select>
          </div>
        </div>
        <div class="flex-1 min-w-[200px]">
          <label class="text-sm font-medium mb-2 block">Filter by Event Type</label>
          <div class="relative">
            <Filter class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <select
              v-model="selectedFilter"
              class="w-full rounded-lg border bg-background pl-9 pr-4 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              @change="applyFilter"
            >
              <option value="">All Events</option>
              <option v-for="type in activityStore.eventTypes" :key="type" :value="type">
                {{ formatEventType(type) }}
              </option>
            </select>
          </div>
        </div>
        <button
          v-if="selectedFilter || selectedTypeFilter !== 'all'"
          class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium hover:bg-muted transition-colors"
          @click="clearFilter"
        >
          <X class="w-4 h-4" />
          Clear
        </button>
        <button
          class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
          :disabled="activityStore.loading"
          @click="refreshLogs"
        >
          <RefreshCw :class="cn('w-4 h-4', activityStore.loading && 'animate-spin')" />
          {{ activityStore.loading ? 'Refreshing...' : 'Refresh' }}
        </button>
      </div>

      <!-- Loading State -->
      <div
        v-if="activityStore.loading && !activityStore.logs.length"
        class="flex flex-col items-center justify-center py-12 rounded-xl border bg-card"
      >
        <Loader2 class="w-8 h-8 animate-spin text-primary mb-3" />
        <p class="text-muted-foreground">Loading activity logs...</p>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="!activityStore.filteredLogs.length"
        class="flex flex-col items-center justify-center py-12 rounded-xl border bg-card"
      >
        <ScrollText class="w-12 h-12 text-muted-foreground/50 mb-4" />
        <h3 class="text-lg font-medium mb-1">No Activity Logs</h3>
        <p class="text-muted-foreground text-sm">There are no activity logs to display</p>
      </div>

      <!-- Activity Table -->
      <ActivityTable
        v-else
        :logs="activityStore.filteredLogs"
        @view="showDetails"
      />

      <!-- Stats Summary -->
      <div v-if="activityStore.logs.length" class="text-sm text-muted-foreground">
        Showing {{ activityStore.filteredLogs.length }} of {{ activityStore.logs.length }} activity log entries
      </div>
    </template>

    <!-- Details Modal -->
    <div
      v-if="selectedLog"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
      @click.self="selectedLog = null"
    >
      <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-xl bg-card border shadow-lg">
        <div class="flex items-center justify-between p-4 border-b">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
              <FileText class="w-5 h-5 text-primary" />
            </div>
            <div>
              <h3 class="font-semibold">Activity Log Details</h3>
              <p class="text-sm text-muted-foreground">Complete log information</p>
            </div>
          </div>
          <button
            class="p-2 rounded-lg hover:bg-muted transition-colors"
            @click="selectedLog = null"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <div class="p-4 space-y-4">
          <!-- Details Grid -->
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border bg-muted/30 p-3">
              <div class="flex items-center gap-2 text-muted-foreground mb-1">
                <Clock class="w-4 h-4" />
                <span class="text-xs font-medium uppercase">Timestamp</span>
              </div>
              <p class="font-mono text-sm">{{ formatFullDate(selectedLog.created_at) }}</p>
            </div>

            <div class="rounded-lg border bg-muted/30 p-3">
              <div class="flex items-center gap-2 text-muted-foreground mb-1">
                <User class="w-4 h-4" />
                <span class="text-xs font-medium uppercase">User ID</span>
              </div>
              <p class="font-mono text-sm break-all">{{ selectedLog.user_id }}</p>
            </div>

            <div class="rounded-lg border bg-muted/30 p-3">
              <div class="flex items-center gap-2 text-muted-foreground mb-1">
                <FileText class="w-4 h-4" />
                <span class="text-xs font-medium uppercase">Event Type</span>
              </div>
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="getEventColor(selectedLog.event_type)"
              >
                {{ selectedLog.event_type }}
              </span>
            </div>

            <div class="rounded-lg border bg-muted/30 p-3">
              <div class="flex items-center gap-2 text-muted-foreground mb-1">
                <Code class="w-4 h-4" />
                <span class="text-xs font-medium uppercase">Entity</span>
              </div>
              <p class="font-mono text-sm">{{ selectedLog.entity_type }}: {{ selectedLog.entity_id }}</p>
            </div>
          </div>

          <!-- Session ID -->
          <div class="rounded-lg border bg-muted/30 p-3">
            <div class="flex items-center gap-2 text-muted-foreground mb-1">
              <Code class="w-4 h-4" />
              <span class="text-xs font-medium uppercase">Session ID</span>
            </div>
            <p class="font-mono text-sm break-all">{{ selectedLog.session_id ?? '--' }}</p>
          </div>

          <!-- Metadata -->
          <div class="rounded-lg border bg-muted/30 p-3">
            <div class="flex items-center gap-2 text-muted-foreground mb-2">
              <Code class="w-4 h-4" />
              <span class="text-xs font-medium uppercase">Metadata</span>
            </div>
            <pre class="font-mono text-xs bg-background rounded-lg p-3 overflow-x-auto">{{ JSON.stringify(selectedLog.metadata, null, 2) }}</pre>
          </div>
        </div>

        <div class="flex items-center justify-end p-4 border-t bg-muted/50">
          <button
            class="inline-flex items-center justify-center rounded-lg border bg-background px-4 py-2 text-sm font-medium hover:bg-muted transition-colors"
            @click="selectedLog = null"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>