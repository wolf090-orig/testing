<template>
  <div class="welcome-view">
    <div class="welcome-container">
      <!-- Логотип и приветствие -->
      <div class="welcome-header">
        <h1 class="welcome-title">Добро пожаловать в Niyat!</h1>
        <p class="welcome-subtitle">
          Лучшая лотерейная платформа в Узбекистане
        </p>
      </div>

      <!-- Выбор настроек -->
      <div class="settings-card card">
        <div class="card-padding">
          <h3>Настройте приложение</h3>
          <p class="text-secondary">
            Выберите вашу страну и язык для персонализированного опыта
          </p>

          <div class="settings-form">
            <!-- Выбор страны -->
            <div class="form-group">
              <label for="country">Страна:</label>
              <select 
                id="country" 
                v-model="selectedCountry"
                class="form-select"
                :disabled="loading"
              >
                <option value="" disabled>Выберите страну</option>
                <option 
                  v-for="country in countries" 
                  :key="country.code" 
                  :value="country.code"
                >
                  {{ country.name_ru }}
                </option>
              </select>
            </div>

            <!-- Выбор языка -->
            <div class="form-group">
              <label for="language">Язык:</label>
              <select 
                id="language" 
                v-model="selectedLanguage"
                class="form-select"
                :disabled="loading"
              >
                <option value="" disabled>Выберите язык</option>
                <option 
                  v-for="language in languages" 
                  :key="language.code" 
                  :value="language.code"
                >
                  {{ language.name }}
                </option>
              </select>
            </div>

            <!-- Кнопки -->
            <div class="form-actions">
              <button 
                @click="saveSettingsAndProceed"
                :disabled="!canProceed || loading"
                class="btn btn-primary"
              >
                <span v-if="loading">Сохранение...</span>
                <span v-else>Продолжить</span>
              </button>

              <button 
                @click="skipSettings"
                :disabled="loading"
                class="btn btn-secondary"
              >
                Пропустить
              </button>
            </div>
          </div>

          <!-- Сообщения -->
          <div v-if="error" class="error-message">
            {{ error }}
          </div>
        </div>
      </div>

      <!-- Информация о приложении -->
      <div class="app-info">
        <p class="text-secondary text-center">
          Telegram Mini App • Версия 1.0.0
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import api from '@/services/api'

const router = useRouter()
const userStore = useUserStore()

// Reactive data
const selectedCountry = ref('')
const selectedLanguage = ref('')
const countries = ref<any[]>([])
const languages = ref<any[]>([])
const loading = ref(false)
const error = ref('')

// Computed
const canProceed = computed(() => 
  selectedCountry.value && selectedLanguage.value
)

// Methods
async function loadAvailableSettings() {
  try {
    loading.value = true
    const settings = await api.getAvailableSettings()
    countries.value = settings.countries
    languages.value = settings.languages
    
    console.log('✅ Настройки загружены:', settings)
  } catch (err) {
    console.error('❌ Ошибка загрузки настроек:', err)
    error.value = 'Ошибка загрузки настроек'
  } finally {
    loading.value = false
  }
}

async function saveSettingsAndProceed() {
  if (!canProceed.value) return
  
  try {
    loading.value = true
    error.value = ''
    
    const success = await userStore.saveUserSettings(
      selectedCountry.value,
      selectedLanguage.value
    )
    
    if (success) {
      console.log('✅ Настройки сохранены, переходим на главную')
      router.push('/home')
    } else {
      error.value = 'Не удалось сохранить настройки'
    }
  } catch (err) {
    console.error('❌ Ошибка сохранения настроек:', err)
    error.value = 'Ошибка сохранения настроек'
  } finally {
    loading.value = false
  }
}

function skipSettings() {
  console.log('⏭️ Настройки пропущены')
  router.push('/home')
}

// Lifecycle
onMounted(async () => {
  console.log('📱 WelcomeView: инициализация')
  
  // Проверяем, есть ли уже настройки пользователя
  if (userStore.hasUserSettings && userStore.country && userStore.language) {
    console.log('✅ Настройки уже есть, переходим на главную')
    router.push('/home')
    return
  }
  
  // Загружаем доступные настройки
  await loadAvailableSettings()
  
  // Предзаполняем из localStorage если есть
  selectedCountry.value = userStore.getCountryCodeFromStorage()
  selectedLanguage.value = userStore.getLanguageCodeFromStorage()
})
</script>

<style scoped>
.welcome-view {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-md);
  background: linear-gradient(135deg, var(--primary-color) 0%, #1976d2 100%);
}

.welcome-container {
  width: 100%;
  max-width: 400px;
}

.welcome-header {
  text-align: center;
  margin-bottom: var(--spacing-xl);
  color: white;
}

.welcome-title {
  font-size: 28px;
  font-weight: 700;
  margin-bottom: var(--spacing-sm);
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.welcome-subtitle {
  font-size: 16px;
  opacity: 0.9;
  margin: 0;
}

.settings-card {
  margin-bottom: var(--spacing-lg);
}

.settings-form {
  margin-top: var(--spacing-lg);
}

.form-group {
  margin-bottom: var(--spacing-md);
}

.form-group label {
  display: block;
  margin-bottom: var(--spacing-xs);
  font-weight: 500;
  color: var(--text-primary);
}

.form-select {
  width: 100%;
  padding: var(--spacing-md);
  border: 1px solid var(--border-color);
  border-radius: var(--border-radius);
  font-size: 16px;
  background: white;
  color: var(--text-primary);
  appearance: none;
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 16px;
  padding-right: 40px;
}

.form-select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(36, 129, 204, 0.2);
}

.form-actions {
  display: flex;
  gap: var(--spacing-md);
  margin-top: var(--spacing-lg);
}

.form-actions .btn {
  flex: 1;
}

.error-message {
  margin-top: var(--spacing-md);
  padding: var(--spacing-md);
  background: #ffebee;
  color: var(--error-color);
  border-radius: var(--border-radius);
  font-size: 14px;
}

.app-info {
  margin-top: var(--spacing-lg);
}

.app-info p {
  color: rgba(255, 255, 255, 0.8);
}

@media (max-width: 480px) {
  .welcome-view {
    padding: var(--spacing-sm);
  }
  
  .welcome-title {
    font-size: 24px;
  }
  
  .form-actions {
    flex-direction: column;
  }
}
</style> 