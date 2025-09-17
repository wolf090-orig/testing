import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '../services/api'
import type { Lottery, Cart, UserTicket } from '../types/lottery'

export const useLotteryStore = defineStore('lottery', () => {
  // State
  const lotteries = ref<Lottery[]>([])
  const activeLotteries = ref<Lottery[]>([])
  const historyLotteries = ref<Lottery[]>([])
  const cart = ref<Cart>({ items: [], total_price: 0, total_items: 0 })
  const userTickets = ref<UserTicket[]>([])
  
  // Loading states
  const loading = ref<boolean>(false)
  const cartLoading = ref<boolean>(false)
  const ticketsLoading = ref<boolean>(false)
  
  // Error states
  const error = ref<string | null>(null)
  const cartError = ref<string | null>(null)

  // Getters
  const cartItemsCount = computed(() => cart.value.total_items)
  const cartTotalPrice = computed(() => cart.value.total_price)
  const hasCartItems = computed(() => cart.value.items.length > 0)
  
  const activeTicketsCount = computed(() => 
    userTickets.value.filter(ticket => ticket.status === 'active').length
  )
  
  const winningTicketsCount = computed(() => 
    userTickets.value.filter(ticket => ticket.status === 'winner').length
  )

  // Actions
  async function fetchLotteries(status: 'active' | 'history' = 'active', lotteryType?: string) {
    loading.value = true
    error.value = null
    
    try {
      const fetchedLotteries = await api.getLotteries(status, lotteryType)
      
      if (status === 'active') {
        activeLotteries.value = fetchedLotteries
        lotteries.value = [...fetchedLotteries, ...historyLotteries.value]
      } else {
        historyLotteries.value = fetchedLotteries
        lotteries.value = [...activeLotteries.value, ...fetchedLotteries]
      }
      
      console.log(`✅ Загружено ${fetchedLotteries.length} лотерей (${status})`)
    } catch (e) {
      error.value = 'Ошибка при загрузке лотерей'
      console.error('❌ Ошибка загрузки лотерей:', e)
    } finally {
      loading.value = false
    }
  }

  // Получение лотереи по ID
  function getLotteryById(id: number): Lottery | null {
    return lotteries.value.find(lottery => lottery.id === id) || null
  }

  // Работа с корзиной
  async function loadCart() {
    cartLoading.value = true
    cartError.value = null
    
    try {
      const loadedCart = await api.getUserCart()
      if (loadedCart) {
        cart.value = loadedCart
        console.log('✅ Корзина загружена:', loadedCart)
      }
    } catch (e) {
      cartError.value = 'Ошибка при загрузке корзины'
      console.error('❌ Ошибка загрузки корзины:', e)
    } finally {
      cartLoading.value = false
    }
  }

  async function addToCart(lotteryId: number, quantity: number = 1) {
    cartLoading.value = true
    cartError.value = null
    
    try {
      const success = await api.addTicketToCart(lotteryId, quantity)
      if (success) {
        await loadCart() // Перезагружаем корзину
        console.log(`✅ Добавлено в корзину: лотерея ${lotteryId}, количество ${quantity}`)
        return true
      }
      return false
    } catch (e) {
      cartError.value = 'Ошибка при добавлении в корзину'
      console.error('❌ Ошибка добавления в корзину:', e)
      return false
    } finally {
      cartLoading.value = false
    }
  }

  async function removeFromCart(ticketId?: string) {
    cartLoading.value = true
    cartError.value = null
    
    try {
      const success = await api.removeFromCart(ticketId)
      if (success) {
        await loadCart() // Перезагружаем корзину
        console.log('✅ Удалено из корзины')
        return true
      }
      return false
    } catch (e) {
      cartError.value = 'Ошибка при удалении из корзины'
      console.error('❌ Ошибка удаления из корзины:', e)
      return false
    } finally {
      cartLoading.value = false
    }
  }

  async function clearCart() {
    return await removeFromCart() // Удаляем все
  }

  async function payCart(paymentMethod: string = 'default') {
    cartLoading.value = true
    cartError.value = null
    
    try {
      const success = await api.paymentCart(paymentMethod)
      if (success) {
        cart.value = { items: [], total_price: 0, total_items: 0 }
        console.log('✅ Оплата прошла успешно')
        
        // Обновляем билеты пользователя
        await loadUserTickets()
        return true
      } else {
        cartError.value = 'Ошибка при обработке платежа'
        return false
      }
    } catch (e) {
      cartError.value = 'Ошибка при оплате'
      console.error('❌ Ошибка оплаты:', e)
      return false
    } finally {
      cartLoading.value = false
    }
  }

  // Работа с билетами пользователя
  async function loadUserTickets(status: 'active' | 'history' | 'winner' = 'active', lotteryId?: number) {
    ticketsLoading.value = true
    error.value = null
    
    try {
      userTickets.value = await api.getUserTickets(status, lotteryId)
      console.log(`✅ Загружено ${userTickets.value.length} билетов пользователя (${status})`)
    } catch (e) {
      error.value = 'Ошибка при загрузке билетов'
      console.error('❌ Ошибка загрузки билетов:', e)
    } finally {
      ticketsLoading.value = false
    }
  }

  // Получение билета пользователя по ID
  async function getUserTicketDetails(ticketId: number, withLeaderboard: boolean = false) {
    try {
      const ticketDetails = await api.getUserTicketDetails(ticketId, withLeaderboard)
      console.log('✅ Детали билета загружены')
      return ticketDetails
    } catch (e) {
      console.error('❌ Ошибка загрузки деталей билета:', e)
      return null
    }
  }

  // Инициализация основных данных
  async function initializeStore() {
    console.log('🎯 Инициализация lottery store...')
    
    await Promise.all([
      fetchLotteries('active'),
      loadCart(),
      loadUserTickets('active')
    ])
    
    console.log('✅ Lottery store инициализирован')
  }

  return { 
    // State
    lotteries,
    activeLotteries, 
    historyLotteries,
    cart,
    userTickets,
    loading,
    cartLoading,
    ticketsLoading,
    error,
    cartError,
    
    // Getters
    cartItemsCount,
    cartTotalPrice,
    hasCartItems,
    activeTicketsCount,
    winningTicketsCount,
    
    // Actions
    fetchLotteries, 
    getLotteryById,
    loadCart,
    addToCart,
    removeFromCart,
    clearCart,
    payCart,
    loadUserTickets,
    getUserTicketDetails,
    initializeStore
  }
}) 