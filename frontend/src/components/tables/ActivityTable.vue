/**
 * Activity Log Table Component
 *
 * A specialized table component for displaying activity logs
 * with filtering, sorting, and detail view capabilities.
 *
 * @package Components/Tables
 */

import { h, type FunctionalComponent } from 'vue'
import { Eye, Key, Server } from 'lucide-vue-next'
import type { AuditLog, AuditLogType } from '@/types'

/**
 * Props for ActivityTable component
 */
interface Props {
  /** Array of audit logs to display */
  items: AuditLog[]
  /** Whether data is loading */
  loading?: boolean
  /** Callback when view details is clicked */
  onViewDetails?: (log: AuditLog) => void
  /** Callback when row is clicked */
  onRowClick?: (log: AuditLog, index: number) => void
}

/**
 * Format event type for display (e.g., 'ip.created' -> 'Ip Created')
 */
function formatEventType(eventType: string | undefined): string {
  if (!eventType) return 'Unknown'
  return eventType
    .split('.')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

/**
 * Get color class for event type badge
 */
function getEventColor(eventType: string | undefined): string {
  if (!eventType) return 'bg-gray-100 text-gray-700'
  if (eventType.includes('login')) return 'bg-emerald-100 text-emerald-700'
  if (eventType.includes('logout')) return 'bg-amber-100 text-amber-700'
  if (eventType.includes('created')) return 'bg-blue-100 text-blue-700'
  if (eventType.includes('updated')) return 'bg-purple-100 text-purple-700'
  if (eventType.includes('deleted')) return 'bg-rose-100 text-rose-700'
  return 'bg-gray-100 text-gray-700'
}

/**
 * Get color class for type badge
 */
function getTypeBadgeColor(type: string | undefined): string {
  if (type === 'auth') return 'bg-indigo-100 text-indigo-700'
  if (type === 'ip') return 'bg-orange-100 text-orange-700'
  return 'bg-gray-100 text-gray-700'
}

/**
 * Format date for display
 */
function formatDate(dateString: string | undefined): string {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

/**
 * Truncate string with ellipsis
 */
function truncate(str: string | undefined, length: number): string {
  if (!str) return ''
  if (str.length <= length) return str
  return str.substring(0, length) + '...'
}

/**
 * Get entity type display
 */
function getEntityType(entityType: string | undefined): string {
  return entityType || 'Unknown'
}

/**
 * Type Badge component
 */
const TypeBadge: FunctionalComponent<{ type: string | undefined }> = (props) => {
  return h('span', {
    class: ['inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium', getTypeBadgeColor(props.type)]
  }, [
    props.type === 'auth' ? h(Key, { class: 'w-3 h-3' }) : props.type === 'ip' ? h(Server, { class: 'w-3 h-3' }) : null,
    props.type === 'auth' ? 'Auth' : props.type === 'ip' ? 'IP' : 'Unknown'
  ])
}

/**
 * Event Badge component
 */
const EventBadge: FunctionalComponent<{ eventType: string | undefined }> = (props) => {
  return h('span', {
    class: ['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', getEventColor(props.eventType)]
  }, formatEventType(props.eventType))
}

/**
 * Entity Cell component
 */
const EntityCell: FunctionalComponent<{ entityType: string | undefined; entityId: string }> = (props) => {
  return h('div', {}, [
    h('span', { class: 'text-xs text-muted-foreground' }, getEntityType(props.entityType)),
    h('span', { class: 'font-mono text-xs block', title: props.entityId }, truncate(props.entityId, 8))
  ])
}

/**
 * Actions Cell component
 */
const ActionsCell: FunctionalComponent<{ log: AuditLog; onViewDetails?: (log: AuditLog) => void }> = (props) => {
  return h('div', { class: 'flex items-center justify-end' }, [
    h('button', {
      class: 'inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-3 py-1.5 text-xs font-medium hover:bg-muted transition-colors',
      onClick: (e: Event) => {
        e.stopPropagation()
        props.onViewDetails?.(props.log)
      }
    }, [
      h(Eye, { class: 'w-3 h-3' }),
      'View'
    ])
  ])
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  onViewDetails: undefined,
  onRowClick: undefined,
})

/**
 * Handle row click
 */
function handleRowClick(item: AuditLog, index: number) {
  props.onRowClick?.(item, index)
}
</script>

<template>
  <div class="rounded-xl border bg-card overflow-hidden">
    <!-- Loading State -->
    <div
      v-if="loading && !items.length"
      class="flex flex-col items-center justify-center py-12"
    >
      <div class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin mb-3" />
      <p class="text-muted-foreground">Loading activity logs...</p>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!items.length"
      class="flex flex-col items-center justify-center py-12"
    >
      <div class="w-12 h-12 rounded-full bg-muted/50 flex items-center justify-center mb-4">
        <svg class="w-6 h-6 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <h3 class="text-lg font-medium mb-1">No Activity Logs</h3>
      <p class="text-muted-foreground text-sm">There are no activity logs to display</p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
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
            v-for="(log, index) in items"
            :key="log.id"
            class="hover:bg-muted/50 transition-colors cursor-pointer"
            @click="handleRowClick(log, index)"
          >
            <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">
              {{ formatDate(log.created_at) }}
            </td>
            <td class="px-4 py-3">
              <TypeBadge :type="log.type" />
            </td>
            <td class="px-4 py-3">
              <span class="font-mono text-xs" :title="log.user_id">
                {{ truncate(log.user_id, 8) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <EventBadge :event-type="log.event_type" />
            </td>
            <td class="px-4 py-3">
              <EntityCell :entity-type="log.entity_type" :entity-id="log.entity_id" />
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end">
                <button
                  class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-3 py-1.5 text-xs font-medium hover:bg-muted transition-colors"
                  @click.stop="onViewDetails?.(log)"
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
