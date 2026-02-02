<script setup lang="ts">
/**
 * IP History View
 *
 * Displays the change history for a specific IP address.
 * Shows all modifications with before/after values.
 *
 * @package Views/IP
 */

import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useIPStore } from '@/stores/ip'
import {
  ArrowLeft,
  Loader2,
  FileText,
  Plus,
  Pencil,
  Trash2,
  ArrowRight,
  Clock,
  User,
} from 'lucide-vue-next'

const route = useRoute()
const ipStore = useIPStore()

function formatAction(action: string): string {
  const actions: Record<string, string> = {
    created: 'Created',
    updated: 'Updated',
    deleted: 'Deleted',
  }
  return actions[action] || action
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function hasChanges(
  oldValues: Record<string, unknown>,
  newValues: Record<string, unknown>,
  field: string
): boolean {
  return oldValues[field] !== newValues[field]
}

function getActionIcon(action: string) {
  switch (action) {
    case 'created':
      return Plus
    case 'updated':
      return Pencil
    case 'deleted':
      return Trash2
    default:
      return FileText
  }
}

function getActionColor(action: string): string {
  switch (action) {
    case 'created':
      return 'bg-emerald-500'
    case 'updated':
      return 'bg-blue-500'
    case 'deleted':
      return 'bg-rose-500'
    default:
      return 'bg-gray-500'
  }
}

function getActionBgColor(action: string): string {
  switch (action) {
    case 'created':
      return 'bg-emerald-50 border-emerald-200'
    case 'updated':
      return 'bg-blue-50 border-blue-200'
    case 'deleted':
      return 'bg-rose-50 border-rose-200'
    default:
      return 'bg-gray-50 border-gray-200'
  }
}

onMounted(() => {
  const id = route.params.id as string
  if (id) {
    ipStore.fetchHistory(id)
    const found = ipStore.ips.find((ip) => ip.id === id)
    if (found) {
      ipStore.setCurrentIP(found)
    }
  }
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
      <div class="flex items-center gap-4">
        <router-link
          :to="`/ip/${route.params.id}`"
          class="inline-flex items-center justify-center w-10 h-10 rounded-lg border hover:bg-muted transition-colors"
        >
          <ArrowLeft class="w-5 h-5" />
        </router-link>
        <div>
          <h1 class="text-2xl font-bold tracking-tight">IP History</h1>
          <p v-if="ipStore.currentIP" class="text-muted-foreground font-mono">
            {{ ipStore.currentIP.ip_address }}
          </p>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div
      v-if="ipStore.loading && !ipStore.history.length"
      class="flex flex-col items-center justify-center py-12 rounded-xl border bg-card"
    >
      <Loader2 class="w-8 h-8 animate-spin text-primary mb-3" />
      <p class="text-muted-foreground">Loading history...</p>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!ipStore.history.length"
      class="flex flex-col items-center justify-center py-12 rounded-xl border bg-card"
    >
      <FileText class="w-12 h-12 text-muted-foreground/50 mb-4" />
      <h3 class="text-lg font-medium mb-1">No History Available</h3>
      <p class="text-muted-foreground text-sm mb-4">This IP address has no recorded changes yet</p>
      <router-link
        :to="`/ip/${route.params.id}`"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
      >
        <ArrowLeft class="w-4 h-4" />
        Back to IP Details
      </router-link>
    </div>

    <!-- History Timeline -->
    <div v-else class="space-y-4">
      <div
        v-for="entry in ipStore.history"
        :key="entry.id"
        class="relative pl-8 pb-8 last:pb-0"
      >
        <!-- Timeline line -->
        <div
          v-if="entry !== ipStore.history[ipStore.history.length - 1]"
          class="absolute left-3 top-8 bottom-0 w-px bg-border"
        />

        <!-- Timeline dot -->
        <div
          class="absolute left-0 top-1 w-6 h-6 rounded-full border-2 border-background flex items-center justify-center"
          :class="getActionColor(entry.action)"
        >
          <component :is="getActionIcon(entry.action)" class="w-3 h-3 text-white" />
        </div>

        <!-- Content -->
        <div
          class="rounded-xl border p-4"
          :class="getActionBgColor(entry.action)"
        >
          <!-- Header -->
          <div class="flex flex-wrap items-center gap-2 mb-3">
            <span
              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
              :class="entry.action === 'created' ? 'bg-emerald-100 text-emerald-700' : entry.action === 'updated' ? 'bg-blue-100 text-blue-700' : 'bg-rose-100 text-rose-700'"
            >
              {{ formatAction(entry.action) }}
            </span>
            <span class="text-sm text-muted-foreground flex items-center gap-1">
              <Clock class="w-3 h-3" />
              {{ formatDate(entry.created_at) }}
            </span>
          </div>

          <!-- Modified By -->
          <div class="flex items-center gap-2 text-sm mb-3">
            <User class="w-4 h-4 text-muted-foreground" />
            <span class="text-muted-foreground">Modified by:</span>
            <span class="font-medium font-mono">{{ entry.modified_by }}</span>
          </div>

          <!-- Changes (for updates) -->
          <div v-if="entry.action === 'updated'" class="space-y-2">
            <div
              v-if="hasChanges(entry.old_values, entry.new_values, 'label')"
              class="rounded-lg bg-background/50 p-3"
            >
              <p class="text-xs font-medium text-muted-foreground mb-1">Label</p>
              <div class="flex items-center gap-2 text-sm">
                <span class="line-through text-muted-foreground">{{ entry.old_values.label || '(empty)' }}</span>
                <ArrowRight class="w-4 h-4 text-muted-foreground" />
                <span class="font-medium">{{ entry.new_values.label || '(empty)' }}</span>
              </div>
            </div>

            <div
              v-if="hasChanges(entry.old_values, entry.new_values, 'comment')"
              class="rounded-lg bg-background/50 p-3"
            >
              <p class="text-xs font-medium text-muted-foreground mb-1">Comment</p>
              <div class="flex flex-col gap-1 text-sm">
                <span class="line-through text-muted-foreground">{{ entry.old_values.comment || '(empty)' }}</span>
                <ArrowRight class="w-4 h-4 text-muted-foreground" />
                <span class="font-medium">{{ entry.new_values.comment || '(empty)' }}</span>
              </div>
            </div>
          </div>

          <!-- Creation Info -->
          <div v-else-if="entry.action === 'created'" class="rounded-lg bg-background/50 p-3">
            <p class="text-sm font-medium mb-2">Initial creation with:</p>
            <ul class="space-y-1 text-sm">
              <li><span class="text-muted-foreground">IP:</span> <span class="font-mono">{{ entry.new_values.ip_address }}</span></li>
              <li><span class="text-muted-foreground">Label:</span> {{ entry.new_values.label }}</li>
              <li v-if="entry.new_values.comment">
                <span class="text-muted-foreground">Comment:</span> {{ entry.new_values.comment }}
              </li>
            </ul>
          </div>

          <!-- Deletion Info -->
          <div v-else-if="entry.action === 'deleted'" class="rounded-lg bg-background/50 p-3">
            <p class="text-sm text-rose-600">This IP address was deleted</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
