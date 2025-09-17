<template>
  <div class="ticket-details-view">
    <div class="container">
      <h1>Детали билета {{ $route.params.id }}</h1>
      <p>Страница в разработке</p>
      <router-link to="/tickets" class="btn btn-primary">К билетам</router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
// Детали билета
</script>

<style scoped>
.ticket-details-view {
  padding: var(--spacing-lg);
  text-align: center;
}
.container {
  max-width: 600px;
  margin: 0 auto;
}
</style>
