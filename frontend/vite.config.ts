import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  build: {
    // Enable minification for production
    minify: 'esbuild',
    // Configure chunk splitting for better caching
    rollupOptions: {
      output: {
        manualChunks: {
          // Core Vue ecosystem
          'vendor': ['vue', 'vue-router', 'pinia'],
          // UI components and utilities
          'ui': ['lucide-vue-next', 'class-variance-authority', 'clsx', 'tailwind-merge'],
          // HTTP client
          'http': ['axios'],
        },
      },
    },
    // Increase chunk size warning limit
    chunkSizeWarningLimit: 500,
  },
  // Optimize dependency pre-bundling
  optimizeDeps: {
    include: ['vue', 'vue-router', 'pinia', 'axios'],
  },
})
