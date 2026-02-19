<script setup lang="ts">
/**
 * Activity Table Component
 *
 * Displays a table of activity logs with actions for viewing details.
 *
 * @package Components/Tables
 */

import type { AuditLog } from '@/types'
import {
  Eye,
  Key,
  Server,
} from 'lucide-vue-next'

/**
 * Props for the ActivityTable component
 */
interface Props {
  /** Array of activity logs to display */
  logs: AuditLog[]
}

const props = defineProps<Props>()

/**
 * Events emitted by the ActivityTable component
 */
const emit = defineEmits<{
  /** Emitted when view details action is clicked */
  (e: 'view', log: AuditLog): void
}>()

/**
 * Format an event type string to a readable format
 */
function formatEventType(eventType: string | undefined | null): string {
  if (!eventType || typeof eventType !== 'string') {
    return 'Unknown Event'
  }
  return eventType
    .split('.')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

/**
 * Get the color classes for an event type badge
 */
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

/**
 * Get the color classes for a type badge
 */
function getTypeBadgeColor(type: string | undefined): string {
  if (type === 'auth') return 'bg-indigo-100 text-indigo-700'
  if (type === 'ip') return 'bg-orange-100 text-orange-700'
  return 'bg-gray-100 text-gray-700'
}

/**
 * Format a date string to a short readable format
 */
function formatDate(dateString: string | undefined | null): string {
  if (!dateString || typeof dateString !== 'string') {
    return 'Invalid Date'
  }
  const date = new Date(dateString)
  if (isNaN(date.getTime())) {
    return 'Invalid Date'
  }
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

/**
 * Truncate a string to a specified length
 */
function truncate(str: string | undefined | null, length: number): string {
  if (!str || typeof str !== 'string') {
    return ''
  }
  if (str.length <= length) return str
  return str.substring(0, length) + '...'
}

/**
 * Handle view details button click
 */
function handleView(log: AuditLog): void {
  emit('view', log)
}
</script>

<template>
  <div class="rounded-xl border bg-card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b bg-muted/50">
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Timestamp</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Source</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">User</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Event</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Entity</th>
            <th class="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr
            v-for="log in props.logs"
            :key="log.id"
            class="hover:bg-muted/50 transition-colors"
          >
            <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">
              {{ formatDate(log.created_at) }}
            </td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="getTypeBadgeColor(log.type)"
              >
                <Key v-if="log.type === 'auth'" class="w-3 h-3" />
                <Server v-else-if="log.type === 'ip'" class="w-3 h-3" />
                {{ log.type === 'auth' ? 'Auth' : log.type === 'ip' ? 'IP' : 'Unknown' }}
              </span>
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
              <div class="flex items-center justify-end">
                <button
                  class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-3 py-1.5 text-xs font-medium hover:bg-muted transition-colors"
                  @click="handleView(log)"
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
</template>
