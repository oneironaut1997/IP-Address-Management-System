<script setup lang="ts">
/**
 * IP Table Component
 *
 * Displays a table of IP addresses with actions for viewing,
 * editing, and deleting.
 *
 * @package Components/Tables
 */

import { useAuthStore } from '@/stores/auth'
import type { IPAddress } from '@/types'
import {
  Eye,
  History,
  Pencil,
  Trash2,
} from 'lucide-vue-next'
import { cn } from '@/lib/utils'

/**
 * Props for the IPTable component
 */
interface Props {
  /** Array of IP addresses to display */
  ips: IPAddress[]
}

const props = defineProps<Props>()

/**
 * Events emitted by the IPTable component
 */
const emit = defineEmits<{
  /** Emitted when edit action is clicked */
  (e: 'edit', ip: IPAddress): void
  /** Emitted when delete action is clicked */
  (e: 'delete', ip: IPAddress): void
}>()

const authStore = useAuthStore()

/**
 * Check if the given user ID matches the current user
 */
function isCurrentUser(userId: string): boolean {
  return authStore.user?.id === userId
}

/**
 * Check if the current user can edit the given IP
 */
function canEdit(ip: IPAddress): boolean {
  return isCurrentUser(ip.user_id) || authStore.isSuperAdmin
}

/**
 * Format a date string to a readable format
 */
function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

/**
 * Handle edit button click
 */
function handleEdit(ip: IPAddress): void {
  emit('edit', ip)
}

/**
 * Handle delete button click
 */
function handleDelete(ip: IPAddress): void {
  emit('delete', ip)
}
</script>

<template>
  <div class="rounded-xl border bg-card overflow-hidden">
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
            v-for="ip in props.ips"
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
                  @click="handleEdit(ip)"
                >
                  <Pencil class="w-4 h-4 text-muted-foreground" />
                </button>
                <button
                  v-if="authStore.isSuperAdmin"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-destructive/10 transition-colors"
                  title="Delete"
                  @click="handleDelete(ip)"
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
