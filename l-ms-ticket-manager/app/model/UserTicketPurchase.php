<?php

namespace app\model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Модель покупок билетов пользователями
 * Партиционированная таблица по месяцам (created_at)
 */
class UserTicketPurchase extends Model
{
    protected $table = 'user_ticket_purchases';

    protected $fillable = [
        'user_id',
        'ticket_id',
        'lottery_id',
        'basket_id',
        'purchase_amount',
        'purchase_currency_id',
        'purchased_at'
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'purchase_amount' => 'integer'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Связь с лотереей
     */
    public function lottery(): BelongsTo
    {
        return $this->belongsTo(LotteryNumber::class, 'lottery_id');
    }

    /**
     * Связь с корзиной
     */
    public function basket(): BelongsTo
    {
        return $this->belongsTo(Basket::class, 'basket_id');
    }

    /**
     * Связь с валютой покупки
     */
    public function purchaseCurrency(): BelongsTo
    {
        return $this->belongsTo(PaymentCurrency::class, 'purchase_currency_id');
    }

    /**
     * Получает название партиции для конкретной даты
     */
    public static function getPartitionName(Carbon $date): string
    {
        $year = $date->format('Y');
        $month = $date->format('m');
        return "user_ticket_purchases_{$year}_{$month}";
    }

    /**
     * Получает текущую партицию (для текущего месяца)
     */
    public static function getCurrentPartitionName(): string
    {
        return self::getPartitionName(Carbon::now());
    }

    /**
     * Автоматически создает партицию для текущего месяца если её нет
     */
    public static function ensureCurrentPartitionExists(): bool
    {
        $partitionName = self::getCurrentPartitionName();

        // Проверяем существует ли партиция
        $exists = \support\Db::select("
            SELECT 1 FROM pg_class c 
            JOIN pg_namespace n ON n.oid = c.relnamespace 
            WHERE c.relname = ? AND n.nspname = 'public'
        ", [$partitionName]);

        if (!empty($exists)) {
            return true; // Партиция уже существует
        }

        // Создаем партицию
        $currentDate = Carbon::now()->startOfMonth();
        $nextMonth = $currentDate->copy()->addMonth();
        $startDate = $currentDate->format('Y-m-d');
        $endDate = $nextMonth->format('Y-m-d');

        try {
            \support\Db::statement("
                CREATE TABLE {$partitionName} 
                PARTITION OF user_ticket_purchases 
                FOR VALUES FROM ('{$startDate}') TO ('{$endDate}')
            ");

            \support\Log::info("Автоматически создана партиция {$partitionName}");
            return true;
        } catch (\Exception $e) {
            \support\Log::error("Ошибка создания партиции {$partitionName}: " . $e->getMessage());
            return false;
        }
    }
}
