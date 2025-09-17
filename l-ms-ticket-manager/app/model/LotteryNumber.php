<?php

namespace app\model;

use Carbon\Carbon;
use support\Model;

class LotteryNumber extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    public $typeMap = [];
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lottery_numbers';
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    protected $fillable = [
        'country_id',
        'lottery_name',
        'lottery_type_id',
        'start_date',
        'end_date',
        'draw_date',
        'is_drawn',
        'is_active',
        'is_prize_config_locked',
        'is_tickets_generation_completed',
        'prize_configuration_id',
        'calculated_winners_count',
        'schedule_exported_at',
        'winners_config_exported_at'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'draw_date' => 'datetime',
        'is_drawn' => 'boolean',
        'is_active' => 'boolean',
        'is_prize_config_locked' => 'boolean',
        'is_tickets_generation_completed' => 'boolean',
        'calculated_winners_count' => 'integer',
        'schedule_exported_at' => 'datetime',
        'winners_config_exported_at' => 'datetime'
    ];

    public static function getLotteryPartitionModel(?string $tableName = null): LotteryNumber
    {
        $lotteryT = new LotteryNumber();
        $lotteryT->setTable($tableName);
        return $lotteryT;
    }

    public function setTable($table)
    {
        return parent::setTable($table);
    }

    public function price()
    {
        return $this->hasOne(LotteryPrice::class, 'lottery_type_id', 'lottery_type_id');
    }

    public function type()
    {
        return $this->belongsTo(LotteryTypes::class, 'lottery_type_id');
    }

    public function lotteryType()
    {
        return $this->belongsTo(LotteryTypes::class, 'lottery_type_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function prizeConfiguration()
    {
        return $this->belongsTo(PrizeConfiguration::class, 'prize_configuration_id');
    }

    public function ticketPurchases()
    {
        return $this->hasMany(UserTicketPurchase::class, 'lottery_id');
    }

    public function getTablePartitionName(Carbon $day = null): array
    {
        $typeMap = $this->lotteryTypeMapIdType();
        $countryCode = strtolower($this->country?->code ?? 'ru');

        // Для всех типов лотерей используем единое партиционирование по ID лотереи
        $partition_suffix = "_{$countryCode}_lottery_{$this->id}";

        $parent_table = $typeMap[$this->lottery_type_id] . "_tickets";
        return [
            $parent_table,
            $parent_table . $partition_suffix
        ];
    }

    public function lotteryTypeMapIdType(): array
    {
        if (!empty($this->typeMap)) {
            return $this->typeMap;
        }
        $lotteryTypes = LotteryTypes::all();
        $typeMap = [];
        foreach ($lotteryTypes as $lotteryType) {
            $typeMap[$lotteryType->id] = $lotteryType->name;
        }
        $this->typeMap = $typeMap;
        return $typeMap;
    }

    public function getDTO(): array
    {
        return [
            "id" => $this->id,
            "name" => $this->lottery_name,
            "country_id" => $this->country_id,
            "country_name" => $this->country?->name,
            "type_id" => $this->lottery_type_id,
            "type_name" => $this->typeIdtoName($this->lottery_type_id),
            "start_date" => $this->start_date,
            "end_date" => $this->end_date,
            "draw_date" => $this->draw_date,
            "is_drawn" => $this->is_drawn,
            "is_active" => $this->is_active,
            "price" => $this->getPrice(),
        ];
    }

    public function typeIdtoName(int $typeId): string
    {
        $types = LotteryTypes::all()->mapWithKeys(function (LotteryTypes $type) use ($typeId) {
            return [$type->id => $type->name];
        });

        return $types[$typeId];
    }

    public function getPrice(): float
    {
        return floatval($this->price->price);
    }

    /**
     * Генерирует уникальные номера билетов с учетом страны и ID лотереи
     *
     * @param int $startNumber
     * @param int $count
     * @return array
     */
    public function generateCountrySpecificTicketNumbers(int $startNumber, int $count): array
    {
        $countryCode = strtoupper($this->country?->code ?? 'RU');
        $tickets = [];

        for ($i = 0; $i < $count; $i++) {
            $ticketNumber = $startNumber + $i;
            // Формат: RU0000001_L2 (где L2 = lottery_id для глобальной уникальности)
            $formattedNumber = $countryCode . sprintf('%07d', $ticketNumber) . '_L' . $this->id;

            $tickets[] = [
                'id' => $ticketNumber,
                'ticket_number' => $formattedNumber,
                'lottery_id' => $this->id,
                'lottery_type_id' => $this->lottery_type_id
            ];
        }

        return $tickets;
    }
}
