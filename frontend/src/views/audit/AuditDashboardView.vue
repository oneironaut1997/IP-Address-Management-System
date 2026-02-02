<script setup lang="ts">
/**
 * Audit Dashboard View
 *
 * Displays system audit logs for super administrators.
 * Includes filtering by event type and detailed log information.
 *
 * @package Views/Audit
 */

import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useAuditStore } from '@/stores/audit'
import type { AuditLog } from '@/types'
import {
  Shield,
  Loader2,
  ScrollText,
  Filter,
  X,
  RefreshCw,
  Eye,
  Ban,
  Home,
  Clock,
  User,
  FileText,
  Code,
} from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const authStore = useAuthStore()
const auditStore = useAuditStore()

const selectedFilter = ref('')
const selectedLog = ref<AuditLog | null>(null)

function formatEventType(eventType: string): string {
  return eventType
    .split('.')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

function getEventColor(eventType: string): string {
  if (eventType.includes('login')) return 'bg-emerald-100 text-emerald-700'
  if (eventType.includes('logout')) return 'bg-amber-100 text-amber-700'
  if (eventType.includes('created')) return 'bg-blue-100 text-blue-700'
  if (eventType.includes('updated')) return 'bg-purple-100 text-purple-700'
  if (eventType.includes('deleted')) return 'bg-rose-100 text-rose-700'
  return 'bg-gray-100 text-gray-700'
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatFullDate(dateString: string): string {
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

function truncate(str: string, length: number): string {
  if (str.length <= length) return str
  return str.substring(0, length) + '...'
}

function applyFilter(): void {
  auditStore.setEventFilter(selectedFilter.value)
}

function clearFilter(): void {
  selectedFilter.value = ''
  auditStore.clearFilter()
}

function showDetails(log: AuditLog): void {
  selectedLog.value = log
}

async function refreshLogs(): Promise<void> {
  await auditStore.fetchAllLogs()
}

onMounted(() => {
  if (authStore.isSuperAdmin) {
    auditStore.fetchAllLogs()
  }
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <div class="flex items-center gap-3">
          <h1 class="text-2xl font-bold tracking-tight">Audit Dashboard</h1>
          <span
            v-if="authStore.isSuperAdmin"
            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-700"
          >
            <Shield class="w-3 h-3" />
            Super Admin
          </span>
        </div>
        <p class="text-muted-foreground">View system audit logs and user activity</p>
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

    <!-- Audit Content -->
    <template v-else>
      <!-- Filters -->
      <div class="flex flex-wrap items-end gap-4 p-4 rounded-xl border bg-card">
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
              <option v-for="type in auditStore.eventTypes" :key="type" :value="type">
                {{ formatEventType(type) }}
              </option>
            </select>
          </div>
        </div>
        <button
          v-if="selectedFilter"
          class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium hover:bg-muted transition-colors"
          @click="clearFilter"
        >
          <X class="w-4 h-4" />
          Clear
        </button>
        <button
          class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
          :disabled="auditStore.loading"
          @click="refreshLogs"
        >
          <RefreshCw :class="cn('w-4 h-4', auditStore.loading && 'animate-spin')" />
          {{ auditStore.loading ? 'Refreshing...' : 'Refresh' }}
        </button>
      </div>

      <!-- Loading State -->
      <div
        v-if="auditStore.loading && !auditStore.logs.length"
        class="flex flex-col items-center justify-center py-12 rounded-xl border bg-card"
      >
        <Loader2 class="w-8 h-8 animate-spin text-primary mb-3" />
        <p class="text-muted-foreground">Loading audit logs...</p>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="!auditStore.filteredLogs.length"
        class="flex flex-col items-center justify-center py-12 rounded-xl border bg-card"
      >
        <ScrollText class="w-12 h-12 text-muted-foreground/50 mb-4" />
        <h3 class="text-lg font-medium mb-1">No Audit Logs</h3>
        <p class="text-muted-foreground text-sm">There are no audit logs to display</p>
      </div>

      <!-- Audit Table -->
      <div v-else class="rounded-xl border bg-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b bg-muted/50">
                <th class="px-4 py-3 text-left font-medium text-muted-foreground">Timestamp</th>
                <th class="px-4 py-3 text-left font-medium text-muted-foreground">User</th>
                <th class="px-4 py-3 text-left font-medium text-muted-foreground">Event</th>
                <th class="px-4 py-3 text-left font-medium text-muted-foreground">Entity</th>
                <th class="px-4 py-3 text-left font-medium text-muted-foreground">Session</th>
                <th class="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr
                v-for="log in auditStore.filteredLogs"
                :key="log.id"
                class="hover:bg-muted/50 transition-colors"
              >
                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">
                  {{ formatDate(log.created_at) }}
                </td>
                <td class="px-4 py-3">
                  <span class="font-mono text-xs" :title="log.user_id">
                    {{ truncate(log.user_id, 8) }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="getEventColor(log.event_type)"
                  >
                    {{ formatEventType(log.event_type) }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span class="text-xs text-muted-foreground">{{ log.entity_type }}</span>
                  <span class="font-mono text-xs block" :title="log.entity_id">
                    {{ truncate(log.entity_id, 8) }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <span class="font-mono text-xs text-muted-foreground" :title="log.session_id">
                    {{ truncate(log.session_id, 8) }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end">
                    <button
                      class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-3 py-1.5 text-xs font-medium hover:bg-muted transition-colors"
                      @click="showDetails(log)"
                    >
                      <Eye class="w-3 h-3" />
                      View
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Stats Summary -->
      <div v-if="auditStore.logs.length" class="text-sm text-muted-foreground">
        Showing {{ auditStore.filteredLogs.length }} of {{ auditStore.logs.length }} audit log entries
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
              <h3 class="font-semibold">Audit Log Details</h3>
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
            <p class="font-mono text-sm break-all">{{ selectedLog.session_id }}</p>
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
