<?php

namespace app\model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use support\Model;

class Basket extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_baskets';
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    protected $fillable = [
        'start_date',
        'end_date',
        'user_id',
        'cancel_reason_id',
        'payment_status_id',
        'transaction_id',
        'is_payment_lock',
        'payment_lock_count',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_payment_lock' => 'boolean',
        'payment_lock_count' => 'integer',
    ];

    public function addTicket(array $ticketData)
    {
        $this->end_date = Carbon::now()->addMinutes(15);
        $this->save();
        return $this->tickets()->createMany($ticketData);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(UserBasketTicket::class, 'basket_id', 'id');
    }

    public function cancelReason(): BelongsTo
    {
        return $this->belongsTo(CancelReasons::class, 'cancel_reason_id', 'id');
    }

    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id', 'id');
    }

    public function getTotalPrice(): float
    {
        $totalPrice = 0;
        $this->tickets()->with('lottery')
            ->each(function (UserBasketTicket $t) use (&$totalPrice) {
                $totalPrice += $t->getPrice();
            });

        return $totalPrice;
    }

    public function getDTOTickets(): array
    {
        $response = [];
        foreach ($this->tickets()->with(['lottery.type', 'lottery.price'])->get() as $ticket) {
            // Получаем реальный номер билета из партиции таблицы
            $lottery = $ticket->getLottery();
            list(, $lotteryTable) = $lottery->getTablePartitionName();
            $lotteryModel = LotteryNumber::getLotteryPartitionModel($lotteryTable);
            $realTicket = $lotteryModel->where('id', $ticket->ticket_id)->first();

            $response[] = [
                "ticket_id" => $ticket->ticket_id,
                "ticket_number" => $realTicket ? $realTicket->ticket_number : (string)$ticket->ticket_id,
                "ticket_price" => (float)$ticket->lottery->price->price,
                "lottery_type_name" => $ticket->lottery->type->name,
                "lottery_id" => $ticket->lottery_id,
            ];
        }
        return $response;
    }

    public function closeBasket(int $reasonId): void
    {
        // Освобождаем зарезервированные билеты
        $this->tickets()
            ->with('lottery')
            ->get()
            ->each(function (UserBasketTicket $ticket) {
                $lottery = $ticket->getLottery();
                list(, $lotteryTable) = $lottery->getTablePartitionName();
                $lotteryModel = LotteryNumber::getLotteryPartitionModel($lotteryTable);
                $lotteryModel->where('id', $ticket->ticket_id)
                    ->where('lottery_id', $ticket->lottery_id)
                    ->where('is_paid', false)
                    ->update(['is_reserved' => false]);
            });

        $this->cancel_reason_id = $reasonId;
        $this->save();
    }

    public function removeTicket(int $ticketId): void
    {
        // need to delete ticket from table
        $this->tickets()
            ->with('lottery')
            ->where('ticket_id', $ticketId)
            ->get()
            ->each(function (UserBasketTicket $bt) {
                $lottery = $bt->getLottery();
                list(, $lotteryPartitionTableName) = $lottery->getTablePartitionName();
                $lotteryPartitionModel = LotteryNumber::getLotteryPartitionModel($lotteryPartitionTableName);
                $lotteryPartitionModel
                    ->where('id', $bt->ticket_id)
                    ->update([
                        'is_reserved' => false,
                    ]);

                $bt->delete();
            });
    }
}
