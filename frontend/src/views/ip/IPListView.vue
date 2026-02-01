/**
 * IP List View
 *
 * Displays a table of all IP addresses with actions for viewing,
 * editing (if owner/admin), and deleting (admin only).
 *
 * @package Views/IP
 */

<template>
  <div class="ip-list-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-content">
        <h1 class="page-title">IP Addresses</h1>
        <p class="page-subtitle">Manage and view all IP addresses in the system</p>
      </div>
      <button class="btn btn--primary" @click="showAddModal = true">
        <span class="btn-icon">+</span>
        Add IP Address
      </button>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <span class="stat-value">{{ ipStore.totalCount }}</span>
        <span class="stat-label">Total IPs</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ ipStore.ipv4Addresses.length }}</span>
        <span class="stat-label">IPv4</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ ipStore.ipv6Addresses.length }}</span>
        <span class="stat-label">IPv6</span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="ipStore.loading && !ipStore.ips.length" class="loading-state">
      <div class="spinner"></div>
      <p>Loading IP addresses...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="!ipStore.ips.length" class="empty-state">
      <div class="empty-icon">🌐</div>
      <h3>No IP Addresses</h3>
      <p>Get started by adding your first IP address.</p>
      <button class="btn btn--primary" @click="showAddModal = true">
        Add IP Address
      </button>
    </div>

    <!-- IP Table -->
    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>IP Address</th>
            <th>Label</th>
            <th>Type</th>
            <th>Owner</th>
            <th>Created</th>
            <th class="actions-column">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ip in ipStore.ips" :key="ip.id" class="table-row">
            <td>
              <router-link :to="`/ip/${ip.id}`" class="ip-link">
                {{ ip.ip_address }}
              </router-link>
            </td>
            <td>{{ ip.label }}</td>
            <td>
              <span class="badge" :class="`badge--${ip.type}`">
                {{ ip.type.toUpperCase() }}
              </span>
            </td>
            <td>
              <span :class="{ 'current-user': isCurrentUser(ip.user_id) }">
                {{ isCurrentUser(ip.user_id) ? 'You' : 'User' }}
              </span>
            </td>
            <td>{{ formatDate(ip.created_at) }}</td>
            <td class="actions-cell">
              <router-link :to="`/ip/${ip.id}`" class="action-btn" title="View Details">
                👁
              </router-link>
              <router-link :to="`/ip/${ip.id}/history`" class="action-btn" title="View History">
                📜
              </router-link>
              <button
                v-if="canEdit(ip)"
                class="action-btn"
                title="Edit"
                @click="openEditModal(ip)"
              >
                ✏️
              </button>
              <button
                v-if="authStore.isSuperAdmin"
                class="action-btn action-btn--danger"
                title="Delete"
                @click="confirmDelete(ip)"
              >
                🗑
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add IP Modal -->
    <AddIPForm
      v-if="showAddModal"
      @close="showAddModal = false"
      @created="handleCreated"
    />

    <!-- Edit IP Modal -->
    <EditIPForm
      v-if="showEditModal"
      :ip="selectedIP"
      @close="showEditModal = false"
      @updated="handleUpdated"
    />

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
      <div class="modal modal--small">
        <div class="modal-header">
          <h3 class="modal-title">Confirm Delete</h3>
          <button class="modal-close" @click="showDeleteModal = false">&times;</button>
        </div>
        <div class="modal-body">
          <p class="confirm-text">
            Are you sure you want to delete the IP address
            <strong>{{ ipToDelete?.ip_address }}</strong>?
          </p>
          <p class="confirm-hint">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn--secondary" @click="showDeleteModal = false" :disabled="ipStore.loading">
            Cancel
          </button>
          <button class="btn btn--danger" @click="handleDelete" :disabled="ipStore.loading">
            <span v-if="ipStore.loading">Deleting...</span>
            <span v-else>Delete</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useIPStore } from '@/stores/ip'
