<?php

namespace App\Http\Integrations\CAD\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class Geofence extends Request
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    /**
     * Constructor for the request
     */
    public function __construct(
        private int $guestShareId,
        private string $token,
    ) {
    }

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        // /api/guest/{$guestShareId}/call-service/
        return "api/guest/{$this->guestShareId}/geofence/";
    }

    /**
     * Query parameters
     */
    protected function defaultQuery(): array
    {
        // ?token=$token
        return [
            'token' => $this->token,
        ];
    }
}
