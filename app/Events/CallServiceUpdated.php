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

    public function __construct(string $tenant, string $callServiceGUID)
    {
        $this->tenant = $tenant;
        $this->callServiceGUID = $callServiceGUID;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("call-service.updated.{$this->tenant}.{$this->callServiceGUID}"),
        ];
    }
}
