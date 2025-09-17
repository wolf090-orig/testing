<?php

namespace app\services;

use app\model\LotterySchedule;
use app\model\Ticket;
use Exception;
use Psr\Log\LoggerInterface;
use support\Log;

class BrokerService
{
    public LoggerInterface $log;
    private DlqService $dlqService;

    public function __construct(LoggerInterface $logger = null)
    {
        if (is_null($logger)) {
            $this->log = Log::channel('default');
        } else {
            $this->log = $logger;
        }
        
        $this->dlqService = new DlqService();
    }

    public function importTickets(int $lotteryId, array $tickets): void
    {
        $lottery = LotterySchedule::where('lottery_id', $lotteryId)->first();
        if (empty($lottery)) {
            $this->log->alert("Импорт билетов в не существующую в базе лотерею (id = $lotteryId)");
            // Кидаем исключение для отправки в DLQ
            throw new Exception("Lottery not found: $lotteryId");
        }

        $this->log->info("Импорт билетов для лотереи $lotteryId", [
            'lottery_name' => $lottery->lottery_name,
            'tickets_count' => count($tickets)
        ]);

        $insert = [];

        foreach ($tickets as $ticket) {
            $insert[] = [
                "ticket_number" => $ticket,
                "lottery_id" => $lotteryId,
                "winner_position" => null,
                "is_winner" => false,
            ];
        }

        if (!empty($insert)) {
            try {
                Ticket::insertOrIgnore($insert);
                $this->log->info("Успешно импортировано билетов: " . count($insert));
            } catch (Exception $e) {
                $this->log->error("Ошибка импорта билетов", [
                    'lottery_id' => $lotteryId,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }
    }

    public function importSchedules(array $schedules): void
    {
        $this->log->info("Импорт расписаний лотерей", ['count' => count($schedules)]);
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($schedules as $s) {
            try {
                $schedule = (array)$s;
                
                // Валидация обязательных полей
                if (!isset($schedule['id']) || !isset($schedule['lottery_name'])) {
                    throw new Exception("Отсутствуют обязательные поля в расписании");
                }
                
                LotterySchedule::updateOrCreate([
                    "lottery_name" => $schedule["lottery_name"],
                    "lottery_id" => $schedule["id"],
                ], [
                    "draw_date" => $schedule["draw_date"] ?? null,
                    "end_date" => $schedule["end_date"] ?? null,
                    "start_date" => $schedule["start_date"] ?? null,
                ]);
                
                $successCount++;
                
            } catch (Exception $e) {
                $errorCount++;
                $this->log->error("Ошибка импорта расписания", [
                    'schedule' => $schedule ?? $s,
                    'error' => $e->getMessage()
                ]);
                // Продолжаем обработку остальных расписаний
            }
        }
        
        $this->log->info("Импорт расписаний завершен", [
            'success' => $successCount,
            'errors' => $errorCount
        ]);
        
        if ($errorCount > 0 && $successCount === 0) {
            throw new Exception("Не удалось импортировать ни одно расписание");
        }
    }

    /**
     * Импорт конфигурации количества победителей
     */
    public function importDrawConfig(array $config): void
    {
        $this->log->info("Импорт конфигурации розыгрыша", $config);
        
        // Валидация обязательных полей
        if (!isset($config['lottery_id']) || !isset($config['winners_count'])) {
            throw new Exception("Отсутствуют обязательные поля в конфигурации розыгрыша");
        }
        
        $lotteryId = $config['lottery_id'];
        $winnersCount = $config['winners_count'];
        
        // Проверяем существование лотереи
        $lottery = LotterySchedule::where('lottery_id', $lotteryId)->first();
        if (!$lottery) {
            throw new Exception("Lottery not found: $lotteryId");
        }
        
        // Сохраняем конфигурацию в модель или кеш
        // TODO: добавить поле winners_count в таблицу lottery_schedules
        // или создать отдельную таблицу lottery_draw_configs
        
        $this->log->info("Конфигурация розыгрыша сохранена", [
            'lottery_id' => $lotteryId,
            'winners_count' => $winnersCount
        ]);
    }

    /**
     * Обработка сообщения с DLQ поддержкой
     */
    public function processTicketsWithDlq(string $topic, array $messageBody, array $headers): bool
    {
        return $this->dlqService->processWithDlq($topic, $messageBody, $headers, function($body, $headers) {
            if (!isset($body['lottery_id']) || !isset($body['tickets'])) {
                throw new Exception("Неверный формат сообщения билетов");
            }
            
            $this->importTickets($body['lottery_id'], $body['tickets']);
        });
    }

    /**
     * Обработка расписаний с DLQ поддержкой
     */
    public function processSchedulesWithDlq(string $topic, array $messageBody, array $headers): bool
    {
        return $this->dlqService->processWithDlq($topic, $messageBody, $headers, function($body, $headers) {
            $schedules = isset($body['lotteries']) ? $body['lotteries'] : $body;
            $this->importSchedules($schedules);
        });
    }

    /**
     * Обработка конфигурации розыгрыша с DLQ поддержкой
     */
    public function processDrawConfigWithDlq(string $topic, array $messageBody, array $headers): bool
    {
        return $this->dlqService->processWithDlq($topic, $messageBody, $headers, function($body, $headers) {
            $this->importDrawConfig($body);
        });
    }
}
