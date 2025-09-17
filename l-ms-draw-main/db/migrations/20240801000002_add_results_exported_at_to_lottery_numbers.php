<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Добавление поля results_exported_at для отслеживания экспорта результатов розыгрышей
 */
final class AddResultsExportedAtToLotteryNumbers extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('lottery_numbers');
        $table->addColumn('results_exported_at', 'timestamp', [
                'null' => true, 
                'comment' => 'Время экспорта результатов розыгрыша в Kafka'
            ])
            ->addIndex(['results_exported_at'])
            ->addIndex(['drawn_at', 'results_exported_at'])
            ->update();
    }
} 