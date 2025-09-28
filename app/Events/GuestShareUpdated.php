<?php

namespace App\Events;

use App\Traits\HashToken;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuestShareUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels, HashToken;

    public string $tenant;
    public int $guestShareId;
    public string $token;

    public function __construct(string $tenant, int $guestShareId, string $token)
    {
        $this->tenant = $tenant;
        $this->guestShareId = $guestShareId;
        $this->token = $this->hashToken($token);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("guest-share.updated.{$this->tenant}.{$this->guestShareId}"),
        ];
    }
}
