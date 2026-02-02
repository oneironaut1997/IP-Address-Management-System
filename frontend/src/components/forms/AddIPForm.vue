<script setup lang="ts">
/**
 * Add IP Form Component
 *
 * Modal form for creating a new IP address.
 * Validates IP format (IPv4/IPv6) and submits to the IP store.
 *
 * @package Components/Forms
 */

import { reactive, computed } from 'vue'
import { useToast } from 'vue-toastification'
import { useIPStore } from '@/stores/ip'
import type { IPFormData } from '@/types'
import {
  X,
  Globe,
  Tag,
  FileText,
  Loader2,
  CheckCircle,
  AlertCircle,
} from 'lucide-vue-next'
import { cn } from '@/lib/utils'

interface Emits {
  (e: 'close'): void
  (e: 'created'): void
}

const emit = defineEmits<Emits>()

const ipStore = useIPStore()
const toast = useToast()

const form = reactive<IPFormData>({
  ip_address: '',
  label: '',
  comment: '',
})

const errors = reactive({
  ip_address: '',
  label: '',
  comment: '',
})

const detectedType = computed(() => {
  const ip = form.ip_address.trim()
  if (!ip) return null

  const ipv4Pattern = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/
  const ipv6Pattern = /^(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,7}:$|^(?:[0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,5}(?::[0-9a-fA-F]{1,4}){1,2}$|^(?:[0-9a-fA-F]{1,4}:){1,4}(?::[0-9a-fA-F]{1,4}){1,3}$|^(?:[0-9a-fA-F]{1,4}:){1,3}(?::[0-9a-fA-F]{1,4}){1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,2}(?::[0-9a-fA-F]{1,4}){1,5}$|^[0-9a-fA-F]{1,4}:(?::[0-9a-fA-F]{1,4}){1,6}$|^:(?::[0-9a-fA-F]{1,4}){1,7}$|^::$|^(?:[fF]{4}:)?(?:[0-9a-fA-F]{1,4}:){0,5}(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/

  if (ipv4Pattern.test(ip)) return 'ipv4'
  if (ipv6Pattern.test(ip)) return 'ipv6'
  return null
})

function isValidIP(ip: string): boolean {
  const ipv4Pattern = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/
  const ipv6Pattern = /^(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,7}:$|^(?:[0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,5}(?::[0-9a-fA-F]{1,4}){1,2}$|^(?:[0-9a-fA-F]{1,4}:){1,4}(?::[0-9a-fA-F]{1,4}){1,3}$|^(?:[0-9a-fA-F]{1,4}:){1,3}(?::[0-9a-fA-F]{1,4}){1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,2}(?::[0-9a-fA-F]{1,4}){1,5}$|^[0-9a-fA-F]{1,4}:(?::[0-9a-fA-F]{1,4}){1,6}$|^:(?::[0-9a-fA-F]{1,4}){1,7}$|^::$|^(?:[fF]{4}:)?(?:[0-9a-fA-F]{1,4}:){0,5}(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/

  return ipv4Pattern.test(ip) || ipv6Pattern.test(ip)
}

function validateForm(): boolean {
  let isValid = true

  errors.ip_address = ''
  errors.label = ''
  errors.comment = ''

  const ip = form.ip_address.trim()
  if (!ip) {
    errors.ip_address = 'IP address is required'
    isValid = false
  } else if (!isValidIP(ip)) {
    errors.ip_address = 'Please enter a valid IPv4 or IPv6 address'
    isValid = false
  }

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
  if (!validateForm()) return

  try {
    await ipStore.createIP({
      ip_address: form.ip_address.trim(),
      label: form.label.trim(),
      comment: form.comment?.trim() || undefined,
    })

    toast.success('IP address created successfully')

    form.ip_address = ''
    form.label = ''
    form.comment = ''

    emit('created')
    emit('close')
  } catch {
    toast.error('Failed to create IP address')
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
            <Globe class="w-5 h-5 text-primary" />
          </div>
          <div>
            <h3 class="font-semibold">Add New IP Address</h3>
            <p class="text-sm text-muted-foreground">Create a new IP address entry</p>
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
        <!-- IP Address -->
        <div class="space-y-2">
          <label for="ip_address" class="text-sm font-medium">
            IP Address <span class="text-destructive">*</span>
          </label>
          <div class="relative">
            <Globe class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <input
              id="ip_address"
              v-model="form.ip_address"
              type="text"
              :class="cn(
                'flex w-full rounded-lg border bg-background px-3 py-2.5 pl-10 text-sm ring-offset-background transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                errors.ip_address ? 'border-destructive focus-visible:ring-destructive' : 'border-input'
              )"
              placeholder="e.g., 192.168.1.1 or 2001:db8::1"
            />
          </div>
          <p v-if="errors.ip_address" class="text-xs text-destructive flex items-center gap-1">
            <AlertCircle class="w-3 h-3" />
            {{ errors.ip_address }}
          </p>
          <p v-else class="text-xs text-muted-foreground">
            Enter a valid IPv4 or IPv6 address
          </p>

          <!-- Detected Type -->
          <div
            v-if="detectedType"
            class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm"
            :class="detectedType === 'ipv4' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200'"
          >
            <CheckCircle class="w-4 h-4" />
            Detected: {{ detectedType === 'ipv4' ? 'IPv4' : 'IPv6' }}
          </div>
        </div>

        <!-- Label -->
        <div class="space-y-2">
          <label for="label" class="text-sm font-medium">
            Label <span class="text-destructive">*</span>
          </label>
          <div class="relative">
            <Tag class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <input
              id="label"
              v-model="form.label"
              type="text"
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
          <label for="comment" class="text-sm font-medium">Comment</label>
          <div class="relative">
            <FileText class="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
            <textarea
              id="comment"
              v-model="form.comment"
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
          class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
          :disabled="ipStore.loading"
          @click="handleSubmit"
        >
          <Loader2 v-if="ipStore.loading" class="w-4 h-4 animate-spin" />
          <span v-if="ipStore.loading">Creating...</span>
          <span v-else>Create IP Address</span>
        </button>
      </div>
    </div>
  </div>
</template>
