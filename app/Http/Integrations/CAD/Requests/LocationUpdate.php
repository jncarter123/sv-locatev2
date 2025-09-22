<?php

namespace App\Http\Integrations\CAD\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class LocationUpdate extends Request
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::POST;

    /**
     * Constructor for the request
     */
    public function __construct(
        private int $guestShareId,
        private string $token,
        private string $latitude,
        private string $longitude,
    ) {
    }

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return "api/guest/{$this->guestShareId}/location/";
    }

    protected function defaultBody(): array
    {
        return [
            'token' => $this->token,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
