import axios from 'axios';
import config from '../config';
import { DEFAULT_COUNTRY_CODE, SUPPORTED_COUNTRIES, SUPPORTED_LANGUAGES } from '../constants/countries';
import type {
  Lottery,
  LotteriesResponse,
  Ticket,
  UserTicket,
  UserTicketDetailed,
  Cart,
  CartItem,
  ApiResponse,
  UserStats
} from '../types/lottery';

// Базовый URL API из конфигурации
const API_URL = config.apiUrl;

// Создаем экземпляр axios с базовыми настройками
const apiClient = axios.create({
  baseURL: API_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Функция для получения кода страны из localStorage
function getStoredCountryCode(): string {
  const stored = localStorage.getItem('user_country_code');
  console.log('Получен код страны из localStorage:', stored || 'не найден, используем default');
  return stored || DEFAULT_COUNTRY_CODE;
}

// Mock данные для разработки
const MOCK_LOTTERIES: Lottery[] = [
  {
    id: 1,
    name: "Jackpot Million",
    description: "Крупнейший джекпот в истории! Выиграй до 1 000 000 сум!",
    type_name: "Недельная",
    status: "active",
    price: 5000,
    drawn_amount: 1000000,
    participants: 234,
    next_draw_at: "2025-01-30T20:00:00Z",
    draw_date: "2025-01-30T20:00:00Z",
    end_date: "2025-01-30T18:00:00Z",
    image_url: "/lottery-jackpot.jpg"
  },
  {
    id: 2,
    name: "Daily Lucky",
    description: "Ежедневная лотерея с гарантированными призами!",
    type_name: "Ежедневная",
    status: "active",
    price: 2000,
    drawn_amount: 50000,
    participants: 89,
    next_draw_at: "2025-01-26T21:00:00Z",
    draw_date: "2025-01-26T21:00:00Z",
    end_date: "2025-01-26T20:45:00Z",
    image_url: "/lottery-daily.jpg"
  },
  {
    id: 3,
    name: "Super Prize",
    description: "Супер приз для суперигроков!",
    type_name: "Месячная",
    status: "active",
    price: 10000,
    drawn_amount: 500000,
    participants: 156,
    next_draw_at: "2025-02-01T20:00:00Z",
    draw_date: "2025-02-01T20:00:00Z",
    end_date: "2025-02-01T19:00:00Z",
    image_url: "/lottery-super.jpg"
  },
  {
    id: 4,
    name: "Lucky Numbers",
    description: "Выбери свои счастливые числа!",
    type_name: "Недельная",
    status: "history",
    price: 3000,
    drawn_amount: 150000,
    participants: 67,
    last_draw_at: "2025-01-20T20:00:00Z",
    image_url: "/lottery-numbers.jpg"
  }
];

const MOCK_TICKETS: Ticket[] = [
  { ticket_number: "001234", price: 5000, lottery_id: 1, status: "available" },
  { ticket_number: "001235", price: 5000, lottery_id: 1, status: "available" },
  { ticket_number: "001236", price: 5000, lottery_id: 1, status: "available" },
  { ticket_number: "002001", price: 2000, lottery_id: 2, status: "available" },
  { ticket_number: "002002", price: 2000, lottery_id: 2, status: "available" },
  { ticket_number: "003100", price: 10000, lottery_id: 3, status: "available" },
];

let mockCart: Cart = {
  items: [],
  total_price: 0,
  total_items: 0
};

const MOCK_USER_TICKETS: UserTicket[] = [
  {
    id: 1,
    lottery_id: 1,
    ticket_id: 101,
    ticket_number: "001200",
    purchased_at: "2025-01-20T14:30:00Z",
    lottery_name: "Jackpot Million",
    draw_date: "2025-01-30T20:00:00Z",
    is_drawn: false,
    lottery_type_name: "Недельная",
    lottery_type_id: 1,
    status: "active"
  },
  {
    id: 2,
    lottery_id: 2,
    ticket_id: 102,
    ticket_number: "002050",
    purchased_at: "2025-01-24T16:15:00Z",
    lottery_name: "Daily Lucky",
    draw_date: "2025-01-25T21:00:00Z",
    is_drawn: true,
    lottery_type_name: "Ежедневная",
    lottery_type_id: 2,
    win_amount: 5000,
    winner_position: 3,
    status: "winner"
  }
];

// Добавляем интерцепторы для отладки
if (config.apiDebug) {
  // Интерцептор запросов
  apiClient.interceptors.request.use(request => {
    console.log('🚀 API Request:', {
      url: request.url,
      method: request.method?.toUpperCase(),
      params: request.params,
      data: request.data
    });
    return request;
  });

  // Интерцептор ответов
  apiClient.interceptors.response.use(
    response => {
      console.log('✅ API Response:', {
        url: response.config.url,
        status: response.status,
        statusText: response.statusText,
        data: response.data
      });
      return response;
    },
    error => {
      console.log('❌ API Error:', {
        url: error.config?.url,
        status: error.response?.status,
        statusText: error.response?.statusText,
        message: error.message,
        data: error.response?.data
      });
      return Promise.reject(error);
    }
  );
}

// Добавляем Telegram заголовки
apiClient.interceptors.request.use(config => {
  const isTelegramApp = typeof window !== 'undefined' && window.Telegram?.WebApp;
  const telegramInitData = isTelegramApp ? window.Telegram?.WebApp?.initData : null;

  if (isTelegramApp && telegramInitData) {
    // Приложение запущено в Telegram - используем настоящие данные
    config.headers['X-Telegram-Init-Data'] = telegramInitData;
    console.log('Запрос из реального Telegram. Добавлен заголовок X-Telegram-Init-Data');
  } else if (!isTelegramApp && import.meta.env.MODE === 'development' && import.meta.env.VITE_DEV_USER_OVERRIDE === 'true') {
    // Приложение запущено в браузере - включаем режим разработки
    config.headers['X-Dev-User-Override'] = 'true';
    console.log('Режим разработки активирован. Добавлен заголовок X-Dev-User-Override');
  }

  // Добавляем код страны для /ticket/ запросов
  if (config.url?.includes('/ticket/')) {
    const countryCode = getStoredCountryCode();
    if (config.params) {
      config.params.country_code = countryCode;
    } else {
      config.params = { country_code: countryCode };
    }
    console.log(`Добавлен country_code: ${countryCode} для ticket API`);
  }

  return config;
});

// Симуляция задержки для реалистичности
const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms));

