<template>
  <div class="register-view">
    <h2 class="form-title">Create Account</h2>

    <form @submit.prevent="handleSubmit" class="form">
      <!-- Email Field -->
      <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          class="form-input"
          :class="{ 'form-input--error': errors.email }"
          placeholder="Enter your email"
          required
          autocomplete="email"
        />
        <span v-if="errors.email" class="form-error">{{ errors.email }}</span>
      </div>

      <!-- Password Field -->
      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          class="form-input"
          :class="{ 'form-input--error': errors.password }"
          placeholder="Create a password (min 8 characters)"
          required
          autocomplete="new-password"
        />
        <span v-if="errors.password" class="form-error">{{ errors.password }}</span>
      </div>

      <!-- Confirm Password Field -->
      <div class="form-group">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          class="form-input"
          :class="{ 'form-input--error': errors.password_confirmation }"
          placeholder="Confirm your password"
          required
          autocomplete="new-password"
        />
        <span v-if="errors.password_confirmation" class="form-error">
          {{ errors.password_confirmation }}
        </span>
      </div>

      <!-- Error Message -->
      <div v-if="authStore.error" class="alert alert--error">
        {{ authStore.error }}
      </div>

      <!-- Success Message -->
      <div v-if="successMessage" class="alert alert--success">
        {{ successMessage }}
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        class="btn btn--primary btn--full"
        :disabled="authStore.loading"
      >
        <span v-if="authStore.loading">Creating account...</span>
        <span v-else>Create Account</span>
      </button>
    </form>

    <!-- Login Link -->
    <div class="form-footer">
      <p>
        Already have an account?
        <router-link to="/login" class="form-link">Sign in</router-link>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Register View
 *
 * Provides the registration form for new user accounts.
 * Validates input and handles registration via the auth store.
 *
 * @package Views/Auth
 */

import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import type { RegistrationData } from '@/types'

const router = useRouter()
const authStore = useAuthStore()

// Success message state
const successMessage = ref('')

// Form state
const form = reactive<RegistrationData>({
  email: '',
  password: '',
  password_confirmation: '',
})

// Validation errors
const errors = reactive({
  email: '',
  password: '',
  password_confirmation: '',
})

/**
 * Validate form inputs
 *
 * @returns True if form is valid
 */
function validateForm(): boolean {
  let isValid = true

  // Reset errors
  errors.email = ''
  errors.password = ''
  errors.password_confirmation = ''

  // Email validation
  if (!form.email) {
    errors.email = 'Email is required'
    isValid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Please enter a valid email address'
    isValid = false
  }

  // Password validation
  if (!form.password) {
    errors.password = 'Password is required'
    isValid = false
  } else if (form.password.length < 8) {
    errors.password = 'Password must be at least 8 characters'
    isValid = false
  }

  // Password confirmation validation
  if (!form.password_confirmation) {
    errors.password_confirmation = 'Please confirm your password'
    isValid = false
  } else if (form.password !== form.password_confirmation) {
    errors.password_confirmation = 'Passwords do not match'
    isValid = false
  }

  return isValid
}

/**
 * Handle form submission
 */
async function handleSubmit(): Promise<void> {
  successMessage.value = ''

  if (!validateForm()) return

  try {
    await authStore.register(form)
    successMessage.value = 'Account created successfully! Redirecting to login...'

    // Redirect to login after short delay
    setTimeout(() => {
      router.push('/login')
    }, 2000)
  } catch {
    // Error is handled by the store
  }
}
</script>

<style scoped>
.register-view {
  width: 100%;
}

.form-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 1.5rem 0;
  text-align: center;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #4a5568;
}

.form-input {
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

.form-error {
  font-size: 0.75rem;
  color: #f56565;
}

.alert {
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
}

.alert--error {
  background-color: #fff5f5;
  color: #c53030;
  border: 1px solid #feb2b2;
}

.alert--success {
  background-color: #f0fff4;
  color: #276749;
  border: 1px solid #9ae6b4;
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

.btn--full {
  width: 100%;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-footer {
  margin-top: 1.5rem;
  text-align: center;
  font-size: 0.875rem;
  color: #718096;
}

.form-link {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
}

.form-link:hover {
  text-decoration: underline;
}
</style>
