/**
 * Audit Dashboard View
 *
 * Displays system audit logs for super administrators.
 * Includes filtering by event type and detailed log information.
 *
 * @package Views/Audit
 */

<template>
  <div class="audit-dashboard-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-content">
        <h1 class="page-title">Audit Dashboard</h1>
        <p class="page-subtitle">View system audit logs and user activity</p>
      </div>
      <span v-if="authStore.isSuperAdmin" class="admin-badge">Super Admin</span>
    </div>

    <!-- Access Denied -->
    <div v-if="!authStore.isSuperAdmin" class="access-denied">
      <div class="access-icon">🚫</div>
      <h2>Access Denied</h2>
      <p>This page is only accessible to super administrators.</p>
      <router-link to="/dashboard" class="btn btn--primary">Back to Dashboard</router-link>
    </div>

    <!-- Audit Logs -->
    <template v-else>
      <!-- Filters -->
      <div class="filters">
        <div class="filter-group">
          <label class="filter-label">Filter by Event Type</label>
          <select v-model="selectedFilter" class="filter-select" @change="applyFilter">
            <option value="">All Events</option>
            <option v-for="type in auditStore.eventTypes" :key="type" :value="type">
              {{ formatEventType(type) }}
            </option>
          </select>
        </div>
        <button v-if="selectedFilter" class="btn btn--secondary" @click="clearFilter">
          Clear Filter
        </button>
        <button class="btn btn--primary" @click="refreshLogs" :disabled="auditStore.loading">
          {{ auditStore.loading ? 'Refreshing...' : 'Refresh' }}
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="auditStore.loading && !auditStore.logs.length" class="loading-state">
        <div class="spinner"></div>
        <p>Loading audit logs...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="!auditStore.filteredLogs.length" class="empty-state">
        <div class="empty-icon">📜</div>
        <h3>No Audit Logs</h3>
        <p>There are no audit logs to display.</p>
      </div>

      <!-- Audit Logs Table -->
      <div v-else class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>User</th>
              <th>Event</th>
              <th>Entity</th>
              <th>Details</th>
              <th>Session</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in auditStore.filteredLogs" :key="log.id" class="table-row">
              <td class="timestamp-cell">
                {{ formatDate(log.created_at) }}
              </td>
              <td>
                <span class="user-id" :title="log.user_id">{{ truncate(log.user_id, 8) }}</span>
              </td>
              <td>
                <span class="event-badge" :class="getEventClass(log.event_type)">
                  {{ formatEventType(log.event_type) }}
                </span>
              </td>
              <td>
                <span class="entity-type">{{ log.entity_type }}</span>
                <span class="entity-id" :title="log.entity_id">
                  {{ truncate(log.entity_id, 8) }}
                </span>
              </td>
              <td>
                <button
                  class="details-btn"
                  @click="showDetails(log)"
                  title="View Details"
                >
                  View
                </button>
              </td>
              <td>
                <span class="session-id" :title="log.session_id">
                  {{ truncate(log.session_id, 8) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Stats Summary -->
      <div v-if="auditStore.logs.length" class="stats-summary">
        <p>Showing {{ auditStore.filteredLogs.length }} of {{ auditStore.logs.length }} audit log entries</p>
      </div>
    </template>

    <!-- Details Modal -->
    <div v-if="selectedLog" class="modal-overlay" @click.self="selectedLog = null">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">Audit Log Details</h3>
          <button class="modal-close" @click="selectedLog = null">&times;</button>
        </div>
        <div class="modal-body">
          <div class="detail-grid">
            <div class="detail-item">
              <span class="detail-label">Timestamp</span>
              <span class="detail-value">{{ formatFullDate(selectedLog.created_at) }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">User ID</span>
              <span class="detail-value">{{ selectedLog.user_id }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Event Type</span>
              <span class="detail-value">{{ selectedLog.event_type }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Entity Type</span>
              <span class="detail-value">{{ selectedLog.entity_type }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Entity ID</span>
              <span class="detail-value">{{ selectedLog.entity_id }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Session ID</span>
              <span class="detail-value">{{ selectedLog.session_id }}</span>
            </div>
          </div>
          <div class="metadata-section">
            <span class="detail-label">Metadata</span>
            <pre class="metadata-json">{{ JSON.stringify(selectedLog.metadata, null, 2) }}</pre>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn--secondary" @click="selectedLog = null">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useAuditStore } from '@/stores/audit'
import type { AuditLog } from '@/types'

/**
 * Stores
 */
const authStore = useAuthStore()
const auditStore = useAuditStore()

/**
 * State
 */
const selectedFilter = ref('')
const selectedLog = ref<AuditLog | null>(null)

/**
 * Format event type for display
 */
function formatEventType(eventType: string): string {
  return eventType
    .split('.')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

/**
 * Get event class for badge styling
 */
function getEventClass(eventType: string): string {
  if (eventType.includes('login')) return 'event--login'
  if (eventType.includes('logout')) return 'event--logout'
  if (eventType.includes('created')) return 'event--create'
  if (eventType.includes('updated')) return 'event--update'
  if (eventType.includes('deleted')) return 'event--delete'
  return 'event--other'
}

/**
 * Format date for display
 */
function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

/**
 * Format full date for details
 */
function formatFullDate(dateString: string): string {
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

/**
 * Truncate string with ellipsis
 */
function truncate(str: string, length: number): string {
  if (str.length <= length) return str
  return str.substring(0, length) + '...'
}

/**
 * Apply filter
 */
function applyFilter(): void {
  auditStore.setEventFilter(selectedFilter.value)
}

/**
 * Clear filter
 */
function clearFilter(): void {
  selectedFilter.value = ''
  auditStore.clearFilter()
}

/**
 * Show log details
 */
function showDetails(log: AuditLog): void {
  selectedLog.value = log
}

/**
 * Refresh logs
 */
async function refreshLogs(): Promise<void> {
  await auditStore.fetchAllLogs()
}

/**
 * Fetch logs on mount
 */
onMounted(() => {
  if (authStore.isSuperAdmin) {
    auditStore.fetchAllLogs()
  }
})
</script>

<style scoped>
.audit-dashboard-view {
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

.admin-badge {
  padding: 0.375rem 0.75rem;
  background-color: #fed7e2;
  color: #97266d;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
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

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Access Denied */
.access-denied {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.access-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.access-denied h2 {
  margin: 0 0 0.5rem 0;
  color: #1a202c;
}

.access-denied p {
  color: #718096;
  margin: 0 0 1.5rem 0;
}

/* Filters */
.filters {
  display: flex;
  align-items: flex-end;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.filter-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: #4a5568;
  text-transform: uppercase;
}

.filter-select {
  padding: 0.625rem 2rem 0.625rem 0.875rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.875rem;
  background-color: white;
  min-width: 200px;
}

.filter-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
  margin: 0;
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

.timestamp-cell {
  white-space: nowrap;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.8125rem;
}

.user-id,
.entity-id,
.session-id {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.8125rem;
  color: #718096;
}

.entity-type {
  display: block;
  font-size: 0.75rem;
  color: #4a5568;
  font-weight: 500;
}

.event-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.event--login {
  background-color: #c6f6d5;
  color: #22543d;
}

.event--logout {
  background-color: #feebc8;
  color: #744210;
}

.event--create {
  background-color: #bee3f8;
  color: #2a4365;
}

.event--update {
  background-color: #e9d8fd;
  color: #553c9a;
}

.event--delete {
  background-color: #fed7d7;
  color: #742a2a;
}

.event--other {
  background-color: #e2e8f0;
  color: #4a5568;
}

.details-btn {
  padding: 0.25rem 0.5rem;
  background: none;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  font-size: 0.75rem;
  color: #667eea;
  cursor: pointer;
  transition: background-color 0.15s;
}

.details-btn:hover {
  background-color: #f7fafc;
}

/* Stats Summary */
.stats-summary {
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  background-color: #f7fafc;
  border-radius: 6px;
  font-size: 0.875rem;
  color: #718096;
}

.stats-summary p {
  margin: 0;
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
  max-width: 600px;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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
  overflow-y: auto;
  max-height: 60vh;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid #e2e8f0;
  background-color: #f7fafc;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
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
}

.detail-value {
  font-size: 0.875rem;
  color: #2d3748;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  word-break: break-all;
}

.metadata-section {
  border-top: 1px solid #e2e8f0;
  padding-top: 1rem;
}

.metadata-json {
  margin: 0.5rem 0 0 0;
  padding: 0.75rem;
  background-color: #f7fafc;
  border-radius: 6px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.8125rem;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-all;
}

/* Responsive */
@media (max-width: 768px) {
  .audit-dashboard-view {
    padding: 1rem;
  }

  .view-header {
    flex-direction: column;
    gap: 0.75rem;
  }

  .filters {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-select {
    width: 100%;
  }

  .data-table th,
  .data-table td {
    padding: 0.625rem;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
