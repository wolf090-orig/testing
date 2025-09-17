<template>
  <div v-if="showDebug" class="debug-panel">
    <div class="debug-header">
      <h4>🐛 Debug Panel</h4>
      <button @click="toggleDebug" class="debug-close">×</button>
    </div>
    <div class="debug-content">
      <div class="debug-section">
        <strong>🎮 Telegram WebApp:</strong>
        <pre>{{ telegramInfo }}</pre>
      </div>
      <div class="debug-section">
        <strong>👤 User Store:</strong>
        <pre>{{ userInfo }}</pre>
      </div>
      <div class="debug-section">
        <strong>🎰 Lottery Store:</strong>
        <pre>{{ lotteryInfo }}</pre>
      </div>
    </div>
  </div>
  <button v-else @click="toggleDebug" class="debug-toggle">🐛</button>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useUserStore } from '@/stores/user'
import { useLotteryStore } from '@/stores/lottery'

const userStore = useUserStore()
const lotteryStore = useLotteryStore()

const showDebug = ref(false)

const telegramInfo = computed(() => ({
  isAvailable: !!window.Telegram?.WebApp,
  initData: window.Telegram?.WebApp?.initData ? 'Available' : 'None',
  user: window.Telegram?.WebApp?.initDataUnsafe?.user,
  platform: window.Telegram?.WebApp?.platform,
  colorScheme: window.Telegram?.WebApp?.colorScheme
}))

const userInfo = computed(() => ({
  id: userStore.id,
  name: userStore.fullName,
  authenticated: userStore.isAuthenticated,
  country: userStore.country,
  language: userStore.language,
  coins: userStore.coins,
  hasSettings: userStore.hasUserSettings
}))

const lotteryInfo = computed(() => ({
  lotteriesCount: lotteryStore.lotteries.length,
  cartItems: lotteryStore.cartItemsCount,
  cartTotal: lotteryStore.cartTotalPrice,
  activeTickets: lotteryStore.activeTicketsCount,
  loading: {
    main: lotteryStore.loading,
    cart: lotteryStore.cartLoading,
    tickets: lotteryStore.ticketsLoading
  }
}))

function toggleDebug() {
  showDebug.value = !showDebug.value
}
</script>

<style scoped>
.debug-toggle {
  position: fixed;
  top: 10px;
  right: 10px;
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  font-size: 16px;
  cursor: pointer;
  z-index: 10000;
}

.debug-panel {
  position: fixed;
  top: 10px;
  right: 10px;
  width: 300px;
  max-height: 80vh;
  background: rgba(0, 0, 0, 0.95);
  color: white;
  border-radius: 8px;
  font-family: monospace;
  font-size: 11px;
  z-index: 10000;
  overflow-y: auto;
}

.debug-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.debug-header h4 {
  margin: 0;
  font-size: 12px;
}

.debug-close {
  background: none;
  border: none;
  color: white;
  font-size: 20px;
  cursor: pointer;
  padding: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.debug-content {
  padding: 8px;
  max-height: 60vh;
  overflow-y: auto;
}

.debug-section {
  margin-bottom: 12px;
}

.debug-section strong {
  display: block;
  margin-bottom: 4px;
  color: #4fc3f7;
}

.debug-section pre {
  background: rgba(255, 255, 255, 0.1);
  padding: 6px;
  border-radius: 4px;
  margin: 0;
  white-space: pre-wrap;
  word-break: break-all;
  font-size: 10px;
  line-height: 1.2;
}

@media (max-width: 480px) {
  .debug-panel {
    width: calc(100vw - 20px);
    top: 10px;
    left: 10px;
    right: 10px;
  }
}
</style> 