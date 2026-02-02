<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// Initialize auth state from localStorage on app startup
onMounted(() => {
  authStore.initializeAuth()
})
</script>

<template>
  <router-view />
</template>

<style>
/* Global styles - Tailwind handles most styling */
html {
  scroll-behavior: smooth;
}

body {
  font-feature-settings: 'rlig' 1, 'calt' 1;
}
</style>
