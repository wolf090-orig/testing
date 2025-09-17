<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Навигационная панель -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center">
            <router-link to="/" class="text-2xl font-bold text-primary-600">NIYAT</router-link>
          </div>
          <div class="flex items-center space-x-4">
            <router-link to="/" class="text-gray-600 hover:text-gray-900">Главная</router-link>
            <router-link to="/catalog" class="text-gray-600 hover:text-gray-900">Каталог</router-link>
            <router-link to="/profile" class="btn-secondary">Профиль</router-link>
          </div>
        </div>
      </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Хлебные крошки -->
      <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2">
          <li>
            <router-link to="/" class="text-gray-500 hover:text-gray-700">Главная</router-link>
          </li>
          <li>
            <span class="text-gray-400">/</span>
          </li>
          <li>
            <router-link to="/catalog" class="text-gray-500 hover:text-gray-700">Каталог</router-link>
          </li>
          <li v-if="currentItem">
            <span class="text-gray-400">/</span>
          </li>
          <li v-if="currentItem">
            <span class="text-gray-900 font-medium">{{ currentItem.title }}</span>
          </li>
        </ol>
      </nav>

      <!-- Индикатор загрузки -->
      <div v-if="isLoading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>

      <!-- Ошибка -->
      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-600 mb-4">{{ error }}</div>
        <div class="space-x-4">
          <button @click="loadItem" class="btn-primary">Попробовать снова</button>
          <router-link to="/catalog" class="btn-secondary">Вернуться к каталогу</router-link>
        </div>
      </div>

      <!-- Детали элемента -->
      <div v-else-if="currentItem" class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Изображение -->
        <div class="space-y-4">
          <div class="aspect-square bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <img
              :src="currentItem.image"
              :alt="currentItem.title"
              class="w-full h-full object-cover"
            >
          </div>
          
          <!-- Дополнительные изображения (заглушка) -->
          <div class="grid grid-cols-4 gap-2">
            <div 
              v-for="i in 4" 
              :key="i"
              class="aspect-square bg-gray-100 rounded-lg border border-gray-200 overflow-hidden cursor-pointer hover:border-primary-300 transition-colors"
            >
              <img
                :src="currentItem.image"
                :alt="`${currentItem.title} - вид ${i}`"
                class="w-full h-full object-cover opacity-75 hover:opacity-100 transition-opacity"
              >
            </div>
          </div>
        </div>

        <!-- Информация -->
        <div class="space-y-6">
          <!-- Заголовок и рейтинг -->
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ currentItem.title }}</h1>
            <div class="flex items-center space-x-4 mb-4">
              <div class="flex items-center">
                <div class="flex items-center">
                  <span v-for="i in 5" :key="i" class="text-yellow-400">
                    {{ i <= Math.floor(currentItem.rating) ? '★' : '☆' }}
                  </span>
                </div>
                <span class="ml-2 text-sm text-gray-600">
                  {{ currentItem.rating }} ({{ currentItem.reviewsCount }} отзывов)
                </span>
              </div>
              <span 
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :style="{ 
                  backgroundColor: currentItem.category.color + '20', 
                  color: currentItem.category.color 
                }"
              >
                {{ currentItem.category.name }}
              </span>
            </div>
          </div>

          <!-- Цена -->
          <div v-if="currentItem.price" class="text-3xl font-bold text-gray-900">
            {{ formatPrice(currentItem.price) }}
          </div>

          <!-- Описание -->
          <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Описание</h3>
            <p class="text-gray-600 leading-relaxed">{{ currentItem.description }}</p>
          </div>

          <!-- Теги -->
          <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Теги</h3>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="tag in currentItem.tags"
                :key="tag"
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800"
              >
                {{ tag }}
              </span>
            </div>
          </div>

          <!-- Действия -->
          <div class="space-y-4">
            <div class="flex space-x-4">
              <button class="btn-primary flex-1">
                Добавить в избранное
              </button>
              <button class="btn-secondary flex-1">
                Поделиться
              </button>
            </div>
            <button class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-lg transition-colors duration-200">
              Связаться с продавцом
            </button>
          </div>

          <!-- Дополнительная информация -->
          <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Дополнительная информация</h3>
            <dl class="grid grid-cols-1 gap-4">
              <div class="flex justify-between">
                <dt class="text-sm font-medium text-gray-500">Дата создания:</dt>
                <dd class="text-sm text-gray-900">{{ formatDate(currentItem.createdAt) }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm font-medium text-gray-500">Последнее обновление:</dt>
                <dd class="text-sm text-gray-900">{{ formatDate(currentItem.updatedAt) }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm font-medium text-gray-500">Статус:</dt>
                <dd class="text-sm">
                  <span 
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      currentItem.isActive 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-red-100 text-red-800'
                    ]"
                  >
                    {{ currentItem.isActive ? 'Активен' : 'Неактивен' }}
                  </span>
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- Похожие элементы -->
      <div v-if="currentItem && relatedItems.length > 0" class="mt-16">
        <h2 class="text-2xl font-bold text-gray-900 mb-8">Похожие элементы</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <div
            v-for="item in relatedItems"
            :key="item.id"
            class="card hover:shadow-lg transition-shadow duration-300 cursor-pointer"
            @click="goToItem(item.id)"
          >
            <img
              :src="item.image"
              :alt="item.title"
              class="w-full h-40 object-cover rounded-lg mb-3"
            >
            <h3 class="text-sm font-semibold text-gray-900 mb-1 line-clamp-2">{{ item.title }}</h3>
            <div class="flex items-center justify-between">
              <span class="text-xs text-primary-600 font-medium">{{ item.category.name }}</span>
              <div class="flex items-center">
                <span class="text-yellow-400 text-xs mr-1">★</span>
                <span class="text-xs text-gray-600">{{ item.rating }}</span>
              </div>
            </div>
            <div v-if="item.price" class="text-sm font-bold text-gray-900 mt-1">
              {{ formatPrice(item.price) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useItemsStore } from '@/stores/items'
import type { Item } from '@/types'

const router = useRouter()
const route = useRoute()
const itemsStore = useItemsStore()

// Получаем ID из параметров маршрута
const itemId = computed(() => route.params.id as string)

// Вычисляемые свойства
const isLoading = computed(() => itemsStore.isLoading)
const error = computed(() => itemsStore.error)
const currentItem = computed(() => itemsStore.currentItem)
const allItems = computed(() => itemsStore.items)

// Похожие элементы (из той же категории)
const relatedItems = computed(() => {
  if (!currentItem.value) return []
  
  return allItems.value
    .filter(item => 
      item.id !== currentItem.value!.id && 
      item.category.id === currentItem.value!.category.id
    )
    .slice(0, 4)
})

// Методы
const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(price)
}

const formatDate = (date: Date): string => {
  return new Intl.DateTimeFormat('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  }).format(new Date(date))
}

const goToItem = (id: string) => {
  router.push(`/item/${id}`)
}

const loadItem = async () => {
  if (itemId.value) {
    await itemsStore.fetchItemById(itemId.value)
    
    // Загружаем все элементы для показа похожих
    if (allItems.value.length === 0) {
      await itemsStore.fetchItems()
    }
  }
}

// Инициализация
onMounted(() => {
  loadItem()
})

// Отслеживание изменений ID в маршруте
watch(itemId, () => {
  itemsStore.clearCurrentItem()
  loadItem()
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>