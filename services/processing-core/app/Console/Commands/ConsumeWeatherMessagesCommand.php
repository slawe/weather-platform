<?php

namespace App\Console\Commands;

use App\Infrastructure\Messaging\RabbitMq\RabbitMqConsumer;
use Illuminate\Console\Command;
use Throwable;

/**
 * Artisan komanda koja pokreće RabbitMQ consume petlju za weather evente.
 */
final class ConsumeWeatherMessagesCommand extends Command
{
    /**
     * Primer:
     * php artisan weather:consume
     */
    protected $signature = 'weather:consume';

    /**
     * Opis komande.
     */
    protected $description = 'Pokreće consume petlju za weather snapshot evente.';

    public function __construct(
        private readonly RabbitMqConsumer $consumer,
    ) {
        parent::__construct();
    }

    /**
     * Pokreće RabbitMQ consume tok.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $this->info('Pokrećem weather consumer...');

            $this->consumer->consume();

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Greška prilikom pokretanja consumer-a: ' . $e->getMessage());

            report($e);

            return self::FAILURE;
        }
    }
}
