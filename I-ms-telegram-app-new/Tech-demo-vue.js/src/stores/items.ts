import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Item, SearchParams, SearchResult, LoadingState } from '@/types'
import { itemsApi } from '@/services/api'

export const useItemsStore = defineStore('items', () => {
  // Состояние
  const items = ref<Item[]>([])
  const currentItem = ref<Item | null>(null)
  const searchResult = ref<SearchResult | null>(null)
  const loadingState = ref<LoadingState>({
    isLoading: false,
    error: null
  })

  // Геттеры
  const isLoading = computed(() => loadingState.value.isLoading)
  const error = computed(() => loadingState.value.error)
  const hasItems = computed(() => items.value.length > 0)
  const featuredItems = computed(() => 
    items.value.filter(item => item.rating >= 4.5).slice(0, 3)
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

  // Загрузить все элементы
  const fetchItems = async () => {
    try {
      setLoading(true)
      clearError()
      
      const response = await itemsApi.getAll()
      
      if (response.success) {
        items.value = response.data
      } else {
        setError(response.error || 'Ошибка загрузки элементов')
      }
    } catch (err) {
      setError('Ошибка сети при загрузке элементов')
      console.error('Error fetching items:', err)
    } finally {
      setLoading(false)
    }
  }

  // Загрузить элемент по ID
  const fetchItemById = async (id: string) => {
    try {
      setLoading(true)
      clearError()
      
      const response = await itemsApi.getById(id)
      
      if (response.success && response.data) {
        currentItem.value = response.data
      } else {
        setError(response.error || 'Элемент не найден')
        currentItem.value = null
      }
    } catch (err) {
      setError('Ошибка сети при загрузке элемента')
      currentItem.value = null
      console.error('Error fetching item:', err)
    } finally {
      setLoading(false)
    }
  }

  // Поиск элементов
  const searchItems = async (params: SearchParams) => {
    try {
      setLoading(true)
      clearError()
      
      const response = await itemsApi.search(params)
      
      if (response.success) {
        searchResult.value = response.data
      } else {
        setError(response.error || 'Ошибка поиска')
        searchResult.value = null
      }
    } catch (err) {
      setError('Ошибка сети при поиске')
      searchResult.value = null
      console.error('Error searching items:', err)
    } finally {
      setLoading(false)
    }
  }

  // Очистить текущий элемент
  const clearCurrentItem = () => {
    currentItem.value = null
  }

  // Очистить результаты поиска
  const clearSearchResults = () => {
    searchResult.value = null
  }

  return {
    // Состояние
    items,
    currentItem,
    searchResult,
    loadingState,
    
    // Геттеры
    isLoading,
    error,
    hasItems,
    featuredItems,
    
    // Действия
    fetchItems,
    fetchItemById,
    searchItems,
    clearCurrentItem,
    clearSearchResults,
    clearError
  }
})