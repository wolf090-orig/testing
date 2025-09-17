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
            <router-link to="/results" class="text-gray-600 hover:text-gray-900">Поиск</router-link>
            <router-link to="/profile" class="btn-secondary">Профиль</router-link>
          </div>
        </div>
      </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Заголовок -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Каталог элементов</h1>
        <p class="text-gray-600">Исследуйте наш полный каталог элементов</p>
      </div>

      <div class="flex flex-col lg:flex-row gap-8">
        <!-- Боковая панель с фильтрами -->
        <aside class="lg:w-1/4">
          <div class="card sticky top-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Фильтры</h3>
            
            <!-- Поиск -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Введите название..."
                class="input-field"
                @input="handleSearch"
              >
            </div>

            <!-- Категории -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Категория</label>
              <select v-model="selectedCategory" class="input-field" @change="handleCategoryChange">
                <option value="">Все категории</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">
                  {{ category.name }} ({{ category.itemsCount }})
                </option>
              </select>
            </div>

            <!-- Сортировка -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Сортировка</label>
              <select v-model="sortBy" class="input-field" @change="handleSortChange">
                <option value="name">По названию</option>
                <option value="rating">По рейтингу</option>
                <option value="date">По дате</option>
                <option value="price">По цене</option>
              </select>
            </div>

            <!-- Порядок сортировки -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Порядок</label>
              <div class="flex space-x-2">
                <button
                  @click="setSortOrder('asc')"
                  :class="[
                    'flex-1 py-2 px-3 text-sm rounded-lg border transition-colors',
                    sortOrder === 'asc' 
                      ? 'bg-primary-600 text-white border-primary-600' 
                      : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                  ]"
                >
                  По возрастанию
                </button>
                <button
                  @click="setSortOrder('desc')"
                  :class="[
                    'flex-1 py-2 px-3 text-sm rounded-lg border transition-colors',
                    sortOrder === 'desc' 
                      ? 'bg-primary-600 text-white border-primary-600' 
                      : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                  ]"
                >
                  По убыванию
                </button>
              </div>
            </div>

            <!-- Кнопка сброса -->
            <button
              @click="resetFilters"
              class="w-full btn-secondary"
            >
              Сбросить фильтры
            </button>
          </div>
        </aside>

        <!-- Основной контент -->
        <main class="lg:w-3/4">
          <!-- Индикатор загрузки -->
          <div v-if="isLoading" class="flex justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
          </div>

          <!-- Ошибка -->
          <div v-else-if="error" class="text-center py-12">
            <div class="text-red-600 mb-4">{{ error }}</div>
            <button @click="loadItems" class="btn-primary">Попробовать снова</button>
          </div>

          <!-- Результаты -->
          <div v-else>
            <!-- Информация о результатах -->
            <div class="flex justify-between items-center mb-6">
              <div class="text-gray-600">
                Найдено {{ filteredItems.length }} элементов
              </div>
              <div class="flex items-center space-x-2">
                <button
                  @click="viewMode = 'grid'"
                  :class="[
                    'p-2 rounded-lg transition-colors',
                    viewMode === 'grid' ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                  ]"
                >
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                  </svg>
                </button>
                <button
                  @click="viewMode = 'list'"
                  :class="[
                    'p-2 rounded-lg transition-colors',
                    viewMode === 'list' ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                  ]"
                >
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Сетка элементов -->
            <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
              <div
                v-for="item in filteredItems"
                :key="item.id"
                class="card hover:shadow-lg transition-shadow duration-300 cursor-pointer"
                @click="goToItem(item.id)"
              >
                <img
                  :src="item.image"
                  :alt="item.title"
                  class="w-full h-48 object-cover rounded-lg mb-4"
                >
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ item.title }}</h3>
                <p class="text-gray-600 mb-4 line-clamp-2">{{ item.description }}</p>
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm text-primary-600 font-medium">{{ item.category.name }}</span>
                  <div class="flex items-center">
                    <span class="text-yellow-400 mr-1">★</span>
                    <span class="text-sm text-gray-600">{{ item.rating }}</span>
                  </div>
                </div>
                <div v-if="item.price" class="text-lg font-bold text-gray-900">
                  {{ formatPrice(item.price) }}
                </div>
              </div>
            </div>

            <!-- Список элементов -->
            <div v-else class="space-y-4">
              <div
                v-for="item in filteredItems"
                :key="item.id"
                class="card hover:shadow-lg transition-shadow duration-300 cursor-pointer"
                @click="goToItem(item.id)"
              >
                <div class="flex">
                  <img
                    :src="item.image"
                    :alt="item.title"
                    class="w-24 h-24 object-cover rounded-lg mr-4"
                  >
                  <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ item.title }}</h3>
                    <p class="text-gray-600 mb-2 line-clamp-2">{{ item.description }}</p>
                    <div class="flex items-center justify-between">
                      <span class="text-sm text-primary-600 font-medium">{{ item.category.name }}</span>
                      <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                          <span class="text-yellow-400 mr-1">★</span>
                          <span class="text-sm text-gray-600">{{ item.rating }}</span>
                        </div>
                        <div v-if="item.price" class="text-lg font-bold text-gray-900">
                          {{ formatPrice(item.price) }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Пустое состояние -->
            <div v-if="filteredItems.length === 0" class="text-center py-12">
              <div class="text-gray-500 mb-4">Элементы не найдены</div>
              <button @click="resetFilters" class="btn-primary">Сбросить фильтры</button>
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useItemsStore } from '@/stores/items'
import { useCategoriesStore } from '@/stores/categories'
import type { Item } from '@/types'

