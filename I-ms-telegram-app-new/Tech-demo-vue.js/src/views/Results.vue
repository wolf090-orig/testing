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
      <!-- Заголовок -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Поиск и результаты</h1>
        <p class="text-gray-600">Найдите нужные элементы с помощью расширенного поиска</p>
      </div>

      <!-- Форма поиска -->
      <div class="card mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Параметры поиска</h2>
        
        <form @submit.prevent="performSearch" class="space-y-6">
          <!-- Основной поиск -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Поисковый запрос</label>
              <input
                v-model="searchParams.query"
                type="text"
                placeholder="Введите ключевые слова..."
                class="input-field"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Категория</label>
              <select v-model="searchParams.categoryId" class="input-field">
                <option value="">Все категории</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Фильтры по цене -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Минимальная цена</label>
              <input
                v-model.number="searchParams.minPrice"
                type="number"
                placeholder="0"
                min="0"
                class="input-field"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Максимальная цена</label>
              <input
                v-model.number="searchParams.maxPrice"
                type="number"
                placeholder="Без ограничений"
                min="0"
                class="input-field"
              >
            </div>
          </div>

          <!-- Сортировка -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Сортировать по</label>
              <select v-model="searchParams.sortBy" class="input-field">
                <option value="name">Названию</option>
                <option value="price">Цене</option>
                <option value="rating">Рейтингу</option>
                <option value="date">Дате создания</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Порядок сортировки</label>
              <select v-model="searchParams.sortOrder" class="input-field">
                <option value="asc">По возрастанию</option>
                <option value="desc">По убыванию</option>
              </select>
            </div>
          </div>

          <!-- Теги -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Теги (через запятую)</label>
            <input
              v-model="tagsInput"
              type="text"
              placeholder="Например: смартфон, android, камера"
              class="input-field"
            >
            <div v-if="searchParams.tags && searchParams.tags.length > 0" class="mt-2 flex flex-wrap gap-2">
              <span
                v-for="tag in searchParams.tags"
                :key="tag"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800"
              >
                {{ tag }}
                <button
                  type="button"
                  @click="removeTag(tag)"
                  class="ml-1 text-primary-600 hover:text-primary-800"
                >
                  ×
                </button>
              </span>
            </div>
          </div>

          <!-- Кнопки -->
          <div class="flex flex-col sm:flex-row gap-4">
            <button
              type="submit"
              :disabled="isLoading"
              class="btn-primary flex-1 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="isLoading" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Поиск...
              </span>
              <span v-else>Найти элементы</span>
            </button>
            <button
              type="button"
              @click="resetSearch"
              class="btn-secondary flex-1"
            >
              Сбросить
            </button>
          </div>
        </form>
      </div>

      <!-- Результаты поиска -->
      <div v-if="hasSearched">
        <!-- Индикатор загрузки -->
        <div v-if="isLoading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        </div>

        <!-- Ошибка -->
        <div v-else-if="error" class="text-center py-12">
          <div class="text-red-600 mb-4">{{ error }}</div>
          <button @click="performSearch" class="btn-primary">Попробовать снова</button>
        </div>

        <!-- Результаты -->
        <div v-else-if="searchResult">
          <!-- Информация о результатах -->
          <div class="flex justify-between items-center mb-6">
            <div class="text-gray-600">
              Найдено {{ searchResult.totalCount }} элементов
              <span v-if="searchParams.query" class="font-medium">
                по запросу "{{ searchParams.query }}"
              </span>
            </div>
            <div class="text-sm text-gray-500">
              Страница {{ searchResult.currentPage }} из {{ searchResult.totalPages }}
            </div>
          </div>

          <!-- Список результатов -->
          <div v-if="searchResult.items.length > 0" class="space-y-6">
            <div
              v-for="item in searchResult.items"
              :key="item.id"
              class="card hover:shadow-lg transition-shadow duration-300 cursor-pointer"
              @click="goToItem(item.id)"
            >
              <div class="flex">
                <img
                  :src="item.image"
                  :alt="item.title"
                  class="w-32 h-32 object-cover rounded-lg mr-6"
                >
                <div class="flex-1">
                  <div class="flex justify-between items-start mb-2">
                    <h3 class="text-xl font-semibold text-gray-900">{{ item.title }}</h3>
                    <div v-if="item.price" class="text-xl font-bold text-gray-900">
                      {{ formatPrice(item.price) }}
                    </div>
                  </div>
                  
                  <p class="text-gray-600 mb-4 line-clamp-3">{{ item.description }}</p>
                  
                  <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                      <span 
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :style="{ 
                          backgroundColor: item.category.color + '20', 
                          color: item.category.color 
                        }"
                      >
                        {{ item.category.name }}
                      </span>
                      <div class="flex items-center">
                        <span class="text-yellow-400 mr-1">★</span>
                        <span class="text-sm text-gray-600">{{ item.rating }} ({{ item.reviewsCount }})</span>
                      </div>
                    </div>
                    
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-for="tag in item.tags.slice(0, 3)"
                        :key="tag"
                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800"
                      >
                        {{ tag }}
                      </span>
                      <span v-if="item.tags.length > 3" class="text-xs text-gray-500">
                        +{{ item.tags.length - 3 }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Пустое состояние -->
          <div v-else class="text-center py-12">
            <div class="text-gray-500 mb-4">По вашему запросу ничего не найдено</div>
            <button @click="resetSearch" class="btn-primary">Изменить параметры поиска</button>
          </div>

          <!-- Пагинация (заглушка) -->
          <div v-if="searchResult.totalPages > 1" class="flex justify-center mt-8">
            <div class="flex space-x-2">
              <button
                :disabled="!searchResult.hasPreviousPage"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Предыдущая
              </button>
              <span class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md">
                {{ searchResult.currentPage }} из {{ searchResult.totalPages }}
              </span>
              <button
                :disabled="!searchResult.hasNextPage"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Следующая
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Состояние до поиска -->
      <div v-else class="text-center py-12">
        <div class="text-gray-500 mb-4">Введите параметры поиска и нажмите "Найти элементы"</div>
        <div class="text-sm text-gray-400">Используйте фильтры для более точного поиска</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useItemsStore } from '@/stores/items'