// ============ API METHODS (с mock данными) ============

export async function getLotteries(status: 'active' | 'history' = 'active', lottery_type?: string): Promise<Lottery[]> {
  await delay(500); // Симуляция сетевой задержки
  
  console.log(`📡 Mock API: getLotteries(status=${status}, lottery_type=${lottery_type})`);
  
  let filteredLotteries = MOCK_LOTTERIES.filter(lottery => lottery.status === status);
  
  if (lottery_type) {
    filteredLotteries = filteredLotteries.filter(lottery => 
      lottery.type_name.toLowerCase().includes(lottery_type.toLowerCase())
    );
  }
  
  return filteredLotteries;
}

export async function getAvailableTickets(
  type: 'auto' | 'manual', 
  quantity?: number, 
  mask?: string, 
  lotteryId?: number
): Promise<Ticket[]> {
  await delay(300);
  
  console.log(`📡 Mock API: getAvailableTickets(type=${type}, quantity=${quantity}, lotteryId=${lotteryId})`);
  
  let filteredTickets = [...MOCK_TICKETS];
  
  if (lotteryId) {
    filteredTickets = filteredTickets.filter(ticket => ticket.lottery_id === lotteryId);
  }
  
  if (quantity) {
    filteredTickets = filteredTickets.slice(0, quantity);
  }
  
  return filteredTickets;
}

export async function addTicketNumbersToCart(ticketNumbers: string[]): Promise<boolean> {
  await delay(200);
  
  console.log(`📡 Mock API: addTicketNumbersToCart`, ticketNumbers);
  
  for (const ticketNumber of ticketNumbers) {
    const ticket = MOCK_TICKETS.find(t => t.ticket_number === ticketNumber);
    if (ticket) {
      const cartItem: CartItem = {
        id: `cart_${Date.now()}_${Math.random()}`,
        ticket_number: ticketNumber,
        lottery_id: ticket.lottery_id,
        lottery_name: MOCK_LOTTERIES.find(l => l.id === ticket.lottery_id)?.name,
        price: ticket.price
      };
      mockCart.items.push(cartItem);
    }
  }
  
  updateCartTotals();
  return true;
}

export async function addTicketToCart(lotteryId: number, quantity: number): Promise<boolean> {
  await delay(200);
  
  console.log(`📡 Mock API: addTicketToCart(lotteryId=${lotteryId}, quantity=${quantity})`);
  
  const lottery = MOCK_LOTTERIES.find(l => l.id === lotteryId);
  if (!lottery) return false;
  
  for (let i = 0; i < quantity; i++) {
    const ticketNumber = `${lotteryId.toString().padStart(3, '0')}${Math.floor(Math.random() * 1000).toString().padStart(3, '0')}`;
    const cartItem: CartItem = {
      id: `cart_${Date.now()}_${Math.random()}`,
      ticket_number: ticketNumber,
      lottery_id: lotteryId,
      lottery_name: lottery.name,
      price: lottery.price
    };
    mockCart.items.push(cartItem);
  }
  
  updateCartTotals();
  return true;
}

export async function getUserCart(): Promise<Cart | null> {
  await delay(100);
  
  console.log(`📡 Mock API: getUserCart`);
  
  return { ...mockCart };
}

export async function removeFromCart(ticketId?: string): Promise<boolean> {
  await delay(100);
  
  console.log(`📡 Mock API: removeFromCart(ticketId=${ticketId})`);
  
  if (ticketId) {
    mockCart.items = mockCart.items.filter(item => item.id !== ticketId);
  } else {
    mockCart.items = [];
  }
  
  updateCartTotals();
  return true;
}

