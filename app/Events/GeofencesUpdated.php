<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GeofencesUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenant;
    public string $region;

    public function __construct(string $tenant, string $region)
    {
        $this->tenant = $tenant;
        $this->region = $region;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("geofences.updated.{$this->tenant}.{$this->region}"),
        ];
    }
}
