<template>
  <div class="lottery-details-view">
    <div class="container">
      <h1>Детали лотереи {{ $route.params.id }}</h1>
      <p>Страница в разработке</p>
      <router-link to="/home" class="btn btn-primary">Назад</router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
// Детали лотереи
</script>

<style scoped>
.lottery-details-view {
  padding: var(--spacing-lg);
  text-align: center;
}
.container {
  max-width: 600px;
  margin: 0 auto;
}
</style>
