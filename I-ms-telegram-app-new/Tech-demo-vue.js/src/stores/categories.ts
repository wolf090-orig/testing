import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Category, LoadingState } from '@/types'
import { categoriesApi } from '@/services/api'

export const useCategoriesStore = defineStore('categories', () => {
  // Состояние
  const categories = ref<Category[]>([])
  const currentCategory = ref<Category | null>(null)
  const loadingState = ref<LoadingState>({
    isLoading: false,
    error: null
  })

  // Геттеры
  const isLoading = computed(() => loadingState.value.isLoading)
  const error = computed(() => loadingState.value.error)
  const hasCategories = computed(() => categories.value.length > 0)
  const totalItemsCount = computed(() => 
    categories.value.reduce((total, category) => total + category.itemsCount, 0)
  )

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

  // Загрузить все категории
  const fetchCategories = async () => {
    try {
      setLoading(true)
      clearError()
      
      const response = await categoriesApi.getAll()
      
      if (response.success) {
        categories.value = response.data
      } else {
        setError(response.error || 'Ошибка загрузки категорий')
      }
    } catch (err) {
      setError('Ошибка сети при загрузке категорий')
      console.error('Error fetching categories:', err)
    } finally {
      setLoading(false)
    }
  }

  // Загрузить категорию по ID
  const fetchCategoryById = async (id: string) => {
    try {
      setLoading(true)
      clearError()
      
      const response = await categoriesApi.getById(id)
      
      if (response.success && response.data) {
        currentCategory.value = response.data
      } else {
        setError(response.error || 'Категория не найдена')
        currentCategory.value = null
      }
    } catch (err) {
      setError('Ошибка сети при загрузке категории')
      currentCategory.value = null
      console.error('Error fetching category:', err)
    } finally {
      setLoading(false)
    }
  }

  // Найти категорию по ID
  const getCategoryById = (id: string): Category | undefined => {
    return categories.value.find(category => category.id === id)
  }

  // Очистить текущую категорию
  const clearCurrentCategory = () => {
    currentCategory.value = null
  }

  return {
    // Состояние
    categories,
    currentCategory,
    loadingState,
    
    // Геттеры
    isLoading,
    error,
    hasCategories,
    totalItemsCount,
    
    // Действия
    fetchCategories,
    fetchCategoryById,
    getCategoryById,
    clearCurrentCategory,
    clearError
  }
})