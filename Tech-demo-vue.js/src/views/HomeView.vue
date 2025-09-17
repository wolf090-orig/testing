<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useLotteryStore } from '@/stores/lottery'

const router = useRouter()
const userStore = useUserStore()
const lotteryStore = useLotteryStore()

// Reactive data
const activeTab = ref<'active' | 'history'>('active')

// Computed
const filteredLotteries = computed(() => {
  if (activeTab.value === 'active') {
    return lotteryStore.activeLotteries
  } else {
    return lotteryStore.historyLotteries
  }
})

// Methods
function setActiveTab(tab: 'active' | 'history') {
  activeTab.value = tab
  loadLotteriesForTab(tab)
}

async function loadLotteriesForTab(tab: 'active' | 'history') {
  try {
    await lotteryStore.fetchLotteries(tab)
  } catch (error) {
    console.error(`❌ Ошибка загрузки ${tab} лотерей:`, error)
  }
}

function reloadLotteries() {
  loadLotteriesForTab(activeTab.value)
}

function openLottery(lotteryId: number) {
  router.push(`/lottery/${lotteryId}`)
}

function goToCart() {
  router.push('/cart')
}

function handleImageError(event: Event) {
  const target = event.target as HTMLImageElement
  target.style.display = 'none'
  const placeholder = target.parentElement?.querySelector('.lottery-image-placeholder')
  if (placeholder) {
    ;(placeholder as HTMLElement).style.display = 'flex'
  }
}

function formatNumber(num: number): string {
  return new Intl.NumberFormat('ru-RU').format(num)
}

function formatTimeRemaining(endDate: string): string {
  const now = new Date()
  const end = new Date(endDate)
  const diff = end.getTime() - now.getTime()
  
  if (diff <= 0) {
    return 'Завершено'
  }
  
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
  
  if (days > 0) {
    return `${days}д ${hours}ч`
  } else if (hours > 0) {
    return `${hours}ч ${minutes}м`
  } else {
    return `${minutes}м`
  }
}

// Lifecycle
onMounted(async () => {
  console.log('🏠 HomeView: инициализация')
  
  // Загружаем активные лотереи если еще не загружены
  if (lotteryStore.activeLotteries.length === 0) {
    await loadLotteriesForTab('active')
  }
})
</script>

