<?php

namespace app\services;

use app\exception\TelegramAuthException;
use Exception;
use Monolog\Logger;
use support\Log;
use support\Request;

class TelegramAuthService
{
    /**
     * @var Logger Логгер для записи событий авторизации
     */
    protected $logger;
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logger = Log::channel('telegram_auth');
    }
    
    /**
     * Логирует сообщение в канал telegram_auth, если включено в конфигурации
     * 
     * @param string $level Уровень логирования (info, error, debug, warning и т.д.)
     * @param string $message Сообщение для логирования
     * @param array $context Дополнительный контекст
     */
    private function log($level, $message, $context = [])
    {
        if (env('TELEGRAM_AUTH_LOGGING', true)) {
            $this->logger->$level($message, $context);
        }
    }
    
    /**
     * Проверяет данные авторизации Telegram и возвращает telegram_id
     */
    public function validateAndGetTelegramId(Request $request)
    {
        $initData = $request->header('X-Telegram-Init-Data');
        
        // РАСШИРЕННОЕ ЛОГИРОВАНИЕ - начало отладки проблемы
        $this->log('info', '======= НАЧАЛО ПРОВЕРКИ ЗАПРОСА =======');
        $this->log('info', 'URL запроса: ' . $request->method() . ' ' . $request->path());
        $this->log('info', 'Полный URL: ' . $request->url());
        $this->log('info', 'Query параметры: ' . json_encode($request->all()));
        $this->log('info', 'IP клиента: ' . $request->getRealIp());
        $this->log('info', 'User-Agent: ' . $request->header('User-Agent'));
        
        // Логируем все заголовки
        $allHeaders = $request->header();
        $this->log('info', 'ВСЕ ЗАГОЛОВКИ ЗАПРОСА: ' . json_encode($allHeaders));
        
        // Смотрим, присутствует ли X-Dev-User-Override
        $hasDevOverride = isset($allHeaders['X-Dev-User-Override']) || 
                          isset($allHeaders['x-dev-user-override']);
        $this->log('info', 'Заголовок X-Dev-User-Override присутствует: ' . 
                          ($hasDevOverride ? 'ДА' : 'НЕТ'));
        
        // Логируем initData
        $this->log('info', 'X-Telegram-Init-Data длина: ' . (strlen($initData ?? '') ?: 'отсутствует'));
        if (!empty($initData)) {
            // Показываем первые 100 символов для анализа
            $this->log('info', 'X-Telegram-Init-Data (начало): ' . substr($initData, 0, 100) . '...');
            
            // Разбираем initData для анализа
            parse_str($initData, $parsedData);
            $this->log('info', 'Разобранные данные: ' . json_encode($parsedData));
            
            // Проверяем наличие элементов, которые указывают на источник
            $this->log('info', 'Содержит hash: ' . (isset($parsedData['hash']) ? 'ДА' : 'НЕТ'));
            $this->log('info', 'Содержит user: ' . (isset($parsedData['user']) ? 'ДА' : 'НЕТ'));
            $this->log('info', 'Содержит auth_date: ' . (isset($parsedData['auth_date']) ? 'ДА' : 'НЕТ'));
            
            if (isset($parsedData['user'])) {
                $userData = json_decode($parsedData['user'], true);
                $this->log('info', 'ID пользователя из initData: ' . ($userData['id'] ?? 'отсутствует'));
            }
        }
        // РАСШИРЕННОЕ ЛОГИРОВАНИЕ - конец отладки
        
        if (empty($initData)) {
            $this->log('error', 'Отсутствует заголовок X-Telegram-Init-Data');
            throw new TelegramAuthException(
                TelegramAuthException::MSG_MISSING_DATA,
                TelegramAuthException::ERROR_MISSING_DATA,
                ['request_path' => $request->path()]
            );
        }
        
        $this->log('info', 'Получен заголовок X-Telegram-Init-Data: ' . $initData);
        
        $headers = $request->header();
        $remoteAddr = $request->getRealIp();
        
        $this->log('info', 'Все заголовки запроса: ' . json_encode($headers));
        
        // Проверяем, активирован ли режим подмены пользователя
        $isDev = config('app.env') === 'development';
        $overrideEnabled = config('app.dev_user_override_enabled') === true;
        
        // Проверяем в разных форматах (с учетом регистра)
        $hasHeader = isset($headers['X-Dev-User-Override']) && $headers['X-Dev-User-Override'] === 'true';
        if (!$hasHeader) {
            $hasHeader = isset($headers['x-dev-user-override']) && $headers['x-dev-user-override'] === 'true';
        }
        
        // Дополнительное логирование для отладки подмены
        $this->log('info', 'ПРОВЕРКА РЕЖИМА ПОДМЕНЫ:', [
            'isDev' => $isDev ? 'true' : 'false',
            'overrideEnabled' => $overrideEnabled ? 'true' : 'false', 
            'hasHeader' => $hasHeader ? 'true' : 'false',
            'env' => config('app.env'),
            'override_config' => config('app.dev_user_override_enabled')
        ]);
        
        // Проверяем все ключи на наличие X-Dev-User-Override (с учетом любого регистра)
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'x-dev-user-override') {
                $this->log('info', 'НАЙДЕН ЗАГОЛОВОК подмены с ключом [' . $key . '] = ' . $value);
            }
        }
        
        // Проверка через специальную функцию
        $useDevOverride = $this->isDevUserOverrideActive($headers, $remoteAddr);
        $this->log('info', 'РЕЗУЛЬТАТ РЕШЕНИЯ: ' . ($useDevOverride ? 'ИСПОЛЬЗУЕМ ПОДМЕНУ' : 'ИСПОЛЬЗУЕМ СТАНДАРТНУЮ ПРОВЕРКУ'));
        
        if ($useDevOverride) {
            // Вызываем специальную функцию для режима разработки
            $this->log('info', 'ВЫБРАН ПУТЬ: Режим разработки с подменой пользователя');
            $telegramData = $this->handleDevUserOverride($initData);
        } else {
            // Используем стандартную проверку для обычного режима
            $this->log('info', 'ВЫБРАН ПУТЬ: Стандартная проверка Telegram данных');
            $telegramData = $this->verifyRealTelegramData($initData);
        }
        
        // После проверки подлинности данных, извлекаем telegram_id
        $telegramId = $telegramData['user']['id'] ?? null;
        
        if (empty($telegramId)) {
            $this->log('error', 'Отсутствует telegram_id в проверенных данных');
            throw new TelegramAuthException(
                TelegramAuthException::MSG_MISSING_TELEGRAM_ID,
                TelegramAuthException::ERROR_MISSING_TELEGRAM_ID,
                ['auth_data' => json_encode($telegramData)]
            );
        }
        
        $this->log('info', 'УСПЕШНАЯ АВТОРИЗАЦИЯ. Используемый ID: ' . $telegramId);
        $this->log('info', '======= КОНЕЦ ПРОВЕРКИ ЗАПРОСА =======');
        
        return $telegramId;
    }
    
    /**
     * Проверка, активен ли режим подмены пользователя
     */
    protected function isDevUserOverrideActive($headers, $remoteAddr)
    {
        $this->log('info', '=== НАЧАЛО ПРОВЕРКИ РЕЖИМА ПОДМЕНЫ ===');
        
        // ПРОВЕРКА 1: Приложение должно быть в режиме разработки
        $isDev = config('app.env') === 'development';
        $this->log('info', '1. Режим разработки: ' . ($isDev ? 'ВКЛЮЧЕН' : 'ВЫКЛЮЧЕН'));
        $this->log('info', '   Значение app.env: ' . config('app.env'));
        
        // ПРОВЕРКА 2: Режим подмены должен быть явно включен в конфигурации
        $overrideEnabled = config('app.dev_user_override_enabled') === true;
        $this->log('info', '2. Подмена включена в конфиге: ' . ($overrideEnabled ? 'ДА' : 'НЕТ'));
        $this->log('info', '   Значение app.dev_user_override_enabled: ' . var_export(config('app.dev_user_override_enabled'), true));
        
        // ПРОВЕРКА 3: В запросе должен присутствовать специальный заголовок
        $hasHeader = false;
        
        // Добавляем детальное логирование для отладки
        $this->log('info', '3. Проверка заголовка X-Dev-User-Override:');
        $this->log('info', '   Все заголовки: ' . json_encode(array_keys($headers)));
        
        // Проверяем заголовок в разных вариантах написания (с учетом регистра)
        if (isset($headers['X-Dev-User-Override'])) {
            $this->log('info', '   Найден X-Dev-User-Override = ' . $headers['X-Dev-User-Override']);
            if ($headers['X-Dev-User-Override'] === 'true') {
                $hasHeader = true;
                $this->log('info', '   Значение соответствует "true"');
            } else {
                $this->log('info', '   Значение НЕ соответствует "true"');
            }
        } 
        
        if (isset($headers['x-dev-user-override'])) {
            $this->log('info', '   Найден x-dev-user-override = ' . $headers['x-dev-user-override']);
            if ($headers['x-dev-user-override'] === 'true') {
                $hasHeader = true;
                $this->log('info', '   Значение соответствует "true"');
            } else {
                $this->log('info', '   Значение НЕ соответствует "true"');
            }
        }
        
        // Проверяем все ключи без учета регистра
        $foundKeys = [];
        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);
            if ($lowerKey === 'x-dev-user-override') {
                $foundKeys[] = $key;
                $this->log('info', '   Найден заголовок в формате: [' . $key . '] = ' . $value);
                if ($value === 'true') {
                    $hasHeader = true;
                    $this->log('info', '   Значение соответствует "true"');
                } else {
                    $this->log('info', '   Значение НЕ соответствует "true"');
                }
            }
        }
        
        if (empty($foundKeys)) {
            $this->log('info', '   Заголовок X-Dev-User-Override НЕ найден в запросе');
        }
        
        // Объединяем все условия - все должны быть true
        $isActive = $isDev && $overrideEnabled && $hasHeader;
        
        $this->log('info', 'ИТОГОВЫЙ РЕЗУЛЬТАТ:', [
            'isDev' => $isDev ? 'ДА' : 'НЕТ',
            'overrideEnabled' => $overrideEnabled ? 'ДА' : 'НЕТ',
            'hasHeader' => $hasHeader ? 'ДА' : 'НЕТ',
            'isActive' => $isActive ? 'АКТИВИРОВАНО' : 'НЕ АКТИВИРОВАНО'
        ]);
        
        $this->log('info', '=== КОНЕЦ ПРОВЕРКИ РЕЖИМА ПОДМЕНЫ ===');
        
        return $isActive;
    }
    
    /**
     * Обработка подмены пользователя в режиме разработки
     */
    protected function handleDevUserOverride($initData)
    {
        $this->log('info', 'Обработка запроса в режиме подмены пользователя: ' . $initData);
        
        // Проверяем наличие DEV_SECRET_KEY
        $devSecret = config('app.dev_secret_key');
        if (!$devSecret) {
            $this->log('error', 'DEV_SECRET_KEY не задан в конфигурации');
            throw new TelegramAuthException(
                TelegramAuthException::MSG_CONFIGURATION,
                TelegramAuthException::ERROR_CONFIGURATION,
                ['config_key' => 'app.dev_secret_key']
            );
        }
        
        $this->log('info', 'DEV_SECRET_KEY: ' . $devSecret);
        
        // Разбор initData как обычно
        parse_str($initData, $auth_data);
        $this->log('info', 'Разобранные данные: ' . json_encode($auth_data));
        
        // Проверка подписи данных в режиме разработки
        $check_hash = $auth_data['hash'] ?? '';
        if (empty($check_hash)) {
            $this->log('error', 'В initData отсутствует hash');
            throw new TelegramAuthException(
                TelegramAuthException::MSG_MISSING_DATA,
                TelegramAuthException::ERROR_MISSING_DATA,
                ['field' => 'hash']
            );
        }
        
        // Удаляем hash из данных для проверки
        $hash_data = $auth_data;
        unset($hash_data['hash']);
        
        // Создаем строку для хеширования
        $data_check_arr = [];
        foreach ($hash_data as $key => $value) {
            $data_check_arr[] = $key . '=' . $value;
        }
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);
        
        $this->log('info', 'Строка данных для хеширования: ' . $data_check_string);
        
        // Вычисляем хеш с DEV_SECRET_KEY
        $hash = hash_hmac('sha256', $data_check_string, $devSecret);
        
        $this->log('info', 'Вычисленный хеш: ' . $hash);
        $this->log('info', 'Полученный хеш: ' . $check_hash);
        
        // Сравниваем хеши
        $is_valid = (strcmp($hash, $check_hash) === 0);
        
        $this->log('info', 'Результат проверки хеша в режиме разработки: ' . ($is_valid ? 'УСПЕШНО' : 'ОШИБКА'));
        
        if (!$is_valid) {
            $this->log('error', 'Неверная подпись данных в режиме разработки');
            throw new TelegramAuthException(
                TelegramAuthException::MSG_INVALID_SIGNATURE,
                TelegramAuthException::ERROR_INVALID_SIGNATURE,
                ['dev_mode' => true]
            );
        }
        
        // Данные пользователя должны быть в initData, как и в стандартном случае
        if (empty($auth_data['user'])) {
            $this->log('error', 'В initData отсутствуют данные пользователя');
            throw new TelegramAuthException(
                TelegramAuthException::MSG_MISSING_DATA,
                TelegramAuthException::ERROR_MISSING_DATA,
                ['field' => 'user']
            );
        }
        
        // Декодируем данные пользователя
        $auth_data['user'] = json_decode($auth_data['user'], true);
        
        // Проверяем наличие ID пользователя
        if (empty($auth_data['user']['id'])) {
            $this->log('error', 'В данных пользователя отсутствует ID');
            throw new TelegramAuthException(
                TelegramAuthException::MSG_MISSING_TELEGRAM_ID,
                TelegramAuthException::ERROR_MISSING_TELEGRAM_ID,
                ['user_data' => json_encode($auth_data['user'])]
            );
        }
        
        $this->log('info', 'Режим подмены пользователя: ID ' . $auth_data['user']['id']);
        
        return $auth_data;
    }
    
    /**
     * Валидация данных от Telegram.
     * 
     * @param string $initData - данные, которые прислал Telegram
     * @return array|false - массив данных пользователя или false при ошибке
     */
    private function verifyRealTelegramData(string $initData): array|false
    {
        $this->log('info', "НАЧАЛО ПРОЦЕССА ВАЛИДАЦИИ TELEGRAM DATA ======================================================");
        $this->log('info', "Полученные данные initData (полностью): " . $initData);
        
        // ШАГ 1: Парсим входные данные
        $this->log('info', "ШАГ 1: Парсим входные данные");
        parse_str($initData, $data);
        $this->log('info', "Разобранные данные: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        
        if (!isset($data['hash']) || !isset($data['auth_date'])) {
            $this->log('error', "Ошибка: Отсутствуют обязательные поля 'hash' или 'auth_date'");
            return false;
        }

        // ШАГ 2: Сохраняем полученный хеш отдельно и удаляем его из массива данных для проверки
        $this->log('info', "ШАГ 2: Извлекаем полученный хеш");
        $receivedHash = $data['hash'];
        $originalData = $data; // Сохраняем оригинальные данные для возврата
        unset($data['hash']);
        $this->log('info', "Полученный хеш: " . $receivedHash);
        $this->log('info', "Длина полученного хеша: " . strlen($receivedHash));

        // ШАГ 3: Проверяем валидность данных по времени (опционально)
        $this->log('info', "ШАГ 3: Проверяем валидность данных по времени");
        $authDate = $data['auth_date'];
        $currentTime = time();
        $maxAge = config('app.telegram_auth_max_age', 86400); // По умолчанию 24 часа
        
        if (($currentTime - $authDate) > $maxAge) {
            $this->log('error', "Ошибка: Данные устарели. Текущее время: {$currentTime}, Время авторизации: {$authDate}, Максимальный возраст: {$maxAge}");
            return false;
        }

        // ШАГ 4: Создаем секретный ключ из токена бота
        $this->log('info', "ШАГ 4: Создаем секретный ключ");
        $botToken = config('app.telegram_bot_token');
        $this->log('info', "Используемый токен бота: {$botToken}");
        $this->log('info', "Длина токена бота: " . strlen($botToken));
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $secretKeyHex = bin2hex($secretKey);
        $this->log('info', "Сгенерированный секретный ключ (hex): " . $secretKeyHex);
        $this->log('info', "Длина секретного ключа: " . strlen($secretKey) . " байт, " . strlen($secretKeyHex) . " символов в hex");

        // ШАГ 5: Формируем проверочную строку согласно документации Telegram
        // https://docs.telegram-mini-apps.com/platform/init-data
        $this->log('info', "ШАГ 5: Формируем проверочную строку");
        ksort($data); // Сортируем ключи в алфавитном порядке (важно для корректной проверки)
        $dataCheckParts = [];
        foreach ($data as $key => $value) {
            $dataCheckParts[] = "{$key}={$value}";
            $this->log('info', "Поле: {$key} = {$value}");
        }
        $dataCheckString = implode("\n", $dataCheckParts); // Соединяем через \n как указано в документации
        $this->log('info', "Проверочная строка (data_check_string): " . $dataCheckString);
        
        // ШАГ 6: Вычисляем хеш по стандартному алгоритму
        $this->log('info', "ШАГ 6: Вычисляем хеш по стандартному алгоритму");
        $computedHash = hash_hmac('sha256', $dataCheckString, $secretKey);
        $this->log('info', "Вычисленный хеш: " . $computedHash);
        $this->log('info', "Полученный хеш:  " . $receivedHash);
        
        $is_valid = hash_equals($computedHash, $receivedHash);
        $this->log('info', "Результат проверки хеша: " . ($is_valid ? 'УСПЕХ' : 'НЕУДАЧА'));

        // Проверка режима разработки
        $env = config('app.env', 'production');
        if (!$is_valid && $env === 'development') {
            $this->log('warning', "ВНИМАНИЕ! Временно пропускаем проверку хеша в режиме разработки.");
            $this->log('warning', "ИСПОЛЬЗУЙТЕ ЭТО ТОЛЬКО ДЛЯ ОТЛАДКИ!");
            $is_valid = true;
        }

        $this->log('info', "ИТОГОВЫЙ РЕЗУЛЬТАТ ПРОВЕРКИ: " . ($is_valid ? 'УСПЕШНО' : 'НЕУДАЧА'));
        
        if (!$is_valid) {
            $this->log('error', "Ошибка валидации данных: неверная подпись");
            $this->log('info', "КОНЕЦ ПРОЦЕССА ВАЛИДАЦИИ TELEGRAM DATA ========================================================");
            return false;
        }
        
        // Обработка успешной валидации - подготовка данных для возврата
        $this->log('info', "Подготовка данных пользователя");
        
        // Декодируем данные пользователя JSON, если они есть
        if (isset($originalData['user'])) {
            $originalData['user'] = json_decode($originalData['user'], true);
            $this->log('info', "Данные пользователя: " . json_encode($originalData['user'], JSON_UNESCAPED_UNICODE));
        } else {
            $this->log('error', "Ошибка: В данных отсутствует поле 'user'");
            $this->log('info', "КОНЕЦ ПРОЦЕССА ВАЛИДАЦИИ TELEGRAM DATA ========================================================");
            return false;
        }
        
        $this->log('info', "КОНЕЦ ПРОЦЕССА ВАЛИДАЦИИ TELEGRAM DATA ========================================================");
        return $originalData;
    }

    /**
     * Добавляет заголовок для режима разработки, если условия соблюдены
     */
    protected function addDevHeaderToRequest(Request $request)
    {
        $env = config('app.env', 'production');
        $overrideEnabled = config('app.dev_user_override_enabled') === true;
        
        // Добавляем заголовок только в режиме разработки когда это разрешено
        if ($env === 'development' && $overrideEnabled) {
            // Добавляем логирование для проверки initData
            $initData = $request->header('X-Telegram-Init-Data');
            $this->log('info', "Проверяем необходимость добавления заголовка разработки");
            $this->log('info', "Текущие заголовки: " . json_encode($request->header()));
            
            if (!empty($initData)) {
                // Добавляем заголовок для режима разработки
                $request->withHeader('X-Dev-User-Override', 'true');
                $this->log('info', "Добавлен заголовок X-Dev-User-Override для режима разработки");
            }
        }
        
        return $request;
    }
} 