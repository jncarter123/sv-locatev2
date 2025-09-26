<?php

namespace App\Http\Integrations\CAD\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdateLocation extends Request implements HasBody
{
    use HasJsonBody;
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
        private float $latitude,
        private float $longitude,
        private ?string $accuracy = null
    ) {
    }

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return "api/guest/{$this->guestShareId}/location";
    }

    protected function defaultBody(): array
    {
        return [
            'token' => $this->token,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy
        ];
    }
}
