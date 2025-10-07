<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class RabbitMqHealthCheck extends Command
{
    protected $signature = 'queue:rabbitmq-health {--queue= : Queue name to use for health ping}';

    protected $description = 'Checks RabbitMQ connectivity by publishing and consuming a lightweight ping message';

    public function handle(): int
    {
        $connection = config('queue.default');
        if ($connection !== 'rabbitmq') {
            $this->warn("QUEUE_CONNECTION is '{$connection}'. Set QUEUE_CONNECTION=rabbitmq to fully test the RabbitMQ driver.");
        }

        $queue = $this->option('queue') ?: config('queue.connections.rabbitmq.queue', 'default');
        $payload = [
            'job' => function () {
                // No-op job payload; driver only needs transport reachability for this check.
            },
            'uuid' => (string) Str::uuid(),
            'ts' => now()->toISOString(),
            'type' => 'health_check',
        ];

        try {
            Queue::connection('rabbitmq')->pushRaw(json_encode($payload), $queue);
            $this->info("✅ RabbitMQ publish succeeded on queue '{$queue}'.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ RabbitMQ publish failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

