<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallServiceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenant;
    public string $callServiceGUID;
    public array $callData;

    public function __construct(string $tenant, string $callServiceGUID, array $callData)
    {
        $this->tenant = $tenant;
        $this->callServiceGUID = $callServiceGUID;
        $this->callData = $callData;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("call-service.updated.{$this->tenant}.{$this->callServiceGUID}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'callData' => $this->callData,
        ];
    }
}
