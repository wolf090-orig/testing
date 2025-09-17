<template>
  <div class="about-view">
    <div class="container">
      <h1>О приложении Niyat</h1>
      <p>Лучшая лотерейная платформа в Узбекистане</p>
      <p>Версия: 1.0.0</p>
      <router-link to="/home" class="btn btn-primary">На главную</router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
// Пустая страница "О приложении"
</script>

<style scoped>
.about-view {
  padding: var(--spacing-lg);
  text-align: center;
}

.container {
  max-width: 400px;
  margin: 0 auto;
}
</style>
