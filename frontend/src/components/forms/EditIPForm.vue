<script setup lang="ts">
/**
 * Edit IP Form Component
 *
 * Modal form for editing an existing IP address.
 * Allows updating label and comment (IP address itself is immutable).
 *
 * @package Components/Forms
 */

import { reactive, computed, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'
import { useIPStore } from '@/stores/ip'
import type { IPAddress } from '@/types'
import {
  X,
  Globe,
  Tag,
  FileText,
  Loader2,
  AlertCircle,
  User,
  Shield,
  Pencil,
} from 'lucide-vue-next'
import { cn } from '@/lib/utils'

interface Props {
  ip: IPAddress | null
}

const props = defineProps<Props>()

interface Emits {
  (e: 'close'): void
  (e: 'updated'): void
}

const emit = defineEmits<Emits>()

const authStore = useAuthStore()
const ipStore = useIPStore()
const toast = useToast()

const isOwner = computed(() => {
  if (!props.ip || !authStore.user) return false
  return props.ip.user_id === authStore.user.id
})

const canEdit = computed(() => {
  return isOwner.value || authStore.isSuperAdmin
})

const form = reactive({
  label: '',
  comment: '',
})

const errors = reactive({
  label: '',
  comment: '',
})

watch(
  () => props.ip,
  (newIP) => {
    if (newIP) {
      form.label = newIP.label
      form.comment = newIP.comment || ''
    }
  },
  { immediate: true }
)

function validateForm(): boolean {
  let isValid = true

  errors.label = ''
  errors.comment = ''

  if (!form.label.trim()) {
    errors.label = 'Label is required'
    isValid = false
  } else if (form.label.length > 255) {
    errors.label = 'Label must not exceed 255 characters'
    isValid = false
  }

  if (form.comment && form.comment.length > 1000) {
    errors.comment = 'Comment must not exceed 1000 characters'
    isValid = false
  }

  return isValid
}

async function handleSubmit(): Promise<void> {
  if (!canEdit.value) return
  if (!validateForm()) return
  if (!props.ip) return

  try {
    await ipStore.updateIP(props.ip.id, {
      label: form.label.trim(),
      comment: form.comment?.trim() || undefined,
    })

    toast.success('IP address updated successfully')

    emit('updated')
    emit('close')
  } catch {
    toast.error('Failed to update IP address')
  }
}
</script>

<template>
  <div
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
    @click.self="$emit('close')"
  >
    <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-xl bg-card border shadow-lg">
      <!-- Header -->
      <div class="flex items-center justify-between p-4 border-b">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
            <Pencil class="w-5 h-5 text-primary" />
          </div>
          <div>
            <h3 class="font-semibold">Edit IP Address</h3>
            <p class="text-sm text-muted-foreground">Update IP address details</p>
          </div>
        </div>
        <button
          class="p-2 rounded-lg hover:bg-muted transition-colors"
          @click="$emit('close')"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Body -->
      <form @submit.prevent="handleSubmit" class="p-4 space-y-4">
        <!-- IP Address (Read-only) -->
        <div class="space-y-2">
          <label class="text-sm font-medium text-muted-foreground">IP Address</label>
          <div class="flex items-center gap-3 p-3 rounded-lg border bg-muted/50">
            <Globe class="w-5 h-5 text-muted-foreground" />
            <div class="flex-1">
              <p class="font-mono font-medium">{{ ip?.ip_address }}</p>
            </div>
            <span
              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="ip?.type === 'ipv4' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'"
            >
              {{ ip?.type?.toUpperCase() }}
            </span>
          </div>
        </div>

        <!-- Owner -->
        <div class="space-y-2">
          <label class="text-sm font-medium text-muted-foreground">Owner</label>
          <div class="flex items-center gap-3 p-3 rounded-lg border bg-muted/50">
            <User class="w-5 h-5 text-muted-foreground" />
            <span class="flex-1">{{ isOwner ? 'You' : 'Another user' }}</span>
            <span
              v-if="authStore.isSuperAdmin"
              class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium bg-rose-100 text-rose-700"
            >
              <Shield class="w-3 h-3" />
              Admin Edit
            </span>
          </div>
        </div>

        <!-- Label -->
        <div class="space-y-2">
          <label for="edit-label" class="text-sm font-medium">
            Label <span class="text-destructive">*</span>
          </label>
          <div class="relative">
            <Tag class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <input
              id="edit-label"
              v-model="form.label"
              type="text"
              :disabled="!canEdit"
              :class="cn(
                'flex w-full rounded-lg border bg-background px-3 py-2.5 pl-10 text-sm ring-offset-background transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                errors.label ? 'border-destructive focus-visible:ring-destructive' : 'border-input'
              )"
              placeholder="e.g., Production Server, Office Network"
              maxlength="255"
            />
          </div>
          <p v-if="errors.label" class="text-xs text-destructive flex items-center gap-1">
            <AlertCircle class="w-3 h-3" />
            {{ errors.label }}
          </p>
        </div>

        <!-- Comment -->
        <div class="space-y-2">
          <label for="edit-comment" class="text-sm font-medium">Comment</label>
          <div class="relative">
            <FileText class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
            <textarea
              id="edit-comment"
              v-model="form.comment"
              :disabled="!canEdit"
              :class="cn(
                'flex w-full rounded-lg border bg-background px-3 py-2.5 pl-10 text-sm ring-offset-background transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 resize-y min-h-[100px]',
                errors.comment ? 'border-destructive focus-visible:ring-destructive' : 'border-input'
              )"
              placeholder="Optional description or notes..."
              maxlength="1000"
            />
          </div>
          <p v-if="errors.comment" class="text-xs text-destructive flex items-center gap-1">
            <AlertCircle class="w-3 h-3" />
            {{ errors.comment }}
          </p>
        </div>

        <!-- Error Message -->
        <div
          v-if="ipStore.error"
          class="p-3 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-sm flex items-center gap-2"
        >
          <AlertCircle class="w-4 h-4" />
          {{ ipStore.error }}
        </div>

        <!-- Permission Warning -->
        <div
          v-if="!canEdit"
          class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-sm flex items-center gap-2"
        >
          <Shield class="w-4 h-4" />
          You don't have permission to edit this IP address
        </div>
      </form>

      <!-- Footer -->
      <div class="flex items-center justify-end gap-2 p-4 border-t bg-muted/50">
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-lg border bg-background px-4 py-2 text-sm font-medium hover:bg-muted transition-colors"
          :disabled="ipStore.loading"
          @click="$emit('close')"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="ipStore.loading || !canEdit"
          @click="handleSubmit"
        >
          <Loader2 v-if="ipStore.loading" class="w-4 h-4 animate-spin" />
          <span v-if="ipStore.loading">Saving...</span>
          <span v-else>Save Changes</span>
        </button>
      </div>
    </div>
  </div>
</template>
