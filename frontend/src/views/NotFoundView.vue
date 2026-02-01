<template>
  <div class="not-found-view">
    <h1>404</h1>
    <p>Page not found</p>
    <router-link to="/">Go Home</router-link>
  </div>
</template>

<script setup lang="ts">
/**
 * Not Found View
 *
 * Displayed when a route is not found.
 *
 * @package Views
 */
</script>

<style scoped>
.not-found-view {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  text-align: center;
}

h1 {
  font-size: 6rem;
  margin: 0;
  color: #667eea;
}

p {
  font-size: 1.5rem;
  color: #4a5568;
  margin: 1rem 0;
}

a {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
}

a:hover {
  text-decoration: underline;
}
</style>
