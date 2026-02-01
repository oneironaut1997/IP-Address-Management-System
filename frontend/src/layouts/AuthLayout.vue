<template>
  <div class="auth-layout">
    <div class="auth-container">
      <div class="auth-card">
        <header class="auth-header">
          <h1 class="auth-title">IP Management System</h1>
          <p class="auth-subtitle">Secure IP Address Management</p>
        </header>

        <main class="auth-content">
          <router-view />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Auth Layout
 *
 * Layout component for authentication pages (login, register).
 * Provides a centered card layout with branding.
 *
 * @package Layouts
 */
</script>

<style scoped>
.auth-layout {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem;
}

.auth-container {
  width: 100%;
  max-width: 420px;
}

.auth-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  padding: 2rem;
}

.auth-header {
  text-align: center;
  margin-bottom: 2rem;
}

.auth-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.5rem 0;
}

.auth-subtitle {
  font-size: 0.875rem;
  color: #718096;
  margin: 0;
}

.auth-content {
  width: 100%;
}

@media (max-width: 480px) {
  .auth-card {
    padding: 1.5rem;
  }

  .auth-title {
    font-size: 1.5rem;
  }
}
</style>
