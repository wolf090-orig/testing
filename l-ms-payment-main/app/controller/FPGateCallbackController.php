<?php

declare(strict_types=1);

namespace app\controller;

use app\services\CallbackService;
use app\validations\FPGateCallbackRequest;
use support\Log;
use support\Request;
use support\Response;
use Exception;

/**
 * Контроллер для обработки callback'ов от FPGate платежного шлюза
 */
class FPGateCallbackController
{
    private CallbackService $callbackService;

    public function __construct()
    {
        // Следуем паттерну из ticket-manager
        $this->callbackService = new CallbackService();
    }

    /**
     * Обработка callback от FPGate
     */
    public function handle(Request $request): Response
    {
        // Логируем входящий callback
        Log::channel('payment_callback')->info('FPGate callback received', [
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->getRealIp(),
            'user_agent' => $request->header('user-agent'),
            'content_type' => $request->header('content-type'),
            'body' => $request->all()
        ]);

        try {
            // Валидация callback данных
            $validation = new FPGateCallbackRequest();
            $validatedData = $validation->check($request->all());

            // Парсим order_id чтобы получить internal_order_id
            $callbackOrderId = $validatedData['order_id'];
            $internalOrderId = $this->callbackService->parseInternalOrderId($callbackOrderId);

            Log::channel('payment_callback')->info('Callback order ID parsed', [
                'callback_order_id' => $callbackOrderId,
                'internal_order_id' => $internalOrderId
            ]);

            // Находим транзакцию в нашей БД
            $transaction = $this->callbackService->findTransactionByInternalOrderId($internalOrderId);

            if ($transaction === null) {
                Log::channel('payment_callback')->error('Transaction not found for callback', [
                    'callback_order_id' => $callbackOrderId,
                    'internal_order_id' => $internalOrderId,
                    'transaction_id' => $validatedData['transaction_id']
                ]);

                return new Response(404, [], json_encode([
                    'error' => 'Transaction not found',
                    'internal_order_id' => $internalOrderId
                ]));
            }

            // Проверяем дублирующий callback
            $existingCallback = $this->callbackService->findExistingCallback(
                $validatedData['transaction_id'],
                $callbackOrderId
            );

            if ($existingCallback) {
                return new Response(200, [], json_encode([
                    'status' => 'ok',
                    'message' => 'Callback already processed',
                    'callback_id' => $existingCallback['id']
                ]));
            }

            // Обрабатываем callback через сервис
            // $transaction точно не null после проверки выше, приводим к массиву
            $callbackId = $this->callbackService->processCallback($validatedData, (array) $transaction, $callbackOrderId);

            return new Response(200, [], json_encode([
                'status' => 'ok',
                'message' => 'Callback processed successfully',
                'callback_id' => $callbackId
            ]));

        } catch (Exception $e) {
            Log::channel('payment_callback')->error('Callback processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return new Response(500, [], json_encode([
                'error' => 'Internal server error',
                'message' => 'Failed to process callback'
            ]));
        }
    }
}
