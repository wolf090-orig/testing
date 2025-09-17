<?php

namespace app\controller;

use support\Request;
use support\Response;
use support\Log;

class DebugController
{
    /**
     * Сохраняет логи с фронтенда в файл с улучшенным форматированием
     */
    public function log(Request $request)
    {
        // Создаем логгер с нужным каналом
        $logger = Log::channel('frontend_debug');
        
        // Получаем данные из запроса
        $data = $request->all();
        
        // Извлекаем основные параметры
        $category = $data['category'] ?? 'FRONTEND';
        $message = $data['message'] ?? 'No message';
        $logData = $data['data'] ?? [];
        
        // Добавляем дополнительный контекст запроса
        $requestContext = [
            'URL запроса' => $request->method() . ' ' . $request->path(),
            'Полный URL' => $request->url(),
            'IP клиента' => $request->getRealIp(),
            'User-Agent' => $request->header('User-Agent'),
            'Время запроса' => date('Y-m-d H:i:s')
        ];
        
        // Начало записи лога с разделителем
        $logger->info('======= НАЧАЛО FRONTEND ЛОГА =======');
        $logger->info('КАТЕГОРИЯ: ' . $category);
        $logger->info('СООБЩЕНИЕ: ' . $message);
        
        // Логируем контекст запроса
        $logger->info('КОНТЕКСТ ЗАПРОСА: ' . json_encode($requestContext, JSON_UNESCAPED_UNICODE));
        
        // Логируем переданные данные
        if (!empty($logData)) {
            if (is_string($logData)) {
                $logger->info('ДАННЫЕ: ' . $logData);
            } else {
                $logger->info('ДАННЫЕ: ' . json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
        }
        
        // Завершение записи лога с разделителем
        $logger->info('======= КОНЕЦ FRONTEND ЛОГА =======');
        
        // Возвращаем успешный ответ
        return json(['status' => 'success']);
    }
    
    /**
     * Специальный метод для логирования данных инициализации Telegram WebApp
     */
    public function logTelegramInit(Request $request)
    {
        // Создаем логгер с нужным каналом
        $logger = Log::channel('frontend_debug');
        
        // Получаем данные из запроса
        $requestData = $request->all();
        $initData = $requestData['initData'] ?? '';
        $userAgent = $request->header('User-Agent');
        $clientIp = $request->getRealIp();
        
        // Начало записи лога с разделителем
        $logger->info('======= НАЧАЛО ЛОГА ИНИЦИАЛИЗАЦИИ TELEGRAM =======');
        $logger->info('ВРЕМЯ: ' . date('Y-m-d H:i:s'));
        $logger->info('IP КЛИЕНТА: ' . $clientIp);
        $logger->info('USER-AGENT: ' . $userAgent);
        
        // Логируем все полученные данные
        $logger->info('ПОЛНЫЙ ЗАПРОС: ' . json_encode($requestData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        if (empty($initData)) {
            $logger->error('ОШИБКА: Параметр initData отсутствует в запросе');
            return json(['status' => 'error', 'message' => 'Параметр initData отсутствует']);
        }
        
        // Логируем initData строку целиком
        $logger->info('TELEGRAM INIT DATA (RAW): ' . $initData);
        
        // Разбираем initData на параметры и логируем их отдельно
        parse_str($initData, $parsedData);
        $logger->info('PARSED INIT DATA: ' . json_encode($parsedData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        
        // Логируем отдельно важные параметры
        if (isset($parsedData['hash'])) {
            $logger->info('HASH: ' . $parsedData['hash']);
        } else {
            $logger->warning('ВНИМАНИЕ: Хеш отсутствует в данных');
        }
        
        if (isset($parsedData['auth_date'])) {
            $timestamp = $parsedData['auth_date'];
            $dateTime = date('Y-m-d H:i:s', $timestamp);
            $logger->info('AUTH DATE: ' . $timestamp . ' (' . $dateTime . ')');
            $logger->info('CURRENT TIME: ' . time() . ' (' . date('Y-m-d H:i:s', time()) . ')');
            $logger->info('РАЗНИЦА ВРЕМЕНИ (сек): ' . (time() - $timestamp));
        } else {
            $logger->warning('ВНИМАНИЕ: auth_date отсутствует в данных');
        }
        
        if (isset($parsedData['user'])) {
            $user = json_decode($parsedData['user'], true);
            $logger->info('USER DATA: ' . json_encode($user, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } else {
            $logger->warning('ВНИМАНИЕ: Данные пользователя отсутствуют');
        }
        
        // Завершение записи лога с разделителем
        $logger->info('======= КОНЕЦ ЛОГА ИНИЦИАЛИЗАЦИИ TELEGRAM =======');
        
        // Возвращаем успешный ответ
        return json(['status' => 'success']);
    }
} 