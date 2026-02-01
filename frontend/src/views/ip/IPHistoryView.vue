/**
 * IP History View
 *
 * Displays the change history for a specific IP address.
 * Shows all modifications with before/after values.
 *
 * @package Views/IP
 */

<template>
  <div class="ip-history-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-content">
        <router-link :to="`/ip/${route.params.id}`" class="back-link">
          ← Back to IP Details
        </router-link>
        <h1 class="page-title">IP Address History</h1>
        <p v-if="ipStore.currentIP" class="page-subtitle">
          Change history for {{ ipStore.currentIP.ip_address }}
        </p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="ipStore.loading && !ipStore.history.length" class="loading-state">
      <div class="spinner"></div>
      <p>Loading history...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="!ipStore.history.length" class="empty-state">
      <div class="empty-icon">📜</div>
      <h3>No History Available</h3>
      <p>This IP address has no recorded changes yet.</p>
      <router-link :to="`/ip/${route.params.id}`" class="btn btn--primary">
        Back to IP Details
      </router-link>
    </div>

    <!-- History Timeline -->
    <div v-else class="history-timeline">
      <div
        v-for="entry in ipStore.history"
        :key="entry.id"
        class="timeline-item"
        :class="`action--${entry.action}`"
      >
        <div class="timeline-marker"></div>
        <div class="timeline-content">
          <div class="timeline-header">
            <span class="action-badge" :class="`action-badge--${entry.action}`">
              {{ formatAction(entry.action) }}
            </span>
            <span class="timeline-date">{{ formatDate(entry.created_at) }}</span>
          </div>

          <div class="timeline-body">
            <p class="modified-by">
              Modified by: <strong>{{ entry.modified_by }}</strong>
            </p>

            <!-- Changes -->
            <div v-if="entry.action === 'updated'" class="changes">
              <div v-if="hasChanges(entry.old_values, entry.new_values, 'label')" class="change-row">
                <span class="change-label">Label:</span>
                <div class="change-values">
                  <span class="value-old">{{ entry.old_values.label || '(empty)' }}</span>
                  <span class="arrow">→</span>
                  <span class="value-new">{{ entry.new_values.label || '(empty)' }}</span>
                </div>
              </div>

              <div v-if="hasChanges(entry.old_values, entry.new_values, 'comment')" class="change-row">
                <span class="change-label">Comment:</span>
                <div class="change-values">
                  <span class="value-old">{{ entry.old_values.comment || '(empty)' }}</span>
                  <span class="arrow">→</span>
                  <span class="value-new">{{ entry.new_values.comment || '(empty)' }}</span>
                </div>
              </div>
            </div>

            <!-- Created Info -->
            <div v-else-if="entry.action === 'created'" class="creation-info">
              <p>Initial creation with:</p>
              <ul class="info-list">
                <li><strong>IP:</strong> {{ entry.new_values.ip_address }}</li>
                <li><strong>Label:</strong> {{ entry.new_values.label }}</li>
                <li v-if="entry.new_values.comment">
                  <strong>Comment:</strong> {{ entry.new_values.comment }}
                </li>
              </ul>
            </div>

            <!-- Deleted Info -->
            <div v-else-if="entry.action === 'deleted'" class="deletion-info">
              <p>This IP address was deleted.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useIPStore } from '@/stores/ip'

/**
 * Route
 */
const route = useRoute()

/**
 * Store
 */
const ipStore = useIPStore()

/**
 * Format action for display
 */
function formatAction(action: string): string {
  const actions: Record<string, string> = {
    created: 'Created',
    updated: 'Updated',
    deleted: 'Deleted',
  }
  return actions[action] || action
}

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
 * Check if a specific field has changes
 */
function hasChanges(
  oldValues: Record<string, unknown>,
  newValues: Record<string, unknown>,
  field: string
): boolean {
  return oldValues[field] !== newValues[field]
}

/**
 * Fetch history on mount
 */
onMounted(() => {
  const id = route.params.id as string
  if (id) {
    ipStore.fetchHistory(id)
    // Also fetch the IP details if not already loaded
    if (!ipStore.currentIP || ipStore.currentIP.id !== id) {
      // Try to find in the list, otherwise we might need to fetch it
      const found = ipStore.ips.find((ip) => ip.id === id)
      if (found) {
        ipStore.setCurrentIP(found)
      }
    }
  }
})
</script>

<style scoped>
.ip-history-view {
  padding: 2rem;
  max-width: 900px;
  margin: 0 auto;
}

.view-header {
  margin-bottom: 2rem;
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
  margin: 0 0 0.25rem 0;
}

.page-subtitle {
  color: #718096;
  margin: 0;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
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

/* Timeline */
.history-timeline {
  position: relative;
  padding-left: 2rem;
}

.history-timeline::before {
  content: '';
  position: absolute;
  left: 7px;
  top: 0;
  bottom: 0;
  width: 2px;
  background-color: #e2e8f0;
}

.timeline-item {
  position: relative;
  margin-bottom: 1.5rem;
}

.timeline-marker {
  position: absolute;
  left: -2rem;
  top: 0.25rem;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background-color: #e2e8f0;
  border: 3px solid white;
  box-shadow: 0 0 0 2px #e2e8f0;
}

.action--created .timeline-marker {
  background-color: #48bb78;
  box-shadow: 0 0 0 2px #48bb78;
}

.action--updated .timeline-marker {
  background-color: #4299e1;
  box-shadow: 0 0 0 2px #4299e1;
}

.action--deleted .timeline-marker {
  background-color: #f56565;
  box-shadow: 0 0 0 2px #f56565;
}

.timeline-content {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.action-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.action-badge--created {
  background-color: #c6f6d5;
  color: #22543d;
}

.action-badge--updated {
  background-color: #bee3f8;
  color: #2a4365;
}

.action-badge--deleted {
  background-color: #fed7d7;
  color: #742a2a;
}

.timeline-date {
  font-size: 0.875rem;
  color: #718096;
}

.timeline-body {
  font-size: 0.875rem;
}

.modified-by {
  margin: 0 0 0.75rem 0;
  color: #4a5568;
}

.changes {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.change-row {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.5rem;
  background-color: #f7fafc;
  border-radius: 4px;
}

.change-label {
  font-weight: 500;
  color: #4a5568;
  font-size: 0.75rem;
  text-transform: uppercase;
}

.change-values {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.value-old {
  color: #e53e3e;
  text-decoration: line-through;
}

.arrow {
  color: #718096;
}

.value-new {
  color: #38a169;
  font-weight: 500;
}

.creation-info,
.deletion-info {
  padding: 0.75rem;
  background-color: #f7fafc;
  border-radius: 4px;
}

.creation-info p,
.deletion-info p {
  margin: 0 0 0.5rem 0;
}

.info-list {
  margin: 0;
  padding-left: 1.25rem;
}

.info-list li {
  margin-bottom: 0.25rem;
}

/* Responsive */
@media (max-width: 640px) {
  .ip-history-view {
    padding: 1rem;
  }

  .history-timeline {
    padding-left: 1.5rem;
  }

  .timeline-marker {
    left: -1.5rem;
    width: 12px;
    height: 12px;
  }

  .timeline-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
}
</style>
