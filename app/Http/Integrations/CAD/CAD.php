<?php

namespace App\Http\Integrations\CAD;

use App\Http\Integrations\BoatUS\BoatUSAuth;
use App\Services\IntegrationsService;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;

class CAD extends Connector
{
    use AcceptsJson;

    private string $baseUrl;



    public function __construct(protected ?string $tenant = null,)
    {
        // Attach middleware to log outgoing requests and responses
        $this->middleware()->onRequest(function (PendingRequest $pending) {
            $body = $pending->body()?->all() ?? null;

            // Redact known sensitive keys
            if (is_array($body)) {
                $body = collect($body)->map(function ($value, $key) {
                    return in_array(strtolower((string)$key), ['password', 'api_key', 'api_secret', 'token', 'refresh_token'], true)
                        ? '***REDACTED***'
                        : $value;
                })->toArray();
            }

            \Log::info('CAD request', [
                'method' => $pending->getMethod()->value,
                'url' => $pending->getUrl(),
                'headers' => $pending->headers()->all(),
                'query' => $pending->query()->all(),
                'body' => $body,
                // If available in your Saloon version:
                'curl' => method_exists($pending, 'asCurlCommand') ? $pending->asCurlCommand() : null,
            ]);
        });

        $this->middleware()->onResponse(function (Response $response) {
            \Log::info('CAD response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        });

    }

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        $base = rtrim((string) config('services.cad.base_url'), '/'); // e.g. https://cad.some.app

        if (empty($this->tenant)) {
            return $base;
        }

        // Insert tenant as a subdomain prefix: https://{tenant}.cad.some.app
        $parts = parse_url($base);
        if ($parts === false || !isset($parts['host'])) {
            return $base; // Fallback
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host']; // cad.some.app
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        $tenantHost = $this->tenant . '.' . $host;

        return sprintf('%s://%s%s', $scheme, $tenantHost, $port);
    }

    /**
     * Default headers for every request
     */
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }
}
