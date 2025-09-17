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
            <router-link to="/results" class="text-gray-600 hover:text-gray-900">Поиск</router-link>
          </div>
        </div>
      </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Индикатор загрузки -->
      <div v-if="isLoading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>

      <!-- Ошибка -->
      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-600 mb-4">{{ error }}</div>
        <button @click="loadUserData" class="btn-primary">Попробовать снова</button>
      </div>

      <!-- Профиль пользователя -->
      <div v-else-if="user" class="space-y-8">
        <!-- Заголовок профиля -->
        <div class="card">
          <div class="flex items-center space-x-6">
            <div class="relative">
              <img
                :src="user.avatar || defaultAvatar"
                :alt="user.name"
                class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg"
              >
              <button 
                @click="changeAvatar"
                class="absolute bottom-0 right-0 bg-primary-600 text-white rounded-full p-2 hover:bg-primary-700 transition-colors"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                </svg>
              </button>
            </div>
            <div class="flex-1">
              <h1 class="text-3xl font-bold text-gray-900">{{ user.name }}</h1>
              <p class="text-gray-600">{{ user.email }}</p>
              <p class="text-sm text-gray-500 mt-1">
                Участник с {{ formatDate(user.createdAt) }}
              </p>
            </div>
            <button 
              @click="toggleEditMode"
              :class="[
                'px-4 py-2 rounded-lg font-medium transition-colors',
                isEditMode 
                  ? 'bg-gray-600 hover:bg-gray-700 text-white' 
                  : 'bg-primary-600 hover:bg-primary-700 text-white'
              ]"
            >
              {{ isEditMode ? 'Отменить' : 'Редактировать' }}
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Основная информация -->
          <div class="card">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Основная информация</h2>
            
            <form v-if="isEditMode" @submit.prevent="saveProfile" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Имя</label>
                <input
                  v-model="editForm.name"
                  type="text"
                  required
                  class="input-field"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input
                  v-model="editForm.email"
                  type="email"
                  required
                  class="input-field"
                >
              </div>
              <div class="flex space-x-4">
                <button 
                  type="submit" 
                  :disabled="isSaving"
                  class="btn-primary flex-1 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ isSaving ? 'Сохранение...' : 'Сохранить' }}
                </button>
                <button 
                  type="button" 
                  @click="cancelEdit"
                  class="btn-secondary flex-1"
                >
                  Отменить
                </button>
              </div>
            </form>
            
            <div v-else class="space-y-4">
              <div class="flex justify-between">
                <span class="text-sm font-medium text-gray-500">Имя:</span>
                <span class="text-sm text-gray-900">{{ user.name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm font-medium text-gray-500">Email:</span>
                <span class="text-sm text-gray-900">{{ user.email }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm font-medium text-gray-500">Дата регистрации:</span>
                <span class="text-sm text-gray-900">{{ formatDate(user.createdAt) }}</span>
              </div>
            </div>
          </div>

          <!-- Настройки -->
          <div class="card">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Настройки</h2>
            
            <div class="space-y-6">
              <!-- Тема -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Тема оформления</label>
                <select 
                  v-model="preferences.theme" 
                  @change="updatePreferences"
                  class="input-field"
                >
                  <option value="light">Светлая</option>
                  <option value="dark">Темная</option>
                </select>
              </div>

              <!-- Язык -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Язык интерфейса</label>
                <select 
                  v-model="preferences.language" 
                  @change="updatePreferences"
                  class="input-field"
                >
                  <option value="ru">Русский</option>
                  <option value="en">English</option>
                </select>
              </div>

              <!-- Уведомления -->
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700">Уведомления</label>
                  <p class="text-xs text-gray-500">Получать уведомления о новых элементах</p>
                </div>
                <button
                  @click="toggleNotifications"
                  :class="[
                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                    preferences.notifications ? 'bg-primary-600' : 'bg-gray-200'
                  ]"
                >
                  <span
                    :class="[
                      'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                      preferences.notifications ? 'translate-x-5' : 'translate-x-0'
                    ]"
                  />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Статистика -->
        <div class="card">
          <h2 class="text-xl font-semibold text-gray-900 mb-6">Статистика активности</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
              <div class="text-3xl font-bold text-primary-600 mb-2">{{ stats.viewedItems }}</div>
              <div class="text-sm text-gray-600">Просмотрено элементов</div>
            </div>
            <div class="text-center">
              <div class="text-3xl font-bold text-primary-600 mb-2">{{ stats.searchQueries }}</div>
              <div class="text-sm text-gray-600">Поисковых запросов</div>
            </div>
            <div class="text-center">
              <div class="text-3xl font-bold text-primary-600 mb-2">{{ stats.favoriteItems }}</div>
              <div class="text-sm text-gray-600">Избранных элементов</div>
            </div>
          </div>
        </div>

        <!-- Последние действия -->
        <div class="card">
          <h2 class="text-xl font-semibold text-gray-900 mb-6">Последние действия</h2>
          
          <div class="space-y-4">
            <div 
              v-for="activity in recentActivities" 
              :key="activity.id"
              class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg"
            >
              <div 
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center',
                  activity.type === 'view' ? 'bg-blue-100 text-blue-600' :
                  activity.type === 'search' ? 'bg-green-100 text-green-600' :
                  'bg-purple-100 text-purple-600'
                ]"
              >
                <svg v-if="activity.type === 'view'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                  <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                </svg>
                <svg v-else-if="activity.type === 'search'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
                <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ activity.description }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(activity.timestamp) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Действия аккаунта -->
        <div class="card">
          <h2 class="text-xl font-semibold text-gray-900 mb-6">Действия с аккаунтом</h2>
          
          <div class="space-y-4">
            <button class="w-full btn-secondary text-left">
              Экспорт данных
            </button>
            <button class="w-full btn-secondary text-left">
              Изменить пароль
            </button>
            <button 
              @click="logout"
              class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200"
            >
              Выйти из аккаунта
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import type { User } from '@/types'

const router = useRouter()
const userStore = useUserStore()

// Реактивные данные
const isEditMode = ref(false)
const isSaving = ref(false)
const defaultAvatar = 'https://trae-api-sg.mchost.guru/api/ide/v1/text_to_image?prompt=default%20user%20avatar%20friendly%20professional&image_size=square'

const editForm = ref({
  name: '',
  email: ''
})

const preferences = reactive({
  theme: 'light' as 'light' | 'dark',
  language: 'ru' as 'ru' | 'en',
  notifications: true
})

// Mock данные для статистики и активности
const stats = ref({
  viewedItems: 42,
  searchQueries: 18,
  favoriteItems: 7
})

const recentActivities = ref([
  {
    id: '1',
    type: 'view',
    description: 'Просмотрел элемент "Смартфон Galaxy S24"',
    timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000) // 2 часа назад
  },
  {
    id: '2',
    type: 'search',
    description: 'Выполнил поиск по запросу "куртка зимняя"',
    timestamp: new Date(Date.now() - 5 * 60 * 60 * 1000) // 5 часов назад
  },
  {
    id: '3',
    type: 'favorite',
    description: 'Добавил в избранное "Кофеварка автоматическая"',
    timestamp: new Date(Date.now() - 24 * 60 * 60 * 1000) // 1 день назад
  }
])

// Вычисляемые свойства
const isLoading = computed(() => userStore.isLoading)
const error = computed(() => userStore.error)
const user = computed(() => userStore.user)

// Методы
const formatDate = (date: Date): string => {
  return new Intl.DateTimeFormat('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(date))
}

const toggleEditMode = () => {
  if (isEditMode.value) {
    cancelEdit()
  } else {
    if (user.value) {
      editForm.value.name = user.value.name
      editForm.value.email = user.value.email
    }
    isEditMode.value = true
  }
}

const cancelEdit = () => {
  isEditMode.value = false
  editForm.value.name = ''
  editForm.value.email = ''
}

const saveProfile = async () => {
  if (!user.value) return
  
  isSaving.value = true
  
  try {
    const success = await userStore.updateProfile({
      name: editForm.value.name,
      email: editForm.value.email
    })
    
    if (success) {
      isEditMode.value = false
    }
  } catch (err) {
    console.error('Error saving profile:', err)
  } finally {
    isSaving.value = false
  }
}

const updatePreferences = async () => {
  await userStore.updatePreferences(preferences)
}

const toggleNotifications = () => {
  preferences.notifications = !preferences.notifications
  updatePreferences()
}

const changeAvatar = () => {
  // Заглушка для изменения аватара
  alert('Функция изменения аватара будет реализована в будущем')
}

const logout = () => {
  userStore.logout()
  router.push('/')
}

const loadUserData = async () => {
  await userStore.fetchUser()
  
  if (user.value) {
    preferences.theme = user.value.preferences.theme
    preferences.language = user.value.preferences.language
    preferences.notifications = user.value.preferences.notifications
  }
}

// Инициализация
onMounted(() => {
  loadUserData()
})
</script>