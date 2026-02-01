/**
 * Dashboard View
 *
 * Main dashboard for authenticated users showing overview
 * of the IP Management System.
 *
 * @package Views/Dashboard
 */

<template>
  <div class="dashboard-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-content">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">
          Welcome back, {{ authStore.user?.email || 'User' }}
        </p>
      </div>
      <router-link to="/ip" class="btn btn--primary">
        View All IPs →
      </router-link>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon stat-icon--ip">🌐</div>
        <div class="stat-content">
          <span class="stat-value">{{ ipStore.totalCount }}</span>
          <span class="stat-label">Total IP Addresses</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon stat-icon--ipv4">📡</div>
        <div class="stat-content">
          <span class="stat-value">{{ ipStore.ipv4Addresses.length }}</span>
          <span class="stat-label">IPv4 Addresses</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon stat-icon--ipv6">🌐</div>
        <div class="stat-content">
          <span class="stat-value">{{ ipStore.ipv6Addresses.length }}</span>
          <span class="stat-label">IPv6 Addresses</span>
        </div>
      </div>

      <div v-if="authStore.isSuperAdmin" class="stat-card">
        <div class="stat-icon stat-icon--admin">👑</div>
        <div class="stat-content">
          <span class="stat-value">Admin</span>
          <span class="stat-label">Access Level</span>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="section">
      <h2 class="section-title">Quick Actions</h2>
      <div class="action-grid">
        <router-link to="/ip" class="action-card">
          <span class="action-icon">📋</span>
          <span class="action-label">View IP List</span>
          <span class="action-description">Browse all IP addresses</span>
        </router-link>

        <button class="action-card" @click="showAddModal = true">
          <span class="action-icon">➕</span>
          <span class="action-label">Add New IP</span>
          <span class="action-description">Create a new IP address entry</span>
        </button>

        <router-link
          v-if="authStore.isSuperAdmin"
          to="/audit"
          class="action-card action-card--admin"
        >
          <span class="action-icon">📜</span>
          <span class="action-label">Audit Logs</span>
          <span class="action-description">View system audit trail</span>
        </router-link>
      </div>
    </div>

    <!-- Recent IPs -->
    <div class="section">
      <div class="section-header">
        <h2 class="section-title">Recent IP Addresses</h2>
        <router-link to="/ip" class="section-link">View All →</router-link>
      </div>

      <!-- Loading State -->
      <div v-if="ipStore.loading && !ipStore.ips.length" class="loading-state">
        <div class="spinner"></div>
        <p>Loading...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="!ipStore.ips.length" class="empty-state">
        <p>No IP addresses yet.</p>
        <button class="btn btn--primary" @click="showAddModal = true">
          Add Your First IP
        </button>
      </div>

      <!-- IP List -->
      <div v-else class="recent-list">
        <div
          v-for="ip in recentIPs"
          :key="ip.id"
          class="recent-item"
        >
          <div class="recent-info">
            <router-link :to="`/ip/${ip.id}`" class="recent-ip">
              {{ ip.ip_address }}
            </router-link>
            <span class="recent-label">{{ ip.label }}</span>
          </div>
          <div class="recent-meta">
            <span class="badge" :class="`badge--${ip.type}`">
              {{ ip.type.toUpperCase() }}
            </span>
            <span class="recent-date">{{ formatDate(ip.created_at) }}</span>
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

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useIPStore } from '@/stores/ip'
import AddIPForm from '@/components/forms/AddIPForm.vue'

/**
 * Stores
 */
const authStore = useAuthStore()
const ipStore = useIPStore()

/**
 * State
 */
const showAddModal = ref(false)

/**
 * Computed
 */
const recentIPs = computed(() => {
  // Get the 5 most recent IPs
  return [...ipStore.ips]
    .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    .slice(0, 5)
})

/**
 * Format date for display
 */
function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
  })
}

/**
 * Handle IP created
 */
function handleCreated(): void {
  // Refresh is handled by store
}

/**
 * Fetch IPs on mount
 */
onMounted(() => {
  ipStore.fetchIPs()
})
</script>

<style scoped>
.dashboard-view {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.view-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 0.25rem 0;
}

.page-subtitle {
  color: #718096;
  margin: 0;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s;
  text-decoration: none;
}

.btn--primary {
  background-color: #667eea;
  color: white;
}

.btn--primary:hover {
  background-color: #5a67d8;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: white;
  padding: 1.25rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.stat-icon--ip {
  background-color: #e0e7ff;
}

.stat-icon--ipv4 {
  background-color: #c6f6d5;
}

.stat-icon--ipv6 {
  background-color: #bee3f8;
}

.stat-icon--admin {
  background-color: #fed7e2;
}

.stat-content {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a202c;
}

.stat-label {
  font-size: 0.875rem;
  color: #718096;
}

/* Sections */
.section {
  margin-bottom: 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.section-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
}

.section-link {
  color: #667eea;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
}

.section-link:hover {
  text-decoration: underline;
}

/* Action Grid */
.action-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.action-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 1.25rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  text-decoration: none;
  color: inherit;
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.action-card:hover {
  border-color: #667eea;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.action-card--admin {
  background-color: #fff5f5;
  border-color: #fed7d7;
}

.action-card--admin:hover {
  border-color: #f56565;
}

.action-icon {
  font-size: 1.5rem;
}

.action-label {
  font-weight: 600;
  color: #1a202c;
}

.action-description {
  font-size: 0.875rem;
  color: #718096;
}

/* Loading State */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: #718096;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e2e8f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 0.75rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem;
  text-align: center;
  color: #718096;
}

.empty-state p {
  margin: 0 0 1rem 0;
}

/* Recent List */
.recent-list {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.recent-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}

.recent-item:last-child {
  border-bottom: none;
}

.recent-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.recent-ip {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
}

.recent-ip:hover {
  text-decoration: underline;
}

.recent-label {
  font-size: 0.875rem;
  color: #718096;
}

.recent-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.badge--ipv4 {
  background-color: #c6f6d5;
  color: #22543d;
}

.badge--ipv6 {
  background-color: #bee3f8;
  color: #2a4365;
}

.recent-date {
  font-size: 0.875rem;
  color: #a0aec0;
}

/* Responsive */
@media (max-width: 768px) {
  .dashboard-view {
    padding: 1rem;
  }

  .view-header {
    flex-direction: column;
    gap: 1rem;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .action-grid {
    grid-template-columns: 1fr;
  }

  .recent-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
