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
import { Mail, Lock, Loader2, ArrowRight } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

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

<template>
  <div class="space-y-6">
    <div class="text-center">
      <h2 class="text-xl font-semibold">Welcome back</h2>
      <p class="text-sm text-muted-foreground mt-1">Sign in to your account</p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Email Field -->
      <div class="space-y-2">
        <label for="email" class="text-sm font-medium">Email Address</label>
        <div class="relative">
          <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <input
            id="email"
            v-model="form.email"
            type="email"
            :class="cn(
              'flex w-full rounded-lg border bg-background px-3 py-2.5 pl-10 text-sm ring-offset-background transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
              errors.email ? 'border-destructive focus-visible:ring-destructive' : 'border-input'
            )"
            placeholder="admin@example.com"
            required
            autocomplete="email"
          />
        </div>
        <p v-if="errors.email" class="text-xs text-destructive">{{ errors.email }}</p>
      </div>

      <!-- Password Field -->
      <div class="space-y-2">
        <label for="password" class="text-sm font-medium">Password</label>
        <div class="relative">
          <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <input
            id="password"
            v-model="form.password"
            type="password"
            :class="cn(
              'flex w-full rounded-lg border bg-background px-3 py-2.5 pl-10 text-sm ring-offset-background transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
              errors.password ? 'border-destructive focus-visible:ring-destructive' : 'border-input'
            )"
            placeholder="Enter your password"
            required
            autocomplete="current-password"
          />
        </div>
        <p v-if="errors.password" class="text-xs text-destructive">{{ errors.password }}</p>
      </div>

      <!-- Error Message -->
      <div
        v-if="authStore.error"
        class="p-3 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-sm"
      >
        {{ authStore.error }}
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        :disabled="authStore.loading"
        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground ring-offset-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
      >
        <Loader2 v-if="authStore.loading" class="w-4 h-4 animate-spin" />
        <span v-if="authStore.loading">Signing in...</span>
        <template v-else>
          <span>Sign In</span>
          <ArrowRight class="w-4 h-4" />
        </template>
      </button>
    </form>

    <!-- Register Link -->
    <div class="text-center text-sm">
      <span class="text-muted-foreground">Don't have an account?</span>
      <router-link
        to="/register"
        class="ml-1 font-medium text-primary hover:text-primary/90 underline-offset-4 hover:underline"
      >
        Create one
      </router-link>
    </div>
  </div>
</template>
