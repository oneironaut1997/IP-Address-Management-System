/**
 * Edit IP Form Component
 *
 * Modal form for editing an existing IP address.
 * Allows updating label and comment (IP address itself is immutable).
 *
 * @package Components/Forms
 */

<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal">
      <div class="modal-header">
        <h3 class="modal-title">Edit IP Address</h3>
        <button class="modal-close" @click="$emit('close')" aria-label="Close">&times;</button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
        <!-- IP Address (Read-only) -->
        <div class="form-group">
          <label class="form-label">IP Address</label>
          <div class="ip-display">
            <span class="ip-address">{{ ip?.ip_address }}</span>
            <span class="ip-type-badge" :class="`ip-type--${ip?.type}`">
              {{ ip?.type === 'ipv4' ? 'IPv4' : 'IPv6' }}
            </span>
          </div>
        </div>

        <!-- Owner Info -->
        <div class="form-group">
          <label class="form-label">Owner</label>
          <div class="owner-display">
            {{ isOwner ? 'You' : 'Another user' }}
            <span v-if="authStore.isSuperAdmin" class="admin-badge">Admin Edit</span>
          </div>
        </div>

        <!-- Label Field -->
        <div class="form-group">
          <label for="edit-label" class="form-label">
            Label <span class="required">*</span>
          </label>
          <input
            id="edit-label"
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
          <label for="edit-comment" class="form-label">Comment</label>
          <textarea
            id="edit-comment"
            v-model="form.comment"
            class="form-input form-textarea"
            :class="{ 'form-input--error': errors.comment }"
            placeholder="Optional description or notes..."
            rows="3"
            maxlength="1000"
          ></textarea>
          <span v-if="errors.comment" class="form-error">{{ errors.comment }}</span>
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
        <button type="submit" class="btn btn--primary" @click="handleSubmit" :disabled="ipStore.loading || !canEdit">
          <span v-if="ipStore.loading">Saving...</span>
          <span v-else>Save Changes</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useIPStore } from '@/stores/ip'
import type { IPAddress } from '@/types'

/**
 * Props
 */
interface Props {
  ip: IPAddress | null
}

const props = defineProps<Props>()

/**
 * Emits
 */
interface Emits {
  (e: 'close'): void
  (e: 'updated'): void
}

const emit = defineEmits<Emits>()

/**
 * Stores
 */
const authStore = useAuthStore()
const ipStore = useIPStore()

/**
 * Computed
 */
const isOwner = computed(() => {
  if (!props.ip || !authStore.user) return false
  return props.ip.user_id === authStore.user.id
})

const canEdit = computed(() => {
  return isOwner.value || authStore.isSuperAdmin
})

/**
 * Form State
 */
const form = reactive({
  label: '',
  comment: '',
})

const errors = reactive({
  label: '',
  comment: '',
})

/**
 * Watch for IP changes and populate form
 */
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

/**
 * Validate form
 */
function validateForm(): boolean {
  let isValid = true

  // Reset errors
  errors.label = ''
  errors.comment = ''

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
  if (!canEdit.value) {
    return
  }

  if (!validateForm()) return

  if (!props.ip) return

  try {
    await ipStore.updateIP(props.ip.id, {
      label: form.label.trim(),
      comment: form.comment?.trim() || undefined,
    })

    emit('updated')
    emit('close')
  } catch {
    // Error is handled by the store
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

.ip-display {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0.875rem;
  background-color: #f7fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
}

.ip-address {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.875rem;
  color: #2d3748;
}

.ip-type-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.ip-type--ipv4 {
  background-color: #c6f6d5;
  color: #22543d;
}

.ip-type--ipv6 {
  background-color: #bee3f8;
  color: #2a4365;
}

.owner-display {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 0.875rem;
  background-color: #f7fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.875rem;
  color: #4a5568;
}

.admin-badge {
  padding: 0.125rem 0.375rem;
  background-color: #fed7e2;
  color: #97266d;
  border-radius: 4px;
  font-size: 0.625rem;
  font-weight: 600;
  text-transform: uppercase;
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
