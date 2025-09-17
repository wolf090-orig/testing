<template>
  <div class="not-found-view">
    <div class="container">
      <h1>404</h1>
      <p>Страница не найдена</p>
      <router-link to="/home" class="btn btn-primary">На главную</router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
// 404 страница
</script>

<style scoped>
.not-found-view {
  padding: var(--spacing-lg);
  text-align: center;
}

.container {
  max-width: 400px;
  margin: 0 auto;
}

h1 {
  font-size: 48px;
  color: var(--primary-color);
}
</style>
