import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User, LoadingState } from '@/types'
import { userApi } from '@/services/api'

export const useUserStore = defineStore('user', () => {
  // Состояние
  const user = ref<User | null>(null)
  const isAuthenticated = ref<boolean>(false)
  const loadingState = ref<LoadingState>({
    isLoading: false,
    error: null
  })

  // Геттеры
  const isLoading = computed(() => loadingState.value.isLoading)
  const error = computed(() => loadingState.value.error)
  const userName = computed(() => user.value?.name || 'Гость')
  const userEmail = computed(() => user.value?.email || '')
  const userAvatar = computed(() => user.value?.avatar || '')
  const userPreferences = computed(() => user.value?.preferences || {
    theme: 'light',
    language: 'ru',
    notifications: true
  })

  // Действия
  const setLoading = (loading: boolean) => {
    loadingState.value.isLoading = loading
  }

  const setError = (error: string | null) => {
    loadingState.value.error = error
  }

  const clearError = () => {
    loadingState.value.error = null
  }

  // Загрузить данные пользователя
  const fetchUser = async () => {
    try {
      setLoading(true)
      clearError()
      
      const response = await userApi.getCurrent()
      
      if (response.success) {
        user.value = response.data
        isAuthenticated.value = true
      } else {
        setError(response.error || 'Ошибка загрузки пользователя')
        user.value = null
        isAuthenticated.value = false
      }
    } catch (err) {
      setError('Ошибка сети при загрузке пользователя')
      user.value = null
      isAuthenticated.value = false
      console.error('Error fetching user:', err)
    } finally {
      setLoading(false)
    }
  }

  // Обновить профиль пользователя
  const updateProfile = async (userData: Partial<User>) => {
    try {
      setLoading(true)
      clearError()
      
      const response = await userApi.updateProfile(userData)
      
      if (response.success) {
        user.value = response.data
        return true
      } else {
        setError(response.error || 'Ошибка обновления профиля')
        return false
      }
    } catch (err) {
      setError('Ошибка сети при обновлении профиля')
      console.error('Error updating profile:', err)
      return false
    } finally {
      setLoading(false)
    }
  }

  // Обновить настройки пользователя
  const updatePreferences = async (preferences: Partial<typeof userPreferences.value>) => {
    if (!user.value) return false
    
    const updatedUser = {
      ...user.value,
      preferences: {
        ...user.value.preferences,
        ...preferences
      }
    }
    
    return await updateProfile(updatedUser)
  }

  // Выйти из системы
  const logout = () => {
    user.value = null
    isAuthenticated.value = false
    clearError()
  }

  // Инициализация (автоматическая загрузка пользователя)
  const initialize = async () => {
    await fetchUser()
  }

  return {
    // Состояние
    user,
    isAuthenticated,
    loadingState,
    
    // Геттеры
    isLoading,
    error,
    userName,
    userEmail,
    userAvatar,
    userPreferences,
    
    // Действия
    fetchUser,
    updateProfile,
    updatePreferences,
    logout,
    initialize,
    clearError
  }
})