import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '../services/api'
import { DEFAULT_COUNTRY_CODE, DEFAULT_LANGUAGE_CODE } from '../constants/countries'
import { setupDevUserOverride } from '../utils/setupDevUserOverride'

// Константы для localStorage
const USER_COUNTRY_CODE_KEY = 'user_country_code'
const USER_LANGUAGE_CODE_KEY = 'user_language_code'

export const useUserStore = defineStore('user', () => {
  const id = ref<string | null>(null)
  const firstName = ref<string>('')
  const lastName = ref<string>('')
  const username = ref<string>('')
  const coins = ref<number>(0)
  const activeTickets = ref<number>(0)
  const isAuthenticated = ref<boolean>(false)
  const country = ref<string>('')
  const language = ref<string>('')
  const hasUserSettings = ref<boolean>(false)
  const level = ref<number>(1)
  const loading = ref<boolean>(false)

  // Геттер для полного имени
  const fullName = computed(() => {
    if (firstName.value && lastName.value) {
      return `${firstName.value} ${lastName.value}`
    }
    if (firstName.value) {
      return firstName.value
    }
    if (username.value) {
      return `@${username.value}`
    }
    return 'Гость'
  })

  // Функция для сохранения кода страны в localStorage
  function saveCountryCodeToStorage(countryCode: string) {
    if (countryCode) {
      localStorage.setItem(USER_COUNTRY_CODE_KEY, countryCode)
      console.log(`Код страны "${countryCode}" сохранен в localStorage`)
    }
  }

  // Функция для сохранения кода языка в localStorage
  function saveLanguageCodeToStorage(languageCode: string) {
    if (languageCode) {
      localStorage.setItem(USER_LANGUAGE_CODE_KEY, languageCode)
      console.log(`Код языка "${languageCode}" сохранен в localStorage`)
    }
  }

  // Функция для получения кода страны из localStorage
  function getCountryCodeFromStorage(): string {
    return localStorage.getItem(USER_COUNTRY_CODE_KEY) || DEFAULT_COUNTRY_CODE
  }

  // Функция для получения кода языка из localStorage
  function getLanguageCodeFromStorage(): string {
    return localStorage.getItem(USER_LANGUAGE_CODE_KEY) || DEFAULT_LANGUAGE_CODE
  }

  // Метод для инициализации данных пользователя из Telegram
  async function initUserFromTelegram() {
    try {
      loading.value = true
      
      // Настраиваем dev mock если нужно
      setupDevUserOverride()

      const telegramUser = window.Telegram?.WebApp?.initDataUnsafe?.user
      
      if (telegramUser) {
        id.value = telegramUser.id?.toString() || null
        firstName.value = telegramUser.first_name || ''
        lastName.value = telegramUser.last_name || ''
        username.value = telegramUser.username || ''
        isAuthenticated.value = true
        
        console.log('✅ Пользователь инициализирован из Telegram:', telegramUser)
      } else {
        // Для тестирования в браузере установим фиктивные данные
        id.value = '12345678'
        firstName.value = 'Test'
        lastName.value = 'User'
        username.value = 'test_user'
        isAuthenticated.value = true
        
        console.log('⚠️ Использованы фиктивные данные пользователя')
      }
      
      // Загружаем настройки пользователя и ждем завершения
      await loadUserSettings()
      
      console.log('🎯 Инициализация пользователя завершена')
    } catch (error) {
      console.error('❌ Ошибка при инициализации пользователя:', error)
      // Установим фиктивные данные в случае ошибки
      id.value = '12345678'
      firstName.value = 'Test'
      lastName.value = 'User'
      isAuthenticated.value = true
      coins.value = 10000
      activeTickets.value = 2
    } finally {
      loading.value = false
    }
  }
  
  // Метод для загрузки настроек пользователя
  async function loadUserSettings() {
    try {
      const profile = await api.getUserProfile()
      
      // Проверяем наличие настроек в профиле (поля user_country_code и user_language_code)
      if (profile && profile.user_country_code && profile.user_language_code) {
        country.value = profile.user_country_code
        language.value = profile.user_language_code
        hasUserSettings.value = true
        
        // Сохраняем коды в localStorage для использования в API запросах
        saveCountryCodeToStorage(profile.user_country_code)
        saveLanguageCodeToStorage(profile.user_language_code)
        
        console.log('✅ Загружены настройки пользователя:', { 
          country: country.value, 
          language: language.value 
        })
      } else {
        // Пытаемся загрузить из localStorage
        const storedCountry = getCountryCodeFromStorage()
        const storedLanguage = getLanguageCodeFromStorage()
        
        country.value = storedCountry
        language.value = storedLanguage
        hasUserSettings.value = false
        
        console.log('⚠️ Настройки пользователя загружены из localStorage')
      }

      // Обновляем дополнительные данные из профиля
      if (profile) {
        coins.value = profile.coins || 0
        level.value = profile.level || 1
      }
    } catch (error) {
      console.error('❌ Ошибка при загрузке настроек пользователя:', error)
      
      // Используем настройки по умолчанию
      country.value = getCountryCodeFromStorage()
      language.value = getLanguageCodeFromStorage()
      hasUserSettings.value = false
    }
  }
  
  // Метод для сохранения настроек пользователя
  async function saveUserSettings(countryCode: string, languageCode: string) {
    try {
      loading.value = true
      
      const result = await api.saveUserSettings(countryCode, languageCode)
      
      if (result) {
        country.value = countryCode
        language.value = languageCode
        hasUserSettings.value = true
        
        // Сохраняем коды в localStorage для использования в API запросах
        saveCountryCodeToStorage(countryCode)
        saveLanguageCodeToStorage(languageCode)
        
        console.log('✅ Сохранены настройки пользователя:', { 
          country: country.value, 
          language: language.value 
        })
        
        return true
      }
      
      return false
    } catch (error) {
      console.error('❌ Ошибка при сохранении настроек пользователя:', error)
      return false
    } finally {
      loading.value = false
    }
  }

  // Метод для обновления статистики пользователя
  async function updateUserStats() {
    try {
      const stats = await api.getUserStatistics()
      activeTickets.value = stats.active_tickets
      console.log('📊 Статистика пользователя обновлена')
    } catch (error) {
      console.error('❌ Ошибка при обновлении статистики:', error)
    }
  }

  return { 
    // State
    id, 
    firstName, 
    lastName, 
    username, 
    coins, 
    activeTickets, 
    isAuthenticated,
    country,
    language,
    hasUserSettings,
    level,
    loading,
    
    // Getters
    fullName, 
    
    // Actions
    initUserFromTelegram,
    loadUserSettings,
    saveUserSettings,
    updateUserStats,
    getCountryCodeFromStorage,
    getLanguageCodeFromStorage
  }
}) 