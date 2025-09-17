<?php

declare(strict_types=1);

namespace app\validations;

use app\classes\Validator\RequestValidation;

/**
 * Валидация callback запросов от FPGate платежного шлюза
 */
class FPGateCallbackRequest extends RequestValidation
{
    protected $rule = [
        // ВСЕ поля обязательные согласно FPGate API
        'token' => 'require|string|max:255',                    // Обязательное
        'transaction_id' => 'require|string|max:255',           // -> external_transaction_id
        'order_id' => 'require|string|max:255',                 // -> order_id  
        'amount' => 'require|array',                            // Обязательное
        'amount.value' => 'require|string|regex:/^\d+\.\d{2}$/', // -> amount
        'amount.currency' => 'require|string|in:RUB,USD,EUR',   // -> currency
        'recalculated' => 'require|string|in:true,false',       // -> recalculated (boolean)
        'timestamp' => 'require|string',                        // -> callback_timestamp
        'status' => 'require|array',                            // Обязательное
        'status.type' => 'require|string|in:created,success,cancelled,processing,error', // -> status_type
        'signature' => 'require|string|size:64'                 // HMAC-SHA256 hex (64 символа)
    ];

    protected $message = [
        'token.require' => 'Token is required',
        'token.string' => 'Token must be a string',
        'token.max' => 'Token must not exceed 255 characters',
        
        'transaction_id.require' => 'Transaction ID is required',
        'transaction_id.string' => 'Transaction ID must be a string',
        'transaction_id.max' => 'Transaction ID must not exceed 255 characters',
        
        'order_id.require' => 'Order ID is required',
        'order_id.string' => 'Order ID must be a string',
        'order_id.max' => 'Order ID must not exceed 255 characters',
        
        'amount.require' => 'Amount is required',
        'amount.array' => 'Amount must be an array',
        
        'amount.value.require' => 'Amount value is required',
        'amount.value.string' => 'Amount value must be a string',
        'amount.value.regex' => 'Amount value must be in format 123.45',
        
        'amount.currency.require' => 'Currency is required',
        'amount.currency.string' => 'Currency must be a string',
        'amount.currency.in' => 'Currency must be one of: RUB, USD, EUR',
        
        'recalculated.require' => 'Recalculated flag is required',
        'recalculated.string' => 'Recalculated must be a string',
        'recalculated.in' => 'Recalculated must be "true" or "false"',
        
        'timestamp.require' => 'Timestamp is required',
        'timestamp.string' => 'Timestamp must be a string',
        
        'status.require' => 'Status is required',
        'status.array' => 'Status must be an array',
        
        'status.type.require' => 'Status type is required',
        'status.type.string' => 'Status type must be a string',
        'status.type.in' => 'Status type must be one of: created, success, cancelled, processing, error',
        
        'signature.require' => 'Signature is required',
        'signature.string' => 'Signature must be a string',
        'signature.size' => 'Signature must be exactly 64 characters (HMAC-SHA256 hex)',
    ];
}