<template>
  <div class="home-view">
    <!-- Заголовок -->
    <header class="home-header">
      <div class="container">
        <h1>🎰 Niyat Lotto</h1>
        <div v-if="userStore.isAuthenticated" class="user-info">
          <span>Привет, {{ userStore.fullName }}!</span>
          <div class="user-stats">
            <span>💰 {{ formatNumber(userStore.coins) }} сум</span>
            <span v-if="lotteryStore.activeTicketsCount > 0">
              🎫 {{ lotteryStore.activeTicketsCount }} билетов
            </span>
          </div>
        </div>
      </div>
    </header>

    <!-- Содержимое -->
    <main class="home-content">
      <div class="container">
        <!-- Фильтры -->
        <div class="filters">
          <button 
            @click="setActiveTab('active')"
            :class="['filter-btn', { active: activeTab === 'active' }]"
          >
            Активные лотереи
          </button>
          <button 
            @click="setActiveTab('history')"
            :class="['filter-btn', { active: activeTab === 'history' }]"
          >
            История
          </button>
        </div>

        <!-- Загрузка -->
        <div v-if="lotteryStore.loading" class="loader">
          <div class="spinner"></div>
          <p>Загружаем лотереи...</p>
        </div>

        <!-- Ошибка -->
        <div v-else-if="lotteryStore.error" class="error-state">
          <p>{{ lotteryStore.error }}</p>
          <button @click="reloadLotteries" class="btn btn-primary">
            Попробовать снова
          </button>
        </div>

        <!-- Список лотерей -->
        <div v-else-if="filteredLotteries.length > 0" class="lotteries-grid">
          <div 
            v-for="lottery in filteredLotteries" 
            :key="lottery.id"
            class="lottery-card card"
            @click="openLottery(lottery.id)"
          >
            <div class="lottery-image">
              <img 
                v-if="lottery.image_url" 
                :src="lottery.image_url" 
                :alt="lottery.name"
                @error="handleImageError"
              />
              <div v-else class="lottery-image-placeholder">
                🎲
              </div>
            </div>
            
            <div class="lottery-info">
              <h3 class="lottery-name">{{ lottery.name }}</h3>
              <p class="lottery-description">{{ lottery.description }}</p>
              
              <div class="lottery-details">
                <div class="lottery-type">{{ lottery.type_name }}</div>
                <div class="lottery-price">{{ formatNumber(lottery.price) }} сум</div>
              </div>
              
              <div class="lottery-stats">
                <div class="stat">
                  <span class="stat-label">Главный приз:</span>
                  <span class="stat-value">{{ formatNumber(lottery.drawn_amount) }} сум</span>
                </div>
                <div class="stat">
                  <span class="stat-label">Участников:</span>
                  <span class="stat-value">{{ lottery.participants }}</span>
                </div>
              </div>
              
              <!-- Таймер для активных лотерей -->
              <div v-if="lottery.status === 'active' && lottery.end_date" class="lottery-timer">
                <span class="timer-label">До окончания:</span>
                <span class="timer-value">{{ formatTimeRemaining(lottery.end_date) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Пустое состояние -->
        <div v-else class="empty-state">
          <div class="empty-icon">🎰</div>
          <h3>{{ activeTab === 'active' ? 'Нет активных лотерей' : 'История пуста' }}</h3>
          <p>
            {{ activeTab === 'active' 
              ? 'В данный момент нет доступных лотерей.' 
              : 'У вас пока нет истории участия в лотереях.' 
            }}
          </p>
        </div>
      </div>
    </main>

    <!-- Плавающая кнопка корзины -->
    <div v-if="lotteryStore.hasCartItems" class="floating-cart" @click="goToCart">
      <div class="cart-icon">🛒</div>
      <div class="cart-badge">{{ lotteryStore.cartItemsCount }}</div>
      <div class="cart-total">{{ formatNumber(lotteryStore.cartTotalPrice) }} сум</div>
    </div>
  </div>
</template>

<style scoped>
.home-view {
  min-height: 100vh;
  background-color: var(--secondary-color);
}

.container {
  max-width: 800px;
  margin: 0 auto;
  padding: 0 var(--spacing-md);
}

.home-header {
  background: linear-gradient(135deg, var(--primary-color) 0%, #1976d2 100%);
  color: white;
  padding: var(--spacing-lg) 0;
}

.home-header h1 {
  margin: 0 0 var(--spacing-md) 0;
  font-size: 24px;
}

.user-info {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.user-stats {
  display: flex;
  gap: var(--spacing-md);
  font-size: 14px;
  opacity: 0.9;
}

.home-content {
  padding: var(--spacing-lg) 0;
}

.filters {
  display: flex;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-lg);
}

.filter-btn {
  padding: var(--spacing-sm) var(--spacing-md);
  border: 2px solid var(--border-color);
  background: white;
  color: var(--text-primary);
  border-radius: var(--border-radius);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-btn.active {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.lotteries-grid {
  display: grid;
  gap: var(--spacing-lg);
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
}

.lottery-card {
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.lottery-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.lottery-image {
  height: 120px;
  position: relative;
  overflow: hidden;
  border-radius: var(--border-radius) var(--border-radius) 0 0;
  background: var(--secondary-color);
}

.lottery-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.lottery-image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  background: linear-gradient(45deg, var(--primary-color), #1976d2);
  color: white;
}

.lottery-info {
  padding: var(--spacing-lg);
}

.lottery-name {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: var(--spacing-sm);
  color: var(--text-primary);
}

.lottery-description {
  color: var(--text-secondary);
  font-size: 14px;
  margin-bottom: var(--spacing-md);
  line-height: 1.4;
}

.lottery-details {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-md);
}

.lottery-type {
  background: var(--secondary-color);
  color: var(--primary-color);
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: 16px;
  font-size: 12px;
  font-weight: 500;
}

.lottery-price {
  font-weight: 600;
  color: var(--primary-color);
}

.lottery-stats {
  display: flex;
  justify-content: space-between;
  margin-bottom: var(--spacing-md);
}

.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.stat-label {
  font-size: 12px;
  color: var(--text-secondary);
  margin-bottom: var(--spacing-xs);
}

.stat-value {
  font-weight: 600;
  color: var(--text-primary);
}

.lottery-timer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-sm);
  background: #fff3e0;
  border-radius: var(--border-radius);
  border: 1px solid #ffcc02;
}

.timer-label {
  font-size: 12px;
  color: var(--text-secondary);
}

.timer-value {
  font-weight: 600;
  color: #f57c00;
}

.empty-state {
  text-align: center;
  padding: var(--spacing-xl);
  color: var(--text-secondary);
}

.empty-icon {
  font-size: 48px;
  margin-bottom: var(--spacing-md);
}

.error-state {
  text-align: center;
  padding: var(--spacing-xl);
}

.floating-cart {
  position: fixed;
  bottom: 80px;
  right: var(--spacing-md);
  background: var(--primary-color);
  color: white;
  border-radius: 50px;
  padding: var(--spacing-md);
  cursor: pointer;
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  z-index: 1000;
  transition: transform 0.2s ease;
}

.floating-cart:hover {
  transform: scale(1.05);
}

.cart-icon {
  font-size: 20px;
}

.cart-badge {
  background: white;
  color: var(--primary-color);
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  margin-left: -4px;
}

.cart-total {
  font-size: 12px;
  font-weight: 600;
}

@media (max-width: 480px) {
  .lotteries-grid {
    grid-template-columns: 1fr;
  }
  
  .user-stats {
    flex-direction: column;
  }
  
  .lottery-stats {
    flex-direction: column;
    gap: var(--spacing-sm);
  }
  
  .floating-cart {
    bottom: 20px;
    right: var(--spacing-sm);
    padding: var(--spacing-sm);
  }
}
</style>