const router = useRouter()
const route = useRoute()
const itemsStore = useItemsStore()
const categoriesStore = useCategoriesStore()

// Реактивные данные
const searchQuery = ref('')
const selectedCategory = ref('')
const sortBy = ref('name')
const sortOrder = ref<'asc' | 'desc'>('asc')
const viewMode = ref<'grid' | 'list'>('grid')

// Вычисляемые свойства
const isLoading = computed(() => itemsStore.isLoading || categoriesStore.isLoading)
const error = computed(() => itemsStore.error || categoriesStore.error)
const items = computed(() => itemsStore.items)
const categories = computed(() => categoriesStore.categories)

const filteredItems = computed(() => {
  let result = [...items.value]

  // Фильтрация по поиску
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(item => 
      item.title.toLowerCase().includes(query) ||
      item.description.toLowerCase().includes(query)
    )
  }

  // Фильтрация по категории
  if (selectedCategory.value) {
    result = result.filter(item => item.category.id === selectedCategory.value)
  }

  // Сортировка
  result.sort((a, b) => {
    let aValue: any, bValue: any
    
    switch (sortBy.value) {
      case 'name':
        aValue = a.title
        bValue = b.title
        break
      case 'price':
        aValue = a.price || 0
        bValue = b.price || 0
        break
      case 'rating':
        aValue = a.rating
        bValue = b.rating
        break
      case 'date':
        aValue = a.createdAt
        bValue = b.createdAt
        break
      default:
        return 0
    }

    if (sortOrder.value === 'desc') {
      return bValue > aValue ? 1 : -1
    }
    return aValue > bValue ? 1 : -1
  })

  return result
})

// Методы
const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(price)
}

const goToItem = (id: string) => {
  router.push(`/item/${id}`)
}

const handleSearch = () => {
  // Поиск выполняется автоматически через computed
}

const handleCategoryChange = () => {
  // Фильтрация выполняется автоматически через computed
}

const handleSortChange = () => {
  // Сортировка выполняется автоматически через computed
}

const setSortOrder = (order: 'asc' | 'desc') => {
  sortOrder.value = order
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedCategory.value = ''
  sortBy.value = 'name'
  sortOrder.value = 'asc'
}

const loadItems = async () => {
  await Promise.all([
    itemsStore.fetchItems(),
    categoriesStore.fetchCategories()
  ])
}

// Инициализация
onMounted(async () => {
  // Проверяем параметры URL
  if (route.query.category) {
    selectedCategory.value = route.query.category as string
  }
  
  await loadItems()
})

// Отслеживание изменений категории в URL
watch(() => route.query.category, (newCategory) => {
  if (newCategory) {
    selectedCategory.value = newCategory as string
  }
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