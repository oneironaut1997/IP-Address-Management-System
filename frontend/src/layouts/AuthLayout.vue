<script setup lang="ts">
/**
 * Auth Layout
 *
 * Layout component for authentication pages (login, register).
 * Provides a centered card layout with branding using Tailwind CSS.
 *
 * @package Layouts
 */

import { Shield } from 'lucide-vue-next'
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Card -->
      <div class="bg-card rounded-2xl shadow-2xl border border-border/50 overflow-hidden">
        <!-- Header -->
        <div class="px-8 pt-8 pb-6 text-center">
          <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-primary mb-4">
            <Shield class="w-6 h-6 text-primary-foreground" />
          </div>
          <h1 class="text-2xl font-bold tracking-tight">IP Management System</h1>
          <p class="text-muted-foreground mt-1">Secure IP Address Management</p>
        </div>

        <!-- Content -->
        <div class="px-8 pb-8">
          <router-view />
        </div>
      </div>

      <!-- Footer -->
      <p class="text-center text-white/60 text-sm mt-6">
        &copy; {{ new Date().getFullYear() }} IP Management System. All rights reserved.
      </p>
    </div>
  </div>
</template>
