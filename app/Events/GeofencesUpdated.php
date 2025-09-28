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
    public array $geofences;

    public function __construct(string $tenant, string $region, array $geofences)
    {
        $this->tenant = $tenant;
        $this->region = $region;
        $this->geofences = $geofences;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("geofences.updated.{$this->tenant}.{$this->region}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'geofences' => $this->geofences,
        ];
    }
}
