<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useUserStore } from '@/stores/user'
import { useLotteryStore } from '@/stores/lottery'
import config from '@/config'
import DebugPanel from '@/components/DebugPanel.vue'

// Stores
const userStore = useUserStore()
const lotteryStore = useLotteryStore()

// Computed
const isProductionBuild = computed(() => import.meta.env.PROD)

// Инициализация приложения
onMounted(async () => {
  console.log('🎯 Инициализация Niyat приложения...')
  
  try {
    // Инициализируем пользователя из Telegram
    await userStore.initUserFromTelegram()
    
    // Инициализируем lottery store
    await lotteryStore.initializeStore()
    
    console.log('✅ Niyat приложение полностью инициализировано')
  } catch (error) {
    console.error('❌ Ошибка при инициализации приложения:', error)
  }
})
</script>

<template>
  <div id="app" class="telegram-app">
    <!-- Основное содержимое приложения -->
    <main class="app-content">
      <router-view v-slot="{ Component }">
        <transition name="page-transition" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- Панель отладки в режиме разработки -->
    <DebugPanel v-if="config.debug && !isProductionBuild" />
  </div>
</template>

<style>
/* Niyat App - Современная цветовая схема для лотерейного приложения */
:root {
  /* Основная палитра Niyat */
  --primary-color: #6C5CE7;
  --primary-dark: #5A4FCF;
  --primary-light: #A29BFE;
  --secondary-color: #00CEC9;
  --secondary-dark: #00B3AE;
  --accent-color: #FDCB6E;
  --accent-dark: #F39C12;
  
  /* Статусные цвета */
  --success-color: #00B894;
  --error-color: #E17055;
  --warning-color: #FDCB6E;
  --info-color: #74B9FF;
  
  /* Нейтральные цвета */
  --bg-primary: #F8F9FA;
  --bg-secondary: #FFFFFF;
  --bg-card: #FFFFFF;
  --text-primary: #2D3436;
  --text-secondary: #636E72;
  --text-muted: #B2BEC3;
  --border-color: #DDD6FE;
  --shadow-light: 0 2px 8px rgba(108, 92, 231, 0.1);
  --shadow-medium: 0 4px 20px rgba(108, 92, 231, 0.15);
  --shadow-heavy: 0 8px 32px rgba(108, 92, 231, 0.2);
  
  /* Градиенты */
  --gradient-primary: linear-gradient(135deg, #6C5CE7 0%, #A29BFE 100%);
  --gradient-secondary: linear-gradient(135deg, #00CEC9 0%, #74B9FF 100%);
  --gradient-success: linear-gradient(135deg, #00B894 0%, #55EFC4 100%);
  --gradient-card: linear-gradient(135deg, #FFFFFF 0%, #F8F9FA 100%);
  
  /* Размеры */
  --border-radius: 16px;
  --border-radius-lg: 24px;
  --border-radius-sm: 12px;
  --spacing-xs: 4px;
  --spacing-sm: 8px;
  --spacing-md: 16px;
  --spacing-lg: 24px;
  --spacing-xl: 32px;
  --spacing-2xl: 48px;
  
  /* Шрифты */
  --font-family-primary: 'Unbounded', 'SF Pro Display', -apple-system, system-ui, sans-serif;
  --font-family-secondary: 'Montserrat', 'SF Pro Text', -apple-system, system-ui, sans-serif;
}

/* Темная тема для Telegram */
[data-theme="dark"] {
  --bg-primary: #1A1A1A;
  --bg-secondary: #2D2D2D;
  --bg-card: #363636;
  --text-primary: #FFFFFF;
  --text-secondary: #B2BEC3;
  --text-muted: #636E72;
  --border-color: #4A4A4A;
}

/* Глобальные стили */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html,
body {
  height: 100%;
  font-family: var(--font-family-secondary);
  font-size: 14px;
  line-height: 1.6;
  color: var(--text-primary);
  background: var(--bg-primary);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  overflow-x: hidden;
}

#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--bg-primary);
}

.app-content {
  flex: 1;
  display: flex;
  flex-direction: column;
}

/* Современная типографика */
h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-family-primary);
  font-weight: 700;
  line-height: 1.2;
  color: var(--text-primary);
  margin-bottom: var(--spacing-md);
}

h1 {
  font-size: 28px;
  background: var(--gradient-primary);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

h2 {
  font-size: 24px;
}

h3 {
  font-size: 20px;
}

p {
  margin-bottom: var(--spacing-md);
  color: var(--text-secondary);
}

/* Анимации переходов */
.page-transition-enter-active,
.page-transition-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-transition-enter-from {
  opacity: 0;
  transform: translateX(30px) scale(0.95);
}

.page-transition-leave-to {
  opacity: 0;
  transform: translateX(-30px) scale(0.95);
}

/* Современные кнопки */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-md) var(--spacing-lg);
  border: none;
  border-radius: var(--border-radius);
  font-family: var(--font-family-primary);
  font-size: 16px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  min-height: 48px;
  position: relative;
  overflow: hidden;
}

.btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.btn:hover::before {
  left: 100%;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.btn-primary {
  background: var(--gradient-primary);
  color: white;
  box-shadow: var(--shadow-medium);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: var(--shadow-heavy);
}

.btn-secondary {
  background: var(--gradient-secondary);
  color: white;
  box-shadow: var(--shadow-light);
}

.btn-secondary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: var(--shadow-medium);
}

.btn-outline {
  background: transparent;
  color: var(--primary-color);
  border: 2px solid var(--primary-color);
}

.btn-outline:hover:not(:disabled) {
  background: var(--primary-color);
  color: white;
  transform: translateY(-1px);
}

/* Современные карточки */
.card {
  background: var(--bg-card);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-light);
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-medium);
}

.card-padding {
  padding: var(--spacing-lg);
}

.card-header {
  padding: var(--spacing-lg);
  border-bottom: 1px solid var(--border-color);
  background: var(--gradient-card);
}

.card-body {
  padding: var(--spacing-lg);
}

.card-footer {
  padding: var(--spacing-lg);
  border-top: 1px solid var(--border-color);
  background: var(--bg-primary);
}

/* Формы */
.form-group {
  margin-bottom: var(--spacing-lg);
}

.form-label {
  display: block;
  margin-bottom: var(--spacing-sm);
  font-weight: 600;
  color: var(--text-primary);
  font-size: 14px;
}

.form-input,
.form-select {
  width: 100%;
  padding: var(--spacing-md);
  border: 2px solid var(--border-color);
  border-radius: var(--border-radius);
  font-size: 16px;
  font-family: var(--font-family-secondary);
  background: var(--bg-secondary);
  color: var(--text-primary);
  transition: all 0.3s ease;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
  transform: translateY(-1px);
}

.form-select {
  appearance: none;
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236C5CE7' stroke-width='2'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 18px;
  padding-right: 48px;
}

/* Загрузчик */
.loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-2xl);
  color: var(--text-secondary);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--border-color);
  border-top: 4px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: var(--spacing-md);
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Утилитарные классы */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 var(--spacing-md);
}

.container-sm {
  max-width: 600px;
  margin: 0 auto;
  padding: 0 var(--spacing-md);
}

.text-center { text-align: center; }
.text-left { text-align: left; }
.text-right { text-align: right; }

.text-primary { color: var(--text-primary); }
.text-secondary { color: var(--text-secondary); }
.text-muted { color: var(--text-muted); }
.text-success { color: var(--success-color); }
.text-error { color: var(--error-color); }
.text-warning { color: var(--warning-color); }

.bg-primary { background: var(--gradient-primary); }
.bg-secondary { background: var(--gradient-secondary); }
.bg-success { background: var(--gradient-success); }

.flex { display: flex; }
.flex-column { flex-direction: column; }
.align-center { align-items: center; }
.justify-center { justify-content: center; }
.justify-between { justify-content: space-between; }

.gap-sm { gap: var(--spacing-sm); }
.gap-md { gap: var(--spacing-md); }
.gap-lg { gap: var(--spacing-lg); }

.p-sm { padding: var(--spacing-sm); }
.p-md { padding: var(--spacing-md); }
.p-lg { padding: var(--spacing-lg); }
.p-xl { padding: var(--spacing-xl); }

.m-sm { margin: var(--spacing-sm); }
.m-md { margin: var(--spacing-md); }
.m-lg { margin: var(--spacing-lg); }
.m-xl { margin: var(--spacing-xl); }

.rounded { border-radius: var(--border-radius); }
.rounded-lg { border-radius: var(--border-radius-lg); }
.shadow { box-shadow: var(--shadow-light); }
.shadow-md { box-shadow: var(--shadow-medium); }

/* Адаптивность для мобильных */
@media (max-width: 768px) {
  html, body {
    font-size: 13px;
  }
  
  h1 { font-size: 24px; }
  h2 { font-size: 20px; }
  h3 { font-size: 18px; }
  
  .container {
    padding: 0 var(--spacing-sm);
  }
  
  .btn {
    padding: var(--spacing-sm) var(--spacing-md);
    font-size: 14px;
    min-height: 44px;
  }
  
  .card-padding,
  .card-header,
  .card-body,
  .card-footer {
    padding: var(--spacing-md);
  }
}

/* Скрытие скроллбара */
::-webkit-scrollbar {
  width: 4px;
}

::-webkit-scrollbar-track {
  background: var(--bg-primary);
}

::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 2px;
}

::-webkit-scrollbar-thumb:hover {
  background: var(--primary-color);
}

/* Специальные эффекты для Telegram Mini App */
.telegram-app {
  -webkit-user-select: none;
  -webkit-touch-callout: none;
  -webkit-tap-highlight-color: transparent;
}
</style>
