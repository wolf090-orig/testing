// Основные типы данных для приложения NIYAT

// Интерфейс пользователя
export interface User {
  id: string;
  name: string;
  email: string;
  avatar?: string;
  createdAt: Date;
  preferences: UserPreferences;
}

// Настройки пользователя
export interface UserPreferences {
  theme: 'light' | 'dark';
  language: 'en' | 'ru';
  notifications: boolean;
}

// Интерфейс элемента каталога
export interface Item {
  id: string;
  title: string;
  description: string;
  image: string;
  category: Category;
  tags: string[];
  price?: number;
  rating: number;
  reviewsCount: number;
  createdAt: Date;
  updatedAt: Date;
  isActive: boolean;
}

// Интерфейс категории
export interface Category {
  id: string;
  name: string;
  description: string;
  icon: string;
  color: string;
  itemsCount: number;
}

// Интерфейс результата поиска
export interface SearchResult {
  items: Item[];
  totalCount: number;
  currentPage: number;
  totalPages: number;
  hasNextPage: boolean;
  hasPreviousPage: boolean;
}

// Параметры поиска
export interface SearchParams {
  query?: string;
  categoryId?: string;
  tags?: string[];
  minPrice?: number;
  maxPrice?: number;
  sortBy?: 'name' | 'price' | 'rating' | 'date';
  sortOrder?: 'asc' | 'desc';
  page?: number;
  limit?: number;
}

// Интерфейс для навигации
export interface NavigationItem {
  id: string;
  label: string;
  path: string;
  icon: string;
  isActive?: boolean;
}

// Состояние загрузки
export interface LoadingState {
  isLoading: boolean;
  error: string | null;
}

// API ответ
export interface ApiResponse<T> {
  data: T;
  success: boolean;
  message?: string;
  error?: string;
}