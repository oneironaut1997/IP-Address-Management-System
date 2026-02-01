/**
 * IP Detail View
 *
 * Displays detailed information about a specific IP address
 * with options to edit or view history.
 *
 * @package Views/IP
 */

<template>
  <div class="ip-detail-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-content">
        <router-link to="/ip" class="back-link">← Back to IP List</router-link>
        <h1 class="page-title">IP Address Details</h1>
      </div>
      <div class="header-actions">
        <router-link :to="`/ip/${route.params.id}/history`" class="btn btn--secondary">
          📜 View History
        </router-link>
        <button
          v-if="canEdit"
          class="btn btn--primary"
          @click="showEditModal = true"
        >
          ✏️ Edit
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="ipStore.loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading IP details...</p>
    </div>

    <!-- IP Details Card -->
    <div v-else-if="ip" class="detail-card">
      <div class="detail-header">
        <div class="ip-display">
          <span class="ip-address">{{ ip.ip_address }}</span>
          <span class="badge" :class="`badge--${ip.type}`">
            {{ ip.type.toUpperCase() }}
          </span>
        </div>
      </div>

      <div class="detail-body">
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Label</span>
            <span class="detail-value">{{ ip.label }}</span>
          </div>

          <div class="detail-item">
            <span class="detail-label">Owner</span>
            <span class="detail-value">
              {{ isOwner ? 'You' : 'Another user' }}
              <span v-if="isOwner" class="owner-badge">Owner</span>
            </span>
          </div>

          <div class="detail-item">
            <span class="detail-label">Created</span>
            <span class="detail-value">{{ formatDate(ip.created_at) }}</span>
          </div>

          <div class="detail-item">
            <span class="detail-label">Last Updated</span>
            <span class="detail-value">{{ formatDate(ip.updated_at) }}</span>
          </div>
        </div>

        <div v-if="ip.comment" class="detail-section">
          <span class="detail-label">Comment</span>
          <p class="detail-comment">{{ ip.comment }}</p>
        </div>

        <div class="detail-section">
          <span class="detail-label">Quick Actions</span>
          <div class="quick-actions">
            <router-link :to="`/ip/${ip.id}/history`" class="action-link">
              View Full History →
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Not Found -->
    <div v-else class="empty-state">
      <div class="empty-icon">❓</div>
      <h3>IP Address Not Found</h3>
      <p>The requested IP address could not be found.</p>
      <router-link to="/ip" class="btn btn--primary">Back to IP List</router-link>
    </div>

    <!-- Edit Modal -->
    <EditIPForm
      v-if="showEditModal"
      :ip="ip"
      @close="showEditModal = false"
      @updated="handleUpdated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useIPStore } from '@/stores/ip'
import EditIPForm from '@/components/forms/EditIPForm.vue'
import type { IPAddress } from '@/types'

/**
 * Route
 */
const route = useRoute()

/**
 * Stores
 */
const authStore = useAuthStore()
const ipStore = useIPStore()

/**
 * State
 */
const showEditModal = ref(false)

/**
 * Computed
 */
const ip = computed<IPAddress | null>(() => {
  // Try to get from currentIP first
  if (ipStore.currentIP?.id === route.params.id) {
    return ipStore.currentIP
  }
  // Otherwise look in the list
  return ipStore.ips.find((i) => i.id === route.params.id) || null
})

const isOwner = computed(() => {
  if (!ip.value || !authStore.user) return false
  return ip.value.user_id === authStore.user.id
})

const canEdit = computed(() => {
  return isOwner.value || authStore.isSuperAdmin
})

/**
 * Format date for display
 */
function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

/**
 * Handle IP updated
 */
function handleUpdated(): void {
  showEditModal.value = false
}

/**
 * Fetch IPs on mount if not already loaded
 */
onMounted(() => {
  if (ip.value) {
    ipStore.setCurrentIP(ip.value)
  } else if (!ipStore.ips.length) {
    ipStore.fetchIPs()
  }
})
</script>

<style scoped>
.ip-detail-view {
  padding: 2rem;
  max-width: 800px;
  margin: 0 auto;
}

.view-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.back-link {
  display: inline-block;
  color: #667eea;
  text-decoration: none;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.back-link:hover {
  text-decoration: underline;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
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

.btn--secondary {
  background-color: #e2e8f0;
  color: #4a5568;
}

.btn--secondary:hover {
  background-color: #cbd5e0;
}

/* Loading State */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #718096;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Detail Card */
.detail-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.detail-header {
  padding: 1.5rem;
  background-color: #f7fafc;
  border-bottom: 1px solid #e2e8f0;
}

.ip-display {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.ip-address {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a202c;
}

.badge {
  display: inline-block;
  padding: 0.375rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge--ipv4 {
  background-color: #c6f6d5;
  color: #22543d;
}

.badge--ipv6 {
  background-color: #bee3f8;
  color: #2a4365;
}

.detail-body {
  padding: 1.5rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #718096;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.detail-value {
  font-size: 0.875rem;
  color: #2d3748;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.owner-badge {
  padding: 0.125rem 0.375rem;
  background-color: #c6f6d5;
  color: #22543d;
  border-radius: 4px;
  font-size: 0.625rem;
  font-weight: 600;
}

.detail-section {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

.detail-comment {
  margin: 0.5rem 0 0 0;
  padding: 0.75rem;
  background-color: #f7fafc;
  border-radius: 6px;
  font-size: 0.875rem;
  color: #4a5568;
  line-height: 1.6;
}

.quick-actions {
  margin-top: 0.5rem;
}

.action-link {
  color: #667eea;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
}

.action-link:hover {
  text-decoration: underline;
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: #1a202c;
}

.empty-state p {
  color: #718096;
  margin: 0 0 1.5rem 0;
}

/* Responsive */
@media (max-width: 640px) {
  .ip-detail-view {
    padding: 1rem;
  }

  .view-header {
    flex-direction: column;
    gap: 1rem;
  }

  .header-actions {
    width: 100%;
    flex-direction: column;
  }

  .ip-address {
    font-size: 1.25rem;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
