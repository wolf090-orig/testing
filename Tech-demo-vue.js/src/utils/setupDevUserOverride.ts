import CryptoJS from 'crypto-js';
import config from '../config';

// Интерфейс для мокового пользователя
interface MockTelegramUser {
  id: number;
  first_name: string;
  last_name?: string;
  username?: string;
  language_code?: string;
  is_premium?: boolean;
  allows_write_to_pm?: boolean;
}

export function setupDevUserOverride(): void {
  const isDevelopment = import.meta.env.MODE === 'development';
  const devUserOverrideEnabled = config.devUserOverride;

  if (!isDevelopment || !devUserOverrideEnabled) {
    console.log('🔧 Dev user override не активирован');
    return;
  }

  console.log('🔧 Настраиваем Telegram WebApp mock для разработки...');

  // Создаем mock пользователя из конфигурации
  const mockUser: MockTelegramUser = {
    id: parseInt(config.devUser.id),
    first_name: config.devUser.firstName,
    last_name: config.devUser.lastName,
    username: config.devUser.username,
    language_code: 'ru',
    is_premium: true,
    allows_write_to_pm: true
  };

  const secretKey = config.devSecretKey;

  // Создаем подписанные initData для имитации реального Telegram
  const initData = createTelegramInitData(mockUser, secretKey);

  if (typeof window !== 'undefined') {
    // Создаем полный mock объекта Telegram WebApp
    const webAppMock = {
      initData: initData,
      initDataUnsafe: {
        user: mockUser,
        auth_date: Math.floor(Date.now() / 1000),
        hash: 'mock_hash'
      },
      version: '7.2',
      platform: 'web',
      colorScheme: 'light' as const,
      themeParams: {
        link_color: '#2481cc',
        button_color: '#2481cc',
        button_text_color: '#ffffff',
        secondary_bg_color: '#efeff3',
        hint_color: '#999999',
        bg_color: '#ffffff',
        text_color: '#000000'
      },
      isExpanded: false,
      viewportHeight: window.innerHeight,
      viewportStableHeight: window.innerHeight,
      isClosingConfirmationEnabled: false,
      headerColor: '#2481cc',
      backgroundColor: '#ffffff',
      isVersionAtLeast: (version: string) => true,
      setHeaderColor: (color: string) => {
        console.log('📱 Mock: setHeaderColor', color);
      },
      setBackgroundColor: (color: string) => {
        console.log('📱 Mock: setBackgroundColor', color);
      },
      enableClosingConfirmation: () => {
        console.log('📱 Mock: enableClosingConfirmation');
      },
      disableClosingConfirmation: () => {
        console.log('📱 Mock: disableClosingConfirmation');
      },
      expand: () => {
        console.log('📱 Mock: expand');
      },
      close: () => {
        console.log('📱 Mock: close');
      },
      ready: () => {
        console.log('📱 Mock: ready');
      },
      MainButton: {
        text: 'Главная кнопка',
        color: '#2481cc',
        textColor: '#ffffff',
        isVisible: false,
        isActive: true,
        isProgressVisible: false,
        setText: (text: string) => console.log('📱 Mock MainButton: setText', text),
        onClick: (callback: () => void) => console.log('📱 Mock MainButton: onClick'),
        show: () => console.log('📱 Mock MainButton: show'),
        hide: () => console.log('📱 Mock MainButton: hide'),
        enable: () => console.log('📱 Mock MainButton: enable'),
        disable: () => console.log('📱 Mock MainButton: disable'),
        showProgress: () => console.log('📱 Mock MainButton: showProgress'),
        hideProgress: () => console.log('📱 Mock MainButton: hideProgress')
      },
      BackButton: {
        isVisible: false,
        onClick: (callback: () => void) => console.log('📱 Mock BackButton: onClick'),
        show: () => console.log('📱 Mock BackButton: show'),
        hide: () => console.log('📱 Mock BackButton: hide')
      },
      HapticFeedback: {
        impactOccurred: (style: string) => console.log('📱 Mock HapticFeedback: impact', style),
        notificationOccurred: (type: string) => console.log('📱 Mock HapticFeedback: notification', type),
        selectionChanged: () => console.log('📱 Mock HapticFeedback: selectionChanged')
      },
      CloudStorage: {
        setItem: (key: string, value: string, callback?: (error: string | null, success: boolean) => void) => {
          console.log('📱 Mock CloudStorage: setItem', key, value);
          callback?.(null, true);
        },
        getItem: (key: string, callback: (error: string | null, value: string) => void) => {
          console.log('📱 Mock CloudStorage: getItem', key);
          callback(null, '');
        },
        getItems: (keys: string[], callback: (error: string | null, values: Record<string, string>) => void) => {
          console.log('📱 Mock CloudStorage: getItems', keys);
          callback(null, {});
        },
        removeItem: (key: string, callback?: (error: string | null, success: boolean) => void) => {
          console.log('📱 Mock CloudStorage: removeItem', key);
          callback?.(null, true);
        },
        removeItems: (keys: string[], callback?: (error: string | null, success: boolean) => void) => {
          console.log('📱 Mock CloudStorage: removeItems', keys);
          callback?.(null, true);
        },
                 getKeys: (callback: (error: string | null, keys: string[]) => void) => {
           console.log('📱 Mock CloudStorage: getKeys');
           callback(null, []);
         }
       },
       BiometricManager: {
         isInited: false,
         isBiometricAvailable: false,
         biometricType: 'unknown',
         isAccessRequested: false,
         isAccessGranted: false,
         isBiometricTokenSaved: false,
         deviceId: 'mock_device_id',
         init: (callback?: () => void) => {
           console.log('📱 Mock BiometricManager: init');
           callback?.();
         },
         requestAccess: (params: any, callback?: (success: boolean) => void) => {
           console.log('📱 Mock BiometricManager: requestAccess');
           callback?.(false);
         },
         authenticate: (params: any, callback?: (success: boolean, token?: string) => void) => {
           console.log('📱 Mock BiometricManager: authenticate');
           callback?.(false);
         },
         updateBiometricToken: (token: string, callback?: (success: boolean) => void) => {
           console.log('📱 Mock BiometricManager: updateBiometricToken');
           callback?.(false);
         },
         openSettings: () => {
           console.log('📱 Mock BiometricManager: openSettings');
         }
       }
    };

    // Устанавливаем mock объект
    if (!window.Telegram) {
      window.Telegram = {
        WebApp: webAppMock
      };
    } else {
      window.Telegram.WebApp = webAppMock;
    }

    console.log('✅ Telegram WebApp mock настроен для пользователя:', mockUser);
    console.log('🔑 Dev initData создан с подписью HMAC-SHA256');
  }
}

// Создание подписанных initData как в реальном Telegram
function createTelegramInitData(user: MockTelegramUser, secretKey: string): string {
  const authDate = Math.floor(Date.now() / 1000).toString();
  const queryId = generateRandomQueryId();

  // Параметры для подписи
  const params: Record<string, string> = {
    user: JSON.stringify(user),
    auth_date: authDate,
    query_id: queryId
  };

  // Создаем data-check-string как в реальном Telegram
  const dataCheckArr: string[] = [];
  for (const key in params) {
    dataCheckArr.push(`${key}=${params[key]}`);
  }
  dataCheckArr.sort();
  const dataCheckString = dataCheckArr.join('\n');

  // Создаем HMAC-SHA256 подпись
  const hash = CryptoJS.HmacSHA256(dataCheckString, secretKey).toString(CryptoJS.enc.Hex);

  // Формируем финальную URL-encoded строку
  const urlParams = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    urlParams.append(key, value);
  });
  urlParams.append('hash', hash);

  return urlParams.toString();
}

function generateRandomQueryId(): string {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
  let result = '';
  for (let i = 0; i < 32; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return result;
} 