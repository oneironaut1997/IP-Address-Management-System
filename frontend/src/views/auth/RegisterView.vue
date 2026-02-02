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
import { Mail, Lock, Loader2, ArrowRight, CheckCircle } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

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

<template>
  <div class="space-y-6">
    <div class="text-center">
      <h2 class="text-xl font-semibold">Create Account</h2>
      <p class="text-sm text-muted-foreground mt-1">Get started with your free account</p>
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
            placeholder="name@company.com"
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
            placeholder="Create a password (min 8 characters)"
            required
            autocomplete="new-password"
          />
        </div>
        <p v-if="errors.password" class="text-xs text-destructive">{{ errors.password }}</p>
      </div>

      <!-- Confirm Password Field -->
      <div class="space-y-2">
        <label for="password_confirmation" class="text-sm font-medium">Confirm Password</label>
        <div class="relative">
          <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            :class="cn(
              'flex w-full rounded-lg border bg-background px-3 py-2.5 pl-10 text-sm ring-offset-background transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
              errors.password_confirmation ? 'border-destructive focus-visible:ring-destructive' : 'border-input'
            )"
            placeholder="Confirm your password"
            required
            autocomplete="new-password"
          />
        </div>
        <p v-if="errors.password_confirmation" class="text-xs text-destructive">{{ errors.password_confirmation }}</p>
      </div>

      <!-- Error Message -->
      <div
        v-if="authStore.error"
        class="p-3 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-sm"
      >
        {{ authStore.error }}
      </div>

      <!-- Success Message -->
      <div
        v-if="successMessage"
        class="p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 text-sm flex items-center gap-2"
      >
        <CheckCircle class="w-4 h-4" />
        {{ successMessage }}
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        :disabled="authStore.loading || !!successMessage"
        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground ring-offset-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
      >
        <Loader2 v-if="authStore.loading" class="w-4 h-4 animate-spin" />
        <span v-if="authStore.loading">Creating account...</span>
        <template v-else>
          <span>Create Account</span>
          <ArrowRight class="w-4 h-4" />
        </template>
      </button>
    </form>

    <!-- Login Link -->
    <div class="text-center text-sm">
      <span class="text-muted-foreground">Already have an account?</span>
      <router-link
        to="/login"
        class="ml-1 font-medium text-primary hover:text-primary/90 underline-offset-4 hover:underline"
      >
        Sign in
      </router-link>
    </div>
  </div>
</template>
