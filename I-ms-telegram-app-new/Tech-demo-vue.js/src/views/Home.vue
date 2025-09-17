<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Навигационная панель -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center">
            <h1 class="text-2xl font-bold text-primary-600">NIYAT</h1>
          </div>
          <div class="flex items-center space-x-4">
            <router-link to="/catalog" class="btn-primary">
              Каталог
            </router-link>
            <router-link to="/profile" class="btn-secondary">
              Профиль
            </router-link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Главный контент -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <!-- Герой секция -->
      <section class="text-center mb-16">
        <h2 class="text-4xl md:text-6xl font-bold text-gray-900 mb-6">
          Добро пожаловать в 
          <span class="text-primary-600">NIYAT</span>
        </h2>
        <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
          Универсальный каталог элементов для всех ваших потребностей. 
          Исследуйте, находите и управляйте элементами с легкостью.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <router-link to="/catalog" class="btn-primary text-lg px-8 py-3">
            Начать исследование
          </router-link>
          <router-link to="/results" class="btn-secondary text-lg px-8 py-3">
            Поиск элементов
          </router-link>
        </div>
      </section>

      <!-- Статистика -->
      <section class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
        <div class="card text-center">
          <div class="text-3xl font-bold text-primary-600 mb-2">{{ totalItemsCount }}</div>
          <div class="text-gray-600">Всего элементов</div>
        </div>
        <div class="card text-center">
          <div class="text-3xl font-bold text-primary-600 mb-2">{{ categoriesCount }}</div>
          <div class="text-gray-600">Категорий</div>
        </div>
        <div class="card text-center">
          <div class="text-3xl font-bold text-primary-600 mb-2">{{ featuredItemsCount }}</div>
          <div class="text-gray-600">Рекомендуемых</div>
        </div>
      </section>

      <!-- Рекомендуемые элементы -->
      <section class="mb-16">
        <h3 class="text-3xl font-bold text-gray-900 mb-8 text-center">
          Рекомендуемые элементы
        </h3>
        <div v-if="isLoading" class="flex justify-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        </div>
        <div v-else-if="error" class="text-center text-red-600">
          {{ error }}
        </div>
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div 
            v-for="item in featuredItems" 
            :key="item.id" 
            class="card hover:shadow-lg transition-shadow duration-300 cursor-pointer"
            @click="goToItem(item.id)"
          >
            <img 
              :src="item.image" 
              :alt="item.title"
              class="w-full h-48 object-cover rounded-lg mb-4"
            >
            <h4 class="text-xl font-semibold text-gray-900 mb-2">{{ item.title }}</h4>
            <p class="text-gray-600 mb-4 line-clamp-2">{{ item.description }}</p>
            <div class="flex items-center justify-between">
              <span class="text-sm text-primary-600 font-medium">{{ item.category.name }}</span>
              <div class="flex items-center">
                <span class="text-yellow-400 mr-1">★</span>
                <span class="text-sm text-gray-600">{{ item.rating }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Категории -->
      <section>
        <h3 class="text-3xl font-bold text-gray-900 mb-8 text-center">
          Популярные категории
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div 
            v-for="category in categories" 
            :key="category.id"
            class="card hover:shadow-lg transition-all duration-300 cursor-pointer group"
            @click="goToCategory(category.id)"
          >
            <div class="flex items-center mb-4">
              <div 
                class="w-12 h-12 rounded-lg flex items-center justify-center mr-4 group-hover:scale-110 transition-transform"
                :style="{ backgroundColor: category.color + '20', color: category.color }"
              >
                <i :class="`icon-${category.icon}`" class="text-xl"></i>
              </div>
              <div>
                <h4 class="text-lg font-semibold text-gray-900">{{ category.name }}</h4>
                <p class="text-sm text-gray-600">{{ category.itemsCount }} элементов</p>
              </div>
            </div>
            <p class="text-gray-600 text-sm">{{ category.description }}</p>
          </div>
        </div>
      </section>
    </main>

    <!-- Футер -->
    <footer class="bg-white border-t border-gray-200 mt-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center text-gray-600">
          <p>&copy; 2024 NIYAT. Все права защищены.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useItemsStore } from '@/stores/items'
import { useCategoriesStore } from '@/stores/categories'

const router = useRouter()
const itemsStore = useItemsStore()
const categoriesStore = useCategoriesStore()

// Вычисляемые свойства
const isLoading = computed(() => itemsStore.isLoading || categoriesStore.isLoading)
const error = computed(() => itemsStore.error || categoriesStore.error)
const featuredItems = computed(() => itemsStore.featuredItems)
const categories = computed(() => categoriesStore.categories)
const totalItemsCount = computed(() => categoriesStore.totalItemsCount)
const categoriesCount = computed(() => categoriesStore.categories.length)
const featuredItemsCount = computed(() => itemsStore.featuredItems.length)

// Методы
const goToItem = (id: string) => {
  router.push(`/item/${id}`)
}

const goToCategory = (categoryId: string) => {
  router.push({ path: '/catalog', query: { category: categoryId } })
}

// Инициализация
onMounted(async () => {
  await Promise.all([
    itemsStore.fetchItems(),
    categoriesStore.fetchCategories()
  ])
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