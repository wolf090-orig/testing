import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

// Импортируем шрифты
import '@fontsource/montserrat/400.css'
import '@fontsource/montserrat/500.css'
import '@fontsource/montserrat/600.css'
import '@fontsource/montserrat/700.css'
import '@fontsource/unbounded/400.css'
import '@fontsource/unbounded/500.css'
import '@fontsource/unbounded/600.css'
import '@fontsource/unbounded/700.css'

// Инициализируем Telegram WebApp
if (typeof window !== 'undefined') {
  // Telegram WebApp готов к работе
  window.Telegram?.WebApp?.ready()
  
  // Настраиваем цветовую схему
  window.Telegram?.WebApp?.setHeaderColor('#2481cc')
  window.Telegram?.WebApp?.setBackgroundColor('#ffffff')
  
  // Разрешаем Telegram управлять закрытием приложения
  window.Telegram?.WebApp?.enableClosingConfirmation()
  
  console.log('🚀 Telegram WebApp инициализирован')
  console.log('📱 Платформа:', window.Telegram?.WebApp?.platform)
  console.log('🎨 Цветовая схема:', window.Telegram?.WebApp?.colorScheme)
}

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')

console.log('✅ Vue приложение Niyat запущено')
