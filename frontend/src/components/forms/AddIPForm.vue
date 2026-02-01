/**
 * Add IP Form Component
 *
 * Modal form for creating a new IP address.
 * Validates IP format (IPv4/IPv6) and submits to the IP store.
 *
 * @package Components/Forms
 */

<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal">
      <div class="modal-header">
        <h3 class="modal-title">Add New IP Address</h3>
        <button class="modal-close" @click="$emit('close')" aria-label="Close">&times;</button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
        <!-- IP Address Field -->
        <div class="form-group">
          <label for="ip_address" class="form-label">
            IP Address <span class="required">*</span>
          </label>
          <input
            id="ip_address"
            v-model="form.ip_address"
            type="text"
            class="form-input"
            :class="{ 'form-input--error': errors.ip_address }"
            placeholder="e.g., 192.168.1.1 or 2001:db8::1"
            required
          />
          <span v-if="errors.ip_address" class="form-error">{{ errors.ip_address }}</span>
          <span v-else class="form-hint">Enter a valid IPv4 or IPv6 address</span>
        </div>

        <!-- Label Field -->
        <div class="form-group">
          <label for="label" class="form-label">
            Label <span class="required">*</span>
          </label>
          <input
            id="label"
            v-model="form.label"
            type="text"
            class="form-input"
            :class="{ 'form-input--error': errors.label }"
            placeholder="e.g., Production Server, Office Network"
            required
            maxlength="255"
          />
          <span v-if="errors.label" class="form-error">{{ errors.label }}</span>
        </div>

        <!-- Comment Field -->
        <div class="form-group">
          <label for="comment" class="form-label">Comment</label>
          <textarea
            id="comment"
            v-model="form.comment"
            class="form-input form-textarea"
            :class="{ 'form-input--error': errors.comment }"
            placeholder="Optional description or notes..."
            rows="3"
            maxlength="1000"
          ></textarea>
          <span v-if="errors.comment" class="form-error">{{ errors.comment }}</span>
        </div>

        <!-- IP Type Display -->
        <div v-if="detectedType" class="ip-type-badge" :class="`ip-type--${detectedType}`">
          Detected: {{ detectedType === 'ipv4' ? 'IPv4' : 'IPv6' }}
        </div>

        <!-- Error Message -->
        <div v-if="ipStore.error" class="alert alert--error">
          {{ ipStore.error }}
        </div>
      </form>

      <div class="modal-footer">
        <button type="button" class="btn btn--secondary" @click="$emit('close')" :disabled="ipStore.loading">
          Cancel
        </button>
        <button type="submit" class="btn btn--primary" @click="handleSubmit" :disabled="ipStore.loading">
          <span v-if="ipStore.loading">Creating...</span>
          <span v-else>Create IP Address</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue'
import { useToast } from 'vue-toastification'
import { useIPStore } from '@/stores/ip'
import type { IPFormData } from '@/types'

/**
 * Emits
 */
interface Emits {
  (e: 'close'): void
  (e: 'created'): void
}

const emit = defineEmits<Emits>()

/**
 * Stores & Services
 */
const ipStore = useIPStore()
const toast = useToast()

/**
 * Form State
 */
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

/**
 * Detect IP type from address
 */
