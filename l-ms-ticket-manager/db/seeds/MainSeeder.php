<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class MainSeeder extends AbstractSeed
{
    /**
     * Сидеры в нужном порядке выполнения
     */
    protected array $seedClasses = [
        CountriesSeeder::class,
        PaymentCurrenciesSeeder::class,
        PaymentStatusSeeder::class,
        LotteryTypesSeeder::class,
        LotteryPricesSeeder::class,
        LotteryTypesSchedulesSeeder::class,
        PrizeConfigurationsSeeder::class,
    ];

    /**
     * Run Method.
     */
    public function run(): void
    {
        foreach ($this->seedClasses as $seedClass) {
            $this->output->writeln("Выполняю {$seedClass}...");

            /** @var AbstractSeed $seeder */
            $seeder = new $seedClass();
            $seeder->setAdapter($this->getAdapter());
            $seeder->setInput($this->getInput());
            $seeder->setOutput($this->getOutput());
            $seeder->run();

            $this->output->writeln("✓ {$seedClass} завершен");
        }
    }
}
