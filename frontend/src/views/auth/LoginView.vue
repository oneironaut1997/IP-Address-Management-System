<template>
  <div class="login-view">
    <h2 class="form-title">Sign In</h2>

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
          placeholder="Enter your password"
          required
          autocomplete="current-password"
        />
        <span v-if="errors.password" class="form-error">{{ errors.password }}</span>
      </div>

      <!-- Error Message -->
      <div v-if="authStore.error" class="alert alert--error">
        {{ authStore.error }}
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        class="btn btn--primary btn--full"
        :disabled="authStore.loading"
      >
        <span v-if="authStore.loading">Signing in...</span>
        <span v-else>Sign In</span>
      </button>
    </form>

    <!-- Register Link -->
    <div class="form-footer">
      <p>
        Don't have an account?
        <router-link to="/register" class="form-link">Create one</router-link>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Login View
 *
 * Provides the login form for user authentication.
 * Validates input and handles authentication via the auth store.
 *
 * @package Views/Auth
 */

import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import type { LoginCredentials } from '@/types'

const router = useRouter()
const authStore = useAuthStore()

// Form state
const form = reactive<LoginCredentials>({
  email: '',
  password: '',
})

// Validation errors
const errors = reactive({
  email: '',
  password: '',
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

  return isValid
}

/**
 * Handle form submission
 */
async function handleSubmit(): Promise<void> {
  if (!validateForm()) return

  try {
    await authStore.login(form)
    // Redirect to dashboard on successful login
    router.push('/dashboard')
  } catch {
    // Error is handled by the store
  }
}
</script>

<style scoped>
.login-view {
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