export async function paymentCart(paymentMethod: string): Promise<boolean> {
  await delay(1000); // Дольше для имитации обработки платежа
  
  console.log(`📡 Mock API: paymentCart(paymentMethod=${paymentMethod})`);
  
  // Симуляция успешной оплаты
  const success = Math.random() > 0.1; // 90% успеха
  
  if (success) {
    mockCart = { items: [], total_price: 0, total_items: 0 };
  }
  
  return success;
}

export async function getUserTickets(
  status: 'active' | 'history' | 'winner' = 'active', 
  lotteryId?: number
): Promise<UserTicket[]> {
  await delay(400);
  
  console.log(`📡 Mock API: getUserTickets(status=${status}, lotteryId=${lotteryId})`);
  
  let filteredTickets = [...MOCK_USER_TICKETS];
  
  if (status !== 'active') {
    filteredTickets = filteredTickets.filter(ticket => ticket.status === status);
  } else {
    filteredTickets = filteredTickets.filter(ticket => ticket.status === 'active');
  }
  
  if (lotteryId) {
    filteredTickets = filteredTickets.filter(ticket => ticket.lottery_id === lotteryId);
  }
  
  return filteredTickets;
}

export async function getUserTicketDetails(id: number, withLeaderboard: boolean = false): Promise<UserTicketDetailed | null> {
  await delay(300);
  
  console.log(`📡 Mock API: getUserTicketDetails(id=${id}, withLeaderboard=${withLeaderboard})`);
  
  const userTicket = MOCK_USER_TICKETS.find(ticket => ticket.id === id);
  if (!userTicket) return null;
  
  const lottery = MOCK_LOTTERIES.find(l => l.id === userTicket.lottery_id);
  if (!lottery) return null;
  
  const detailed: UserTicketDetailed = {
    ...userTicket,
    lottery,
    is_winner: !!userTicket.win_amount,
    winning_amount: userTicket.win_amount ?? undefined
  };
  
  if (withLeaderboard) {
    detailed.leaderboard = [
      { position: 1, ticket_number: "001199", user_name: "Анвар", prize_amount: 100000 },
      { position: 2, ticket_number: "001156", user_name: "Малика", prize_amount: 50000 },
      { position: 3, ticket_number: "002050", prize_amount: 25000 },
    ];
  }
  
  return detailed;
}

export async function getLotteryInfo(lotteryId: number): Promise<any> {
  await delay(200);
  
  console.log(`📡 Mock API: getLotteryInfo(lotteryId=${lotteryId})`);
  
  const lottery = MOCK_LOTTERIES.find(l => l.id === lotteryId);
  return lottery ? {
    lottery,
    rules: "Правила лотереи...",
    prizes: ["Главный приз", "Второй приз", "Третий приз"]
  } : null;
}

export async function getUserStatistics(): Promise<UserStats> {
  await delay(300);
  
  console.log(`📡 Mock API: getUserStatistics`);
  
  return {
    total_tickets: 15,
    active_tickets: 3,
    total_winnings: 25000,
    biggest_win: 15000,
    tickets_this_month: 8
  };
}

export async function getUserProfile(): Promise<any> {
  await delay(200);
  
  console.log(`📡 Mock API: getUserProfile`);
  
  return {
    id: "12345678",
    first_name: "Test",
    last_name: "User",
    username: "test_user",
    user_country_code: getStoredCountryCode(),
    user_language_code: localStorage.getItem('user_language_code') || 'ru',
    coins: 45000,
    level: 3,
    created_at: "2024-01-15T10:00:00Z"
  };
}

export async function saveUserSettings(country: string, language: string): Promise<boolean> {
  await delay(400);
  
  console.log(`📡 Mock API: saveUserSettings(country=${country}, language=${language})`);
  
  try {
    // Имитация сохранения в localStorage для dev режима
    localStorage.setItem('user_country_code', country.trim());
    localStorage.setItem('user_language_code', language.trim());
    console.log('Настройки сохранены в localStorage для режима разработки');
    
    return true;
  } catch (error: any) {
    console.error('Ошибка при сохранении настроек пользователя:', error);
    return false;
  }
}

export async function getAvailableSettings(): Promise<{countries: any[], languages: any[]}> {
  await delay(200);
  
  console.log(`📡 Mock API: getAvailableSettings`);
  
  return {
    countries: SUPPORTED_COUNTRIES,
    languages: SUPPORTED_LANGUAGES
  };
}

// Helper функция для обновления totals в корзине
function updateCartTotals() {
  mockCart.total_items = mockCart.items.length;
  mockCart.total_price = mockCart.items.reduce((sum, item) => sum + item.price, 0);
}

// Экспорт default для совместимости
export default {
  getLotteries,
  getAvailableTickets,
  addTicketNumbersToCart,
  addTicketToCart,
  getUserCart,
  removeFromCart,
  paymentCart,
  getUserTickets,
  getUserTicketDetails,
  getLotteryInfo,
  getUserStatistics,
  getUserProfile,
  saveUserSettings,
  getAvailableSettings
}; 