/**
 * IP Address Table Component
 *
 * A specialized table component for displaying IP addresses with
 * actions for viewing, editing, and deleting.
 *
 * @package Components/Tables
 */

import { h, type FunctionalComponent, type Component } from 'vue'
import { RouterLink } from 'vue-router'
import { Eye, History, Pencil, Trash2 } from 'lucide-vue-next'
import type { IPAddress } from '@/types'
import { cn } from '@/lib/utils'

/**
 * Props for IPTable component
 */
interface Props {
  /** Array of IP addresses to display */
  items: IPAddress[]
  /** Whether data is loading */
  loading?: boolean
  /** Current user ID for ownership check */
  currentUserId?: string
  /** Whether current user is super admin */
  isSuperAdmin?: boolean
  /** Callback when view is clicked */
  onView?: (ip: IPAddress) => void
  /** Callback when history is clicked */
  onHistory?: (ip: IPAddress) => void
  /** Callback when edit is clicked */
  onEdit?: (ip: IPAddress) => void
  /** Callback when delete is clicked */
  onDelete?: (ip: IPAddress) => void
  /** Callback when row is clicked */
  onRowClick?: (ip: IPAddress, index: number) => void
}

/**
 * Format a date string to locale date
 */
function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

/**
 * Check if the IP is owned by current user
 */
function isCurrentUser(userId: string, currentUserId?: string): boolean {
  return currentUserId ? userId === currentUserId : false
}

/**
 * Check if user can edit the IP
 */
function canEdit(ip: IPAddress, currentUserId?: string, isSuperAdmin?: boolean): boolean {
  return isCurrentUser(ip.user_id, currentUserId) || (isSuperAdmin ?? false)
}

/**
 * IP Type Badge component
 */
const TypeBadge: FunctionalComponent<{ type: string }> = (props) => {
  return h('span', {
    class: [
      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
      props.type === 'ipv4' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'
    ]
  }, props.type.toUpperCase())
}

/**
 * Owner Cell component
 */
const OwnerCell: FunctionalComponent<{ userId: string; currentUserId?: string }> = (props) => {
  const isOwner = isCurrentUser(props.userId, props.currentUserId)
  return h('span', {
    class: cn(
      'text-sm',
      isOwner ? 'text-primary font-medium' : 'text-muted-foreground'
    )
  }, isOwner ? 'You' : 'User')
}

/**
 * Actions Cell component
 */
const ActionsCell: FunctionalComponent<{
  ip: IPAddress
  currentUserId?: string
  isSuperAdmin?: boolean
  onView?: (ip: IPAddress) => void
  onHistory?: (ip: IPAddress) => void
  onEdit?: (ip: IPAddress) => void
  onDelete?: (ip: IPAddress) => void
}> = (props) => {
  const canEditIP = canEdit(props.ip, props.currentUserId, props.isSuperAdmin)
  const isAdmin = props.isSuperAdmin ?? false

  return h('div', { class: 'flex items-center justify-end gap-1' }, [
    // View button
    h(RouterLink, {
      to: `/ip/${props.ip.id}`,
      class: 'inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-muted transition-colors',
      title: 'View Details'
    }, () => h(Eye, { class: 'w-4 h-4 text-muted-foreground' })),

    // History button
    h(RouterLink, {
      to: `/ip/${props.ip.id}/history`,
      class: 'inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-muted transition-colors',
      title: 'View History'
    }, () => h(History, { class: 'w-4 h-4 text-muted-foreground' })),

    // Edit button
    canEditIP ? h('button', {
      class: 'inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-muted transition-colors',
      title: 'Edit',
      onClick: (e: Event) => {
        e.stopPropagation()
        props.onEdit?.(props.ip)
      }
    }, () => h(Pencil, { class: 'w-4 h-4 text-muted-foreground' })) : null,

    // Delete button (admin only)
    isAdmin ? h('button', {
      class: 'inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-destructive/10 transition-colors',
      title: 'Delete',
      onClick: (e: Event) => {
        e.stopPropagation()
        props.onDelete?.(props.ip)
      }
    }, () => h(Trash2, { class: 'w-4 h-4 text-destructive' })) : null
  ])
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  currentUserId: undefined,
  isSuperAdmin: false,
  onView: undefined,
  onHistory: undefined,
  onEdit: undefined,
  onDelete: undefined,
  onRowClick: undefined,
})

/**
 * Get table columns configuration
 */
function getColumns() {
  return [
    {
      key: 'ip_address',
      label: 'IP Address',
      render: (ip: IPAddress) => h(RouterLink, {
        to: `/ip/${ip.id}`,
        class: 'font-mono font-medium text-primary hover:underline'
      }, () => ip.ip_address)
    },
    {
      key: 'label',
      label: 'Label'
    },
    {
      key: 'type',
      label: 'Type',
      render: (ip: IPAddress) => h(TypeBadge, { type: ip.type })
    },
    {
      key: 'owner',
      label: 'Owner',
      render: (ip: IPAddress) => h(OwnerCell, { 
        userId: ip.user_id, 
        currentUserId: props.currentUserId 
      })
    },
    {
      key: 'created_at',
      label: 'Created',
      render: (ip: IPAddress) => h('span', { 
        class: 'text-muted-foreground' 
      }, formatDate(ip.created_at))
    },
    {
      key: 'actions',
      label: 'Actions',
      headerClass: 'text-right',
      cellClass: 'text-right',
      render: (ip: IPAddress) => h(ActionsCell, {
        ip,
        currentUserId: props.currentUserId,
        isSuperAdmin: props.isSuperAdmin,
        onView: props.onView,
        onHistory: props.onHistory,
        onEdit: props.onEdit,
        onDelete: props.onDelete
      })
    }
  ]
}

/**
 * Handle row click
 */
function handleRowClick(item: IPAddress, index: number) {
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
      <p class="text-muted-foreground">Loading IP addresses...</p>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!items.length"
      class="flex flex-col items-center justify-center py-12"
    >
      <div class="w-12 h-12 rounded-full bg-muted/50 flex items-center justify-center mb-4">
        <svg class="w-6 h-6 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
        </svg>
      </div>
      <h3 class="text-lg font-medium mb-1">No IP Addresses</h3>
      <p class="text-muted-foreground text-sm">Get started by adding your first IP address</p>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto">
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
            v-for="(ip, index) in items"
            :key="ip.id"
            class="hover:bg-muted/50 transition-colors cursor-pointer"
            @click="handleRowClick(ip, index)"
          >
            <td class="px-4 py-3">
              <RouterLink
                :to="`/ip/${ip.id}`"
                class="font-mono font-medium text-primary hover:underline"
                @click.stop
              >
                {{ ip.ip_address }}
              </RouterLink>
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
                  isCurrentUser(ip.user_id, currentUserId) ? 'text-primary font-medium' : 'text-muted-foreground'
                )"
              >
                {{ isCurrentUser(ip.user_id, currentUserId) ? 'You' : 'User' }}
              </span>
            </td>
            <td class="px-4 py-3 text-muted-foreground">{{ formatDate(ip.created_at) }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-1">
                <RouterLink
                  :to="`/ip/${ip.id}`"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-muted transition-colors"
                  title="View Details"
                  @click.stop
                >
                  <Eye class="w-4 h-4 text-muted-foreground" />
                </RouterLink>
                <RouterLink
                  :to="`/ip/${ip.id}/history`"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-muted transition-colors"
                  title="View History"
                  @click.stop
                >
                  <History class="w-4 h-4 text-muted-foreground" />
                </RouterLink>
                <button
                  v-if="canEdit(ip, currentUserId, isSuperAdmin)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-muted transition-colors"
                  title="Edit"
                  @click.stop="onEdit?.(ip)"
                >
                  <Pencil class="w-4 h-4 text-muted-foreground" />
                </button>
                <button
                  v-if="isSuperAdmin"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-destructive/10 transition-colors"
                  title="Delete"
                  @click.stop="onDelete?.(ip)"
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
</template>