import { useCategoriesStore } from '@/stores/categories'
import type { SearchParams } from '@/types'

const router = useRouter()
const itemsStore = useItemsStore()
const categoriesStore = useCategoriesStore()

// Реактивные данные
const hasSearched = ref(false)
const tagsInput = ref('')

const searchParams = ref<SearchParams>({
  query: '',
  categoryId: '',
  tags: [],
  minPrice: undefined,
  maxPrice: undefined,
  sortBy: 'name',
  sortOrder: 'asc',
  page: 1,
  limit: 10
})

// Вычисляемые свойства
const isLoading = computed(() => itemsStore.isLoading)
const error = computed(() => itemsStore.error)
const searchResult = computed(() => itemsStore.searchResult)
const categories = computed(() => categoriesStore.categories)

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

const performSearch = async () => {
  // Обработка тегов
  if (tagsInput.value) {
    searchParams.value.tags = tagsInput.value
      .split(',')
      .map(tag => tag.trim())
      .filter(tag => tag.length > 0)
  } else {
    searchParams.value.tags = []
  }

  hasSearched.value = true
  await itemsStore.searchItems(searchParams.value)
}

const resetSearch = () => {
  searchParams.value = {
    query: '',
    categoryId: '',
    tags: [],
    minPrice: undefined,
    maxPrice: undefined,
    sortBy: 'name',
    sortOrder: 'asc',
    page: 1,
    limit: 10
  }
  tagsInput.value = ''
  hasSearched.value = false
  itemsStore.clearSearchResults()
}

const removeTag = (tagToRemove: string) => {
  if (searchParams.value.tags) {
    searchParams.value.tags = searchParams.value.tags.filter(tag => tag !== tagToRemove)
    tagsInput.value = searchParams.value.tags.join(', ')
  }
}

// Инициализация
onMounted(async () => {
  await categoriesStore.fetchCategories()
})

// Отслеживание изменений в поле тегов
watch(tagsInput, (newValue) => {
  if (newValue) {
    searchParams.value.tags = newValue
      .split(',')
      .map(tag => tag.trim())
      .filter(tag => tag.length > 0)
  } else {
    searchParams.value.tags = []
  }
})
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>