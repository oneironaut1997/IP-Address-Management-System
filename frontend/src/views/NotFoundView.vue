<script setup lang="ts">
/**
 * Not Found View
 *
 * Displayed when a route is not found.
 *
 * @package Views
 */

import { Home, AlertCircle } from 'lucide-vue-next'
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-4">
    <div class="text-center space-y-6 max-w-md">
      <!-- Icon -->
      <div class="w-24 h-24 rounded-full bg-primary/10 flex items-center justify-center mx-auto">
        <AlertCircle class="w-12 h-12 text-primary" />
      </div>

      <!-- Content -->
      <div class="space-y-2">
        <h1 class="text-6xl font-bold text-primary">404</h1>
        <h2 class="text-2xl font-semibold">Page Not Found</h2>
        <p class="text-muted-foreground">
          The page you're looking for doesn't exist or has been moved.
        </p>
      </div>

      <!-- Action -->
      <router-link
        to="/"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
      >
        <Home class="w-4 h-4" />
        Go Home
      </router-link>
    </div>
  </div>
</template>