const detectedType = computed(() => {
  const ip = form.ip_address.trim()
  if (!ip) return null

  // IPv4 pattern
  const ipv4Pattern = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/

  // IPv6 pattern (simplified)
  const ipv6Pattern = /^(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,7}:$|^(?:[0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,5}(?::[0-9a-fA-F]{1,4}){1,2}$|^(?:[0-9a-fA-F]{1,4}:){1,4}(?::[0-9a-fA-F]{1,4}){1,3}$|^(?:[0-9a-fA-F]{1,4}:){1,3}(?::[0-9a-fA-F]{1,4}){1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,2}(?::[0-9a-fA-F]{1,4}){1,5}$|^[0-9a-fA-F]{1,4}:(?::[0-9a-fA-F]{1,4}){1,6}$|^:(?::[0-9a-fA-F]{1,4}){1,7}$|^::$|^(?:[0-9a-fA-F]{1,4}:){1,7}[0-9a-fA-F]{1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,5}(?::[0-9a-fA-F]{1,4}){1,2}$|^(?:[0-9a-fA-F]{1,4}:){1,4}(?::[0-9a-fA-F]{1,4}){1,3}$|^(?:[0-9a-fA-F]{1,4}:){1,3}(?::[0-9a-fA-F]{1,4}){1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,2}(?::[0-9a-fA-F]{1,4}){1,5}$|^[0-9a-fA-F]{1,4}:(?::[0-9a-fA-F]{1,4}){1,6}$|^:(?::[0-9a-fA-F]{1,4}){1,7}$|^::$|^(?:[fF]{4}:)?(?:[0-9a-fA-F]{1,4}:){0,5}(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/

  if (ipv4Pattern.test(ip)) return 'ipv4'
  if (ipv6Pattern.test(ip)) return 'ipv6'
  return null
})

/**
 * Validate IP address format
 */
function isValidIP(ip: string): boolean {
  const ipv4Pattern = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/
  const ipv6Pattern = /^(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,7}:$|^(?:[0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,5}(?::[0-9a-fA-F]{1,4}){1,2}$|^(?:[0-9a-fA-F]{1,4}:){1,4}(?::[0-9a-fA-F]{1,4}){1,3}$|^(?:[0-9a-fA-F]{1,4}:){1,3}(?::[0-9a-fA-F]{1,4}){1,4}$|^(?:[0-9a-fA-F]{1,4}:){1,2}(?::[0-9a-fA-F]{1,4}){1,5}$|^[0-9a-fA-F]{1,4}:(?::[0-9a-fA-F]{1,4}){1,6}$|^:(?::[0-9a-fA-F]{1,4}){1,7}$|^::$|^(?:[fF]{4}:)?(?:[0-9a-fA-F]{1,4}:){0,5}(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/

  return ipv4Pattern.test(ip) || ipv6Pattern.test(ip)
}

/**
 * Validate form
 */
function validateForm(): boolean {
  let isValid = true

  // Reset errors
  errors.ip_address = ''
  errors.label = ''
  errors.comment = ''

  // IP address validation
  const ip = form.ip_address.trim()
  if (!ip) {
    errors.ip_address = 'IP address is required'
    isValid = false
  } else if (!isValidIP(ip)) {
    errors.ip_address = 'Please enter a valid IPv4 or IPv6 address'
    isValid = false
  }

  // Label validation
  if (!form.label.trim()) {
    errors.label = 'Label is required'
    isValid = false
  } else if (form.label.length > 255) {
    errors.label = 'Label must not exceed 255 characters'
    isValid = false
  }

  // Comment validation
  if (form.comment && form.comment.length > 1000) {
    errors.comment = 'Comment must not exceed 1000 characters'
    isValid = false
  }

  return isValid
}

/**
 * Handle form submission
 */
async function handleSubmit(): Promise<void> {
  if (!validateForm()) return

  try {
    await ipStore.createIP({
      ip_address: form.ip_address.trim(),
      label: form.label.trim(),
      comment: form.comment?.trim() || undefined,
    })

    // Show success toast
    toast.success('IP address created successfully')

    // Reset form
    form.ip_address = ''
    form.label = ''
    form.comment = ''

    // Emit success
    emit('created')
    emit('close')
  } catch {
    // Show error toast
    toast.error('Failed to create IP address')
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal {
  background: white;
  border-radius: 8px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #718096;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: background-color 0.15s, color 0.15s;
}

.modal-close:hover {
  background-color: #f7fafc;
  color: #1a202c;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid #e2e8f0;
  background-color: #f7fafc;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #4a5568;
  margin-bottom: 0.375rem;
}

.required {
  color: #f56565;
}

.form-input {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.875rem;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-input--error {
  border-color: #f56565;
}

.form-input--error:focus {
  border-color: #f56565;
  box-shadow: 0 0 0 3px rgba(245, 101, 101, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.form-error {
  display: block;
  font-size: 0.75rem;
  color: #f56565;
  margin-top: 0.25rem;
}

.form-hint {
  display: block;
  font-size: 0.75rem;
  color: #718096;
  margin-top: 0.25rem;
}

.ip-type-badge {
  display: inline-block;
  padding: 0.375rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  margin-bottom: 1rem;
}

.ip-type--ipv4 {
  background-color: #c6f6d5;
  color: #22543d;
}

.ip-type--ipv6 {
  background-color: #bee3f8;
  color: #2a4365;
}

.alert {
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  margin-top: 1rem;
}

.alert--error {
  background-color: #fff5f5;
  color: #c53030;
  border: 1px solid #feb2b2;
}

.btn {
  padding: 0.625rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s, opacity 0.15s;
}

.btn--primary {
  background-color: #667eea;
  color: white;
}

.btn--primary:hover:not(:disabled) {
  background-color: #5a67d8;
}

.btn--secondary {
  background-color: #e2e8f0;
  color: #4a5568;
}

.btn--secondary:hover:not(:disabled) {
  background-color: #cbd5e0;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .modal-overlay {
    padding: 0.5rem;
  }

  .modal {
    max-height: 95vh;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 1rem;
    padding-right: 1rem;
  }
}
</style>