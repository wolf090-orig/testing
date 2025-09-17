<?php

/**
 * Коды ошибок FPGate
 * 
 * Используется для обработки и логирования ошибок от FPGate
 * с человекочитаемыми описаниями
 */

return [

    // Общие ошибки системы
    'SYSTEM_ERROR' => 'Системная ошибка',
    'INVALID_REQUEST' => 'Неверный формат запроса',
    'AUTHENTICATION_FAILED' => 'Ошибка аутентификации',
    'AUTHORIZATION_FAILED' => 'Ошибка авторизации',
    'INVALID_SIGNATURE' => 'Неверная подпись запроса',
    'TIMEOUT' => 'Превышено время ожидания',

    // Ошибки валидации
    'INVALID_AMOUNT' => 'Некорректная сумма операции',
    'INVALID_CURRENCY' => 'Неподдерживаемая валюта',
    'INVALID_PAYMENT_METHOD' => 'Недоступный метод платежа',
    'INVALID_CARD_NUMBER' => 'Некорректный номер карты',
    'INVALID_CARD_EXPIRY' => 'Некорректная дата истечения карты',
    'INVALID_CVV' => 'Некорректный CVV код',
    'INVALID_BANK_ID' => 'Неверный ID банка',
    'INVALID_PHONE_NUMBER' => 'Некорректный номер телефона',

    // Ошибки пополнения (PayIn)
    'CARD_DECLINED' => 'Операция отклонена банком',
    'INSUFFICIENT_FUNDS' => 'Недостаточно средств на карте',
    'CARD_EXPIRED' => 'Карта просрочена',
    'CARD_BLOCKED' => 'Карта заблокирована',
    'CARD_NOT_SUPPORTED' => 'Карта не поддерживается',
    'LIMITS_EXCEEDED' => 'Превышены лимиты операций',
    'FREQUENT_OPERATIONS' => 'Слишком частые операции',
    'SUSPICIOUS_ACTIVITY' => 'Подозрительная активность заблокирована',

    // Ошибки вывода (PayOut)
    'ACCOUNT_NOT_FOUND' => 'Счет получателя не найден',
    'ACCOUNT_BLOCKED' => 'Счет получателя заблокирован',
    'BANK_NOT_AVAILABLE' => 'Банк временно недоступен',
    'TRANSFER_FAILED' => 'Перевод не удался',
    'RECIPIENT_DECLINED' => 'Получатель отклонил перевод',
    'DAILY_LIMIT_EXCEEDED' => 'Превышен дневной лимит переводов',
    'MONTHLY_LIMIT_EXCEEDED' => 'Превышен месячный лимит переводов',

    // Ошибки СБП (Система быстрых платежей)
    'SBP_SERVICE_UNAVAILABLE' => 'Сервис СБП временно недоступен',
    'SBP_BANK_UNAVAILABLE' => 'Банк получателя недоступен в СБП',
    'SBP_PHONE_NOT_REGISTERED' => 'Номер телефона не зарегистрирован в СБП',
    'SBP_TRANSFER_DECLINED' => 'Перевод по СБП отклонен',
    'SBP_LIMIT_EXCEEDED' => 'Превышен лимит переводов СБП',

    // Ошибки сети и технические
    'CONNECTION_ERROR' => 'Ошибка соединения с процессинговым центром',
    'GATEWAY_TIMEOUT' => 'Таймаут шлюза',
    'SERVICE_MAINTENANCE' => 'Техническое обслуживание сервиса',
    'RATE_LIMIT_EXCEEDED' => 'Превышен лимит запросов в секунду',

    // Ошибки статуса операций
    'OPERATION_NOT_FOUND' => 'Операция не найдена',
    'OPERATION_ALREADY_PROCESSED' => 'Операция уже обработана',
    'OPERATION_EXPIRED' => 'Срок действия операции истек',
    'OPERATION_CANCELLED' => 'Операция отменена',

    // Ошибки конфигурации мерчанта
    'MERCHANT_NOT_FOUND' => 'Мерчант не найден',
    'MERCHANT_BLOCKED' => 'Мерчант заблокирован',
    'MERCHANT_LIMITS_EXCEEDED' => 'Превышены лимиты мерчанта',
    'PAYMENT_METHOD_DISABLED' => 'Метод платежа отключен для мерчанта',
    'CURRENCY_NOT_SUPPORTED' => 'Валюта не поддерживается мерчантом',

    // Ошибки безопасности
    'FRAUD_DETECTED' => 'Обнаружена мошенническая активность',
    'IP_BLOCKED' => 'IP-адрес заблокирован',
    'TOO_MANY_ATTEMPTS' => 'Слишком много попыток операции',
    'SECURITY_VIOLATION' => 'Нарушение политики безопасности',

    // Специфичные ошибки
    'DUPLICATE_TRANSACTION' => 'Дублирование транзакции',
    'CALLBACK_ERROR' => 'Ошибка обработки callback',
    'WEBHOOK_FAILED' => 'Ошибка доставки webhook',
    'INVALID_REDIRECT_URL' => 'Некорректный URL для перенаправления',

];
