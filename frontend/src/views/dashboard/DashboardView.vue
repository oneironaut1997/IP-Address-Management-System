<script setup lang="ts">
/**
 * Dashboard View
 *
 * Main dashboard for authenticated users showing overview
 * of the IP Management System.
 *
 * @package Views/Dashboard
 */

import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useIPStore } from '@/stores/ip'
import AddIPForm from '@/components/forms/AddIPForm.vue'
import {
  Network,
  Globe,
  Server,
  Shield,
  Plus,
  List,
  ScrollText,
  ArrowRight,
  Loader2,
} from 'lucide-vue-next'

const authStore = useAuthStore()
const ipStore = useIPStore()

const showAddModal = ref(false)

const recentIPs = computed(() => {
  return [...ipStore.ips]
    .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    .slice(0, 5)
})

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  })
}

function handleCreated(): void {
  // Refresh is handled by store
}

onMounted(() => {
  ipStore.fetchIPs()
})
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
        <p class="text-muted-foreground">
          Welcome back, {{ authStore.user?.email || 'User' }}
        </p>
      </div>
      <router-link
        to="/ip"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground ring-offset-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
      >
        <List class="w-4 h-4" />
        View All IPs
        <ArrowRight class="w-4 h-4" />
      </router-link>
    </div>

    <!-- Stats Cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <!-- Total IPs -->
      <div class="rounded-xl border bg-card p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
            <Network class="w-6 h-6 text-primary" />
          </div>
          <div>
            <p class="text-sm font-medium text-muted-foreground">Total IPs</p>
            <p class="text-2xl font-bold">{{ ipStore.totalCount }}</p>
          </div>
        </div>
      </div>

      <!-- IPv4 -->
      <div class="rounded-xl border bg-card p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-lg bg-emerald-500/10 flex items-center justify-center">
            <Globe class="w-6 h-6 text-emerald-500" />
          </div>
          <div>
            <p class="text-sm font-medium text-muted-foreground">IPv4</p>
            <p class="text-2xl font-bold">{{ ipStore.ipv4Addresses.length }}</p>
          </div>
        </div>
      </div>

      <!-- IPv6 -->
      <div class="rounded-xl border bg-card p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-lg bg-blue-500/10 flex items-center justify-center">
            <Server class="w-6 h-6 text-blue-500" />
          </div>
          <div>
            <p class="text-sm font-medium text-muted-foreground">IPv6</p>
            <p class="text-2xl font-bold">{{ ipStore.ipv6Addresses.length }}</p>
          </div>
        </div>
      </div>

      <!-- Admin Badge -->
      <div v-if="authStore.isSuperAdmin" class="rounded-xl border bg-card p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-lg bg-rose-500/10 flex items-center justify-center">
            <Shield class="w-6 h-6 text-rose-500" />
          </div>
          <div>
            <p class="text-sm font-medium text-muted-foreground">Access</p>
            <p class="text-2xl font-bold">Admin</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div>
      <h2 class="text-lg font-semibold mb-4">Quick Actions</h2>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <router-link
          to="/ip"
          class="group rounded-xl border bg-card p-6 hover:border-primary/50 hover:shadow-sm transition-all"
        >
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
              <List class="w-5 h-5 text-primary" />
            </div>
            <div>
              <h3 class="font-semibold">View IP List</h3>
              <p class="text-sm text-muted-foreground mt-1">Browse all IP addresses</p>
            </div>
          </div>
        </router-link>

        <button
          class="group text-left rounded-xl border bg-card p-6 hover:border-primary/50 hover:shadow-sm transition-all"
          @click="showAddModal = true"
        >
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
              <Plus class="w-5 h-5 text-primary" />
            </div>
            <div>
              <h3 class="font-semibold">Add New IP</h3>
              <p class="text-sm text-muted-foreground mt-1">Create a new IP address entry</p>
            </div>
          </div>
        </button>

        <router-link
          v-if="authStore.isSuperAdmin"
          to="/activity"
          class="group rounded-xl border bg-rose-50/50 p-6 hover:border-rose-200 hover:shadow-sm transition-all"
        >
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-rose-500/10 flex items-center justify-center group-hover:bg-rose-500/20 transition-colors">
              <ScrollText class="w-5 h-5 text-rose-500" />
            </div>
            <div>
              <h3 class="font-semibold text-rose-700">Activity Logs</h3>
              <p class="text-sm text-rose-600/70 mt-1">View system activity trail</p>
            </div>
          </div>
        </router-link>
      </div>
    </div>

    <!-- Recent IPs -->
    <div>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Recent IP Addresses</h2>
        <router-link
          to="/ip"
          class="text-sm text-primary hover:text-primary/90 font-medium"
        >
          View All
        </router-link>
      </div>

      <!-- Loading State -->
      <div
        v-if="ipStore.loading && !ipStore.ips.length"
        class="flex flex-col items-center justify-center py-12 rounded-xl border bg-card"
      >
        <Loader2 class="w-8 h-8 animate-spin text-primary mb-3" />
        <p class="text-muted-foreground">Loading...</p>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="!ipStore.ips.length"
        class="flex flex-col items-center justify-center py-12 rounded-xl border bg-card"
      >
        <Network class="w-12 h-12 text-muted-foreground/50 mb-4" />
        <h3 class="text-lg font-medium mb-1">No IP addresses yet</h3>
        <p class="text-muted-foreground text-sm mb-4">Get started by adding your first IP address</p>
        <button
          class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
          @click="showAddModal = true"
        >
          <Plus class="w-4 h-4" />
          Add Your First IP
        </button>
      </div>

      <!-- IP List -->
      <div v-else class="rounded-xl border bg-card overflow-hidden">
        <div class="divide-y">
          <div
            v-for="ip in recentIPs"
            :key="ip.id"
            class="flex items-center justify-between p-4 hover:bg-muted/50 transition-colors"
          >
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                <Globe class="w-5 h-5 text-primary" />
              </div>
              <div>
                <router-link
                  :to="`/ip/${ip.id}`"
                  class="font-medium font-mono text-primary hover:underline"
                >
                  {{ ip.ip_address }}
                </router-link>
                <p class="text-sm text-muted-foreground">{{ ip.label }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="ip.type === 'ipv4' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'"
              >
                {{ ip.type.toUpperCase() }}
              </span>
              <span class="text-sm text-muted-foreground">{{ formatDate(ip.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add IP Modal -->
    <AddIPForm
      v-if="showAddModal"
      @close="showAddModal = false"
      @created="handleCreated"
    />
  </div>
</template>
