import type { User, Item, Category, SearchResult } from '@/types'

// Mock категории
export const mockCategories: Category[] = [
  {
    id: '1',
    name: 'Электроника',
    description: 'Современные электронные устройства и гаджеты',
    icon: 'smartphone',
    color: '#3B82F6',
    itemsCount: 15
  },
  {
    id: '2',
    name: 'Одежда',
    description: 'Модная одежда и аксессуары',
    icon: 'shirt',
    color: '#EF4444',
    itemsCount: 23
  },
  {
    id: '3',
    name: 'Дом и сад',
    description: 'Товары для дома и садоводства',
    icon: 'home',
    color: '#10B981',
    itemsCount: 18
  }
]

// Mock элементы
export const mockItems: Item[] = [
  {
    id: '1',
    title: 'Смартфон Galaxy S24',
    description: 'Современный смартфон с отличной камерой и производительностью',
    image: 'https://trae-api-sg.mchost.guru/api/ide/v1/text_to_image?prompt=modern%20smartphone%20galaxy%20s24%20sleek%20design%20black%20color&image_size=square',
    category: mockCategories[0],
    tags: ['смартфон', 'android', 'камера'],
    price: 89999,
    rating: 4.8,
    reviewsCount: 156,
    createdAt: new Date('2024-01-15'),
    updatedAt: new Date('2024-01-20'),
    isActive: true
  },
  {
    id: '2',
    title: 'Куртка зимняя',
    description: 'Теплая зимняя куртка из качественных материалов',
    image: 'https://trae-api-sg.mchost.guru/api/ide/v1/text_to_image?prompt=winter%20jacket%20warm%20clothing%20dark%20blue%20modern%20style&image_size=square',
    category: mockCategories[1],
    tags: ['куртка', 'зима', 'теплая'],
    price: 12999,
    rating: 4.5,
    reviewsCount: 89,
    createdAt: new Date('2024-01-10'),
    updatedAt: new Date('2024-01-18'),
    isActive: true
  },
  {
    id: '3',
    title: 'Кофеварка автоматическая',
    description: 'Автоматическая кофеварка для приготовления идеального кофе',
    image: 'https://trae-api-sg.mchost.guru/api/ide/v1/text_to_image?prompt=automatic%20coffee%20maker%20machine%20modern%20kitchen%20appliance%20silver&image_size=square',
    category: mockCategories[2],
    tags: ['кофеварка', 'кухня', 'автомат'],
    price: 25999,
    rating: 4.7,
    reviewsCount: 234,
    createdAt: new Date('2024-01-05'),
    updatedAt: new Date('2024-01-22'),
    isActive: true
  }
]

// Mock пользователь
export const mockUser: User = {
  id: '1',
  name: 'Иван Петров',
  email: 'ivan.petrov@example.com',
  avatar: 'https://trae-api-sg.mchost.guru/api/ide/v1/text_to_image?prompt=professional%20avatar%20male%20friendly%20smile%20business%20casual&image_size=square',
  createdAt: new Date('2023-12-01'),
  preferences: {
    theme: 'light',
    language: 'ru',
    notifications: true
  }
}

// Mock результат поиска
export const mockSearchResult: SearchResult = {
  items: mockItems,
  totalCount: 3,
  currentPage: 1,
  totalPages: 1,
  hasNextPage: false,
  hasPreviousPage: false
}