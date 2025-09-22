<?php

namespace App\Services;

use App\Http\Integrations\CAD\CAD;
use App\Http\Integrations\CAD\Requests\CallService;
use App\Http\Integrations\CAD\Requests\Geofence;
use App\Http\Integrations\CAD\Requests\UpdateLocation;
use Illuminate\Support\Facades\Cache;

class CADService
{
    // Cache configuration
    private const int CALL_SERVICE_CACHE_TTL_MINUTES = 30;
    private const int GEOFENCE_CACHE_TTL_MINUTES = 120;

    // Cache key parts
    private const string CACHE_KEY_PREFIX_CALL_SERVICE = 'cad:call-service';
    private const string CACHE_KEY_PREFIX_GEOFENCE = 'cad:geofence';
    private const string CACHE_KEY_SEPARATOR = ':';

    /**
     * Retrieves call service data for a specific tenant and guest share ID using the provided token.
     *
     * Makes a cached API request to fetch the call service data.
     * If an error occurs during the process, it logs the error and throws an exception.
     *
     * @param string $tenant The tenant identifier.
     * @param int $guestShareId The guest share ID.
     * @param string $token The authentication token.
     *
     * @return array|null The retrieved call service data or null if unavailable.
     *
     * @throws \Exception If an error occurs while retrieving call service data.
     */
    public function getCallService(string $tenant, int $guestShareId, string $token): ?array
    {
        try {
            return $this->fetchWithCache(
                prefix: self::CACHE_KEY_PREFIX_CALL_SERVICE,
                ttlMinutes: self::CALL_SERVICE_CACHE_TTL_MINUTES,
                tenant: $tenant,
                cacheKey: $this->buildCallServiceCacheKey($tenant, $guestShareId, $token),
                makeRequest: fn (CAD $connector) => $connector->send(new CallService(
                    guestShareId: $guestShareId,
                    token: $token,
                )),
            );
        } catch (\Exception $e) {
            $this->logError('Get Call Service Error', $e, $guestShareId, $token);
            throw new \Exception('Failed to retrieve call service data', 0, $e);
        }
    }

    /**
     * Retrieves geofence data for a specific tenant, guest share ID, and region using the provided token.
     *
     * Makes a cached API request to fetch the geofence data.
     * If an error occurs during the process, it logs the error and throws an exception.
     *
     * @param string $tenant The tenant identifier.
     * @param int $guestShareId The guest share ID.
     * @param string $token The authentication token.
     * @param int $regionId The region identifier.
     *
     * @return array|null The retrieved geofence data or null if unavailable.
     *
     * @throws \Exception If an error occurs while retrieving geofence data.
     */
    public function getGeofence(string $tenant, int $guestShareId, string $token, int $regionId): ?array
    {
        try {
            return $this->fetchWithCache(
                prefix: self::CACHE_KEY_PREFIX_GEOFENCE,
                ttlMinutes: self::GEOFENCE_CACHE_TTL_MINUTES,
                tenant: $tenant,
                cacheKey: $this->buildGeofenceCacheKey($tenant, $regionId),
                makeRequest: fn (CAD $connector) => $connector->send(new Geofence(
                    guestShareId: $guestShareId,
                    token: $token,
                )),
            );
        } catch (\Exception $e) {
            $this->logError('Get Geofence Error', $e, $guestShareId, $token);
            throw new \Exception('Failed to retrieve geofence data', 0, $e);
        }
    }

    /**
     * Clears the cache for call service data associated with a specific tenant and guest share ID using the provided token.
     *
     * Removes the cached data for the specified call service, ensuring subsequent calls will retrieve fresh data.
     *
     * @param string $tenant The tenant identifier.
     * @param int $guestShareId The guest share ID.
     * @param string $token The authentication token.
     *
     * @return void
     */
    public function clearCallServiceCache(string $tenant, int $guestShareId, string $token): void
    {
        Cache::forget($this->buildCallServiceCacheKey($tenant, $guestShareId, $token));
    }

    /**
     * Clears the cached geofence data for a specific tenant and region.
     *
     * Removes the cache entry identified by the generated geofence cache key.
     *
     * @param string $tenant The tenant identifier.
     * @param int $regionId The region ID.
     *
     * @return void
     */
    public function clearGeofenceCache(string $tenant, int $regionId): void
    {
        Cache::forget($this->buildGeofenceCacheKey($tenant, $regionId));
    }

    /**
     * Retrieves and caches data using the provided cache key and callback function.
     *
     * This method attempts to fetch data from the cache. If the cache does not contain the data,
     * it invokes the provided callable to make a request and store the result in the cache for a specified duration.
     *
     * @param string $prefix A prefix to organize or categorize the cache key.
     * @param int $ttlMinutes The time-to-live for the cache entry, in minutes.
     * @param string $tenant The tenant identifier to be used in creating the connector.
     * @param string $cacheKey The unique key used for caching the response.
     * @param callable $makeRequest A callback that handles data retrieval, receives a `CAD` instance as an argument.
     *
     * @return array|null The cached or newly retrieved data as an array, or null if the request is unsuccessful.
     */
    private function fetchWithCache(
        string $prefix,
        int $ttlMinutes,
        string $tenant,
        string $cacheKey,
        callable $makeRequest
    ): ?array {
        $ttl = now()->addMinutes($ttlMinutes);

        return Cache::remember($cacheKey, $ttl, function () use ($tenant, $makeRequest) {
            $connector = new CAD(tenant: $tenant);
            $response = $makeRequest($connector);
            return $response->successful() ? $response->json() : null;
        });
    }


    /**
     * Builds a cache key for the call service using tenant identifier, guest share ID, and token.
     */
    private function buildCallServiceCacheKey(string $tenant, int $guestShareId, string $token): string
    {
        return implode(self::CACHE_KEY_SEPARATOR, [
            self::CACHE_KEY_PREFIX_CALL_SERVICE,
            $tenant,
            (string) $guestShareId,
            $this->hashToken($token),
        ]);
    }

    /**
     * Constructs a cache key for the geofence using the tenant identifier and region ID.
     */
    private function buildGeofenceCacheKey(string $tenant, int $regionId): string
    {
        return implode(self::CACHE_KEY_SEPARATOR, [
            self::CACHE_KEY_PREFIX_GEOFENCE,
            $tenant,
            (string) $regionId,
        ]);
    }

    /**
     * Generates a SHA-1 hash of the provided token.
     */
    private function hashToken(string $token): string
    {
        return sha1($token);
    }

    /**
     * Logs an error message along with exception details, guest share ID, and a hashed token.
     */
    private function logError(string $message, \Throwable $e, int $guestShareId, string $token): void
    {
        \Log::error($message, [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'guestShareId' => $guestShareId,
            'token_hash' => $this->hashToken($token),
        ]);
    }

    public function updateGuestLocation(string $tenant, int $guestShareId, string $token, float $latitude, float $longitude): void
    {
        try {
            $connector = new CAD(tenant: $tenant);
            $connector->send(new UpdateLocation($guestShareId, $token, $latitude, $longitude));
        } catch (\Exception $e) {
            $this->logError('Update Guest Location Error', $e, $guestShareId, $token);
            throw new \Exception('Failed to update guest location', 0, $e);
        }
    }
}
