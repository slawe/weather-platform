<?php

namespace App\Console\Commands;

use App\Infrastructure\Messaging\RabbitMq\RabbitMqTopology;
use Illuminate\Console\Command;
use Throwable;

/**
 * Artisan komanda za deklarisanje RabbitMQ topologije processing servisa.
 */
final class SetupRabbitMqTopologyCommand extends Command
{
    protected $signature = 'rabbitmq:setup';

    protected $description = 'Deklariše RabbitMQ exchange, queue, retry queue i DLQ za processing-core.';

    public function __construct(private readonly RabbitMqTopology $topology)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->topology->setup();
            $this->info('RabbitMQ topologija je uspešno deklarisana.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Greška pri deklarisanju RabbitMQ topologije: ' . $e->getMessage());
            report($e);

            return self::FAILURE;
        }
    }
}