import AddIPForm from '@/components/forms/AddIPForm.vue'
import EditIPForm from '@/components/forms/EditIPForm.vue'
import type { IPAddress } from '@/types'

/**
 * Stores
 */
const authStore = useAuthStore()
const ipStore = useIPStore()

/**
 * State
 */
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const selectedIP = ref<IPAddress | null>(null)
const ipToDelete = ref<IPAddress | null>(null)

/**
 * Check if user is the owner of the IP
 */
function isCurrentUser(userId: string): boolean {
  return authStore.user?.id === userId
}

/**
 * Check if current user can edit the IP
 */
function canEdit(ip: IPAddress): boolean {
  return isCurrentUser(ip.user_id) || authStore.isSuperAdmin
}

/**
 * Format date for display
 */
function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

/**
 * Open edit modal
 */
function openEditModal(ip: IPAddress): void {
  selectedIP.value = ip
  showEditModal.value = true
}

/**
 * Confirm delete action
 */
function confirmDelete(ip: IPAddress): void {
  ipToDelete.value = ip
  showDeleteModal.value = true
}

/**
 * Handle IP created
 */
function handleCreated(): void {
  // Refresh is handled by store
}

/**
 * Handle IP updated
 */
function handleUpdated(): void {
  selectedIP.value = null
}

/**
 * Handle delete confirmation
 */
async function handleDelete(): Promise<void> {
  if (!ipToDelete.value) return

  try {
    await ipStore.deleteIP(ipToDelete.value.id)
    showDeleteModal.value = false
    ipToDelete.value = null
  } catch {
    // Error is handled by store
  }
}

/**
 * Fetch IPs on mount
 */
onMounted(() => {
  ipStore.fetchIPs()
})
</script>

<style scoped>
.ip-list-view {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.view-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
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
  transition: background-color 0.15s, opacity 0.15s;
}

.btn-icon {
  font-size: 1.125rem;
  line-height: 1;
}

.btn--primary {
  background-color: #667eea;
  color: white;
}

.btn--primary:hover:not(:disabled) {
  background-color: #5a67d8;
}

.btn--secondary {
  background-color: #e2e8f0;
  color: #4a5568;
}

.btn--secondary:hover:not(:disabled) {
  background-color: #cbd5e0;
}

.btn--danger {
  background-color: #f56565;
  color: white;
}

.btn--danger:hover:not(:disabled) {
  background-color: #e53e3e;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  background: white;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a202c;
}

.stat-label {
  font-size: 0.875rem;
  color: #718096;
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

/* Table */
.table-container {
  background: white;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.data-table th,
.data-table td {
  padding: 0.875rem 1rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.data-table th {
  background-color: #f7fafc;
  font-weight: 600;
  color: #4a5568;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
}

.table-row:hover {
  background-color: #f7fafc;
}

.actions-column {
  width: 1%;
  white-space: nowrap;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.ip-link {
  color: #667eea;
  text-decoration: none;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.ip-link:hover {
  text-decoration: underline;
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

.current-user {
  color: #667eea;
  font-weight: 500;
}

.action-btn {
  padding: 0.375rem;
  background: none;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  line-height: 1;
  transition: background-color 0.15s;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.action-btn:hover {
  background-color: #e2e8f0;
}

.action-btn--danger:hover {
  background-color: #fed7d7;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal {
  background: white;
  border-radius: 8px;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal--small {
  max-width: 400px;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #718096;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
}

.modal-close:hover {
  background-color: #f7fafc;
  color: #1a202c;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid #e2e8f0;
  background-color: #f7fafc;
}

.confirm-text {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
}

.confirm-hint {
  margin: 0;
  font-size: 0.875rem;
  color: #718096;
}

/* Responsive */
@media (max-width: 768px) {
  .ip-list-view {
    padding: 1rem;
  }

  .view-header {
    flex-direction: column;
    gap: 1rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.625rem;
  }

  .actions-cell {
    flex-wrap: wrap;
  }
}
</style>
