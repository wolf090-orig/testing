<?php

namespace app\model;

use support\Model;

class LotteryNumber extends Model
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
    protected $table = 'lottery_numbers';
    
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'lottery_type',
        'lottery_name',
        'start_date',
        'end_date',
        'draw_date',
        'drawn_at',
        'results_exported_at',
        'is_active',
        'calculated_winners_count',
        'total_participants',
        'total_tickets_sold'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'draw_date' => 'datetime',
        'drawn_at' => 'datetime',
        'results_exported_at' => 'datetime',
        'is_active' => 'boolean',
        'calculated_winners_count' => 'integer',
        'total_participants' => 'integer',
        'total_tickets_sold' => 'integer'
    ];

    /**
     * Связь с выигрышными билетами
     */
    public function winnerTickets()
    {
        return $this->hasMany(Ticket::class, 'lottery_id', 'id')->where('is_winner', true);
    }

    /**
     * Проверка, разыграна ли лотерея
     */
    public function isDrawn(): bool
    {
        return !is_null($this->drawn_at);
    }

    /**
     * Scope для получения разыгранных лотерей
     */
    public function scopeDrawn($query)
    {
        return $query->whereNotNull('drawn_at');
    }

    /**
     * Scope для получения неразыгранных лотерей
     */
    public function scopeNotDrawn($query)
    {
        return $query->whereNull('drawn_at');
    }
} 