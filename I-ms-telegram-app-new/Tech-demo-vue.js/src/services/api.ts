import type { Item, Category, User, SearchResult, SearchParams, ApiResponse } from '@/types'
import { mockItems, mockCategories, mockUser, mockSearchResult } from './mockData'

// Симуляция задержки API
const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms))

// API сервис для работы с элементами
export const itemsApi = {
  // Получить все элементы
  async getAll(): Promise<ApiResponse<Item[]>> {
    await delay(500)
    return {
      data: mockItems,
      success: true,
      message: 'Элементы успешно загружены'
    }
  },

  // Получить элемент по ID
  async getById(id: string): Promise<ApiResponse<Item | null>> {
    await delay(300)
    const item = mockItems.find(item => item.id === id)
    return {
      data: item || null,
      success: !!item,
      message: item ? 'Элемент найден' : 'Элемент не найден'
    }
  },

  // Поиск элементов
  async search(params: SearchParams): Promise<ApiResponse<SearchResult>> {
    await delay(400)
    let filteredItems = [...mockItems]

    // Фильтрация по запросу
    if (params.query) {
      const query = params.query.toLowerCase()
      filteredItems = filteredItems.filter(item => 
        item.title.toLowerCase().includes(query) ||
        item.description.toLowerCase().includes(query)
      )
    }

    // Фильтрация по категории
    if (params.categoryId) {
      filteredItems = filteredItems.filter(item => item.category.id === params.categoryId)
    }

    // Сортировка
    if (params.sortBy) {
      filteredItems.sort((a, b) => {
        let aValue: any, bValue: any
        
        switch (params.sortBy) {
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

        if (params.sortOrder === 'desc') {
          return bValue > aValue ? 1 : -1
        }
        return aValue > bValue ? 1 : -1
      })
    }

    const result: SearchResult = {
      items: filteredItems,
      totalCount: filteredItems.length,
      currentPage: params.page || 1,
      totalPages: Math.ceil(filteredItems.length / (params.limit || 10)),
      hasNextPage: false,
      hasPreviousPage: false
    }

    return {
      data: result,
      success: true,
      message: 'Поиск выполнен успешно'
    }
  }
}

// API сервис для работы с категориями
export const categoriesApi = {
  // Получить все категории
  async getAll(): Promise<ApiResponse<Category[]>> {
    await delay(300)
    return {
      data: mockCategories,
      success: true,
      message: 'Категории успешно загружены'
    }
  },

  // Получить категорию по ID
  async getById(id: string): Promise<ApiResponse<Category | null>> {
    await delay(200)
    const category = mockCategories.find(cat => cat.id === id)
    return {
      data: category || null,
      success: !!category,
      message: category ? 'Категория найдена' : 'Категория не найдена'
    }
  }
}

// API сервис для работы с пользователем
export const userApi = {
  // Получить текущего пользователя
  async getCurrent(): Promise<ApiResponse<User>> {
    await delay(200)
    return {
      data: mockUser,
      success: true,
      message: 'Пользователь загружен'
    }
  },

  // Обновить профиль пользователя
  async updateProfile(userData: Partial<User>): Promise<ApiResponse<User>> {
    await delay(500)
    const updatedUser = { ...mockUser, ...userData }
    return {
      data: updatedUser,
      success: true,
      message: 'Профиль обновлен успешно'
    }
  }
}