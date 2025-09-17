<?php

namespace app\exception;

use Exception;

/**
 * Исключения для процесса авторизации через Telegram
 */
class TelegramAuthException extends Exception
{
    // Константы для типов ошибок
    const ERROR_OUTDATED_DATA = 401;        // Устаревшие данные
    const ERROR_INVALID_SIGNATURE = 403;    // Неверная подпись
    const ERROR_MISSING_DATA = 400;         // Отсутствие обязательных данных
    const ERROR_CONFIGURATION = 500;        // Ошибка конфигурации
    const ERROR_MISSING_TELEGRAM_ID = 400;  // Отсутствует telegram_id
    
    // Константы для сообщений об ошибках
    const MSG_OUTDATED_DATA = 'Data is outdated';
    const MSG_INVALID_SIGNATURE = 'Data is NOT from Telegram';
    const MSG_MISSING_DATA = 'Missing Telegram Init Data';
    const MSG_CONFIGURATION = 'Bot token not configured';
    const MSG_MISSING_TELEGRAM_ID = 'Missing Telegram ID';
    
    /**
     * Дополнительные данные об ошибке
     */
    protected $errorData = [];
    
    /**
     * Конструктор с возможностью добавления дополнительных данных
     * 
     * @param string $message Сообщение об ошибке
     * @param int $code Код ошибки
     * @param array $data Дополнительные данные
     * @param \Throwable|null $previous Предыдущее исключение
     */
    public function __construct(string $message = "", int $code = 0, array $data = [], \Throwable $previous = null)
    {
        $this->errorData = $data;
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Получение дополнительных данных об ошибке
     */
    public function getErrorData(): array
    {
        return $this->errorData;
    }
} 