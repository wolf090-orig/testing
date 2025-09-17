import { fileURLToPath, URL } from 'node:url'
import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  // Загружаем переменные окружения
  const env = loadEnv(mode, process.cwd())
  
  return {
    plugins: [vue()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      },
    },
    server: {
      port: 5173,
      host: true,
      cors: true,
      hmr: {
        host: 'localhost'
      },
      proxy: {
        '/api': {
          target: 'http://localhost:8088',
          changeOrigin: true,
        }
      },
      fs: {
        strict: true
      }
    }
  }
})
