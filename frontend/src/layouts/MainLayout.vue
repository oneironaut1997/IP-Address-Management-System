<script setup lang="ts">
/**
 * Main Layout
 *
 * Layout component for the admin panel with sidebar navigation.
 * Provides a responsive sidebar with navigation links, user info,
 * and logout functionality.
 *
 * @package Layouts
 */

import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { cn } from '@/lib/utils'
import {
  LayoutDashboard,
  Network,
  ScrollText,
  Shield,
  User,
  LogOut,
  Menu,
  X,
  ChevronRight,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const isSidebarOpen = ref(false)

const navigation = computed(() => [
  { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, current: route.path === '/dashboard' },
  { name: 'IP Addresses', href: '/ip', icon: Network, current: route.path.startsWith('/ip') },
  ...(authStore.isSuperAdmin
    ? [{ name: 'Audit Logs', href: '/audit', icon: ScrollText, current: route.path === '/audit' }]
    : []),
])

function toggleSidebar() {
  isSidebarOpen.value = !isSidebarOpen.value
}

function closeSidebar() {
  isSidebarOpen.value = false
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}

function isCurrentRoute(href: string): boolean {
  return route.path === href || route.path.startsWith(href + '/')
}
</script>

<template>
  <div class="min-h-screen bg-background flex">
    <!-- Mobile sidebar overlay -->
    <div
      v-if="isSidebarOpen"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden"
      @click="closeSidebar"
    />

    <!-- Sidebar -->
    <aside
      :class="cn(
        'fixed inset-y-0 left-0 z-50 w-64 bg-card border-r border-border transform transition-transform duration-200 ease-in-out lg:translate-x-0 lg:static lg:inset-0',
        isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
      )"
    >
      <!-- Logo -->
      <div class="flex h-16 items-center px-6 border-b border-border">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center">
            <Shield class="w-5 h-5 text-primary-foreground" />
          </div>
          <span class="font-semibold text-lg">IP Admin</span>
        </div>
        <button
          class="ml-auto lg:hidden p-2 rounded-md hover:bg-accent"
          @click="closeSidebar"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4 px-3">
        <ul class="space-y-1">
          <li v-for="item in navigation" :key="item.name">
            <router-link
              :to="item.href"
              :class="cn(
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                isCurrentRoute(item.href)
                  ? 'bg-primary text-primary-foreground'
                  : 'text-muted-foreground hover:bg-accent hover:text-foreground'
              )"
              @click="closeSidebar"
            >
              <component :is="item.icon" class="w-5 h-5" />
              {{ item.name }}
              <ChevronRight
                v-if="isCurrentRoute(item.href)"
                class="w-4 h-4 ml-auto"
              />
            </router-link>
          </li>
        </ul>

        <!-- User Section -->
        <div class="mt-8 pt-4 border-t border-border">
          <p class="px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">
            Account
          </p>
          <ul class="space-y-1">
            <li>
              <button
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors"
                @click="handleLogout"
              >
                <LogOut class="w-5 h-5" />
                Sign out
              </button>
            </li>
          </ul>
        </div>
      </nav>

      <!-- User Info -->
      <div class="border-t border-border p-4">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center">
            <User class="w-5 h-5 text-primary" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ authStore.user?.email }}</p>
            <p class="text-xs text-muted-foreground capitalize">{{ authStore.user?.role }}</p>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <div class="w-full">
      <!-- Top header -->
      <header class="sticky top-0 z-30 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b border-border">
        <div class="flex h-16 items-center gap-4 px-4 sm:px-6 lg:px-8">
          <button
            class="lg:hidden p-2 -ml-2 rounded-md hover:bg-accent"
            @click="toggleSidebar"
          >
            <Menu class="w-5 h-5" />
          </button>

          <!-- Breadcrumb -->
          <nav class="flex-1 hidden sm:flex" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm text-muted-foreground">
              <li>
                <router-link to="/dashboard" class="hover:text-foreground">
                  Home
                </router-link>
              </li>
              <li v-if="route.path !== '/dashboard'">
                <span class="text-border">/</span>
              </li>
              <li v-if="route.path !== '/dashboard'" class="capitalize text-foreground">
                {{ route.name?.toString().replace(/-/g, ' ') }}
              </li>
            </ol>
          </nav>

          <!-- Right section -->
          <div class="flex items-center gap-4 ml-auto">
            <span
              v-if="authStore.isSuperAdmin"
              class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-destructive/10 text-destructive"
            >
              <Shield class="w-3 h-3" />
              Admin
            </span>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="p-4 sm:p-6 lg:p-8">
        <router-view />
      </main>
    </div>
  </div>
</template>
