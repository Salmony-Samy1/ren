<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminDashboardMetricsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $metrics) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('Admin.Dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'admin.metrics.updated';
    }

    public function broadcastWith(): array
    {
        return $this->metrics;
    }
}

