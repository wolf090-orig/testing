// Конфигурация приложения
export default {
  apiUrl: import.meta.env.VITE_API_URL || '/api/v1',
  debug: import.meta.env.VITE_DEBUG === 'true',
  apiDebug: import.meta.env.VITE_API_DEBUG === 'true',
  appName: import.meta.env.VITE_APP_NAME || 'Niyat',
  port: parseInt(import.meta.env.VITE_APP_PORT) || 5173,
  winnersImagesUrl: import.meta.env.VITE_WINNERS_IMAGES_URL || '/winners',
  
  // Dev режим настройки
  devUserOverride: import.meta.env.VITE_DEV_USER_OVERRIDE === 'true',
  devUser: {
    id: import.meta.env.VITE_DEV_USER_ID || '12345678',
    firstName: import.meta.env.VITE_DEV_USER_FIRST_NAME || 'Test',
    lastName: import.meta.env.VITE_DEV_USER_LAST_NAME || 'User',
    username: import.meta.env.VITE_DEV_USER_USERNAME || 'test_user'
  },
  devSecretKey: import.meta.env.VITE_DEV_SECRET_KEY || 'dev_secret_only'
} 