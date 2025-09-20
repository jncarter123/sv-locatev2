<?php

namespace App\Services;

use App\Http\Integrations\CAD\CAD;
use App\Http\Integrations\CAD\Requests\CallService;
use App\Http\Integrations\CAD\Requests\Geofence;

class CADService
{
    /**
     * Retrieves call service data based on the provided tenant, guest share ID, and token.
     *
     * @param string $tenant The tenant identifier to use for the request.
     * @param int $guestShareId The unique identifier for the guest share.
     * @param string $token The authentication token required for the call service.
     *
     * @return array|null The call service data as an associative array if the request is successful, or null otherwise.
     *
     * @throws \Exception If an error occurs while retrieving the call service data, an exception is thrown with details.
     */
    public function getCallService(string $tenant, int $guestShareId, string $token): ?array
    {
        try {
            $connector = new CAD(tenant: $tenant); // builds https://pm.cad.some.app
            $response = $connector->send(new CallService(
                guestShareId: $guestShareId,
                token: $token,
            ));

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            \Log::error('Get Call Service Error' ,[
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'guestShareId' => $guestShareId,
                'token' => $token,
            ]);

            throw new \Exception('Failed to retrieve call service data', 0, $e);
        }
    }

    /**
     * Executes the process of sending a request to retrieve geofence data using the provided guest share ID and token.
     *
     * @param string $tenant The tenant identifier used for configuration purposes.
     * @param int $guestShareId The unique identifier associated with the guest share.
     * @param string $token The authentication token required for the request to the geofence service.
     *
     * @return array|null Returns the response as an array if the operation succeeds, or null in case of a failure.
     *
     * @throws \Exception Logs error details, including the exception message, trace, guest share ID, and token, upon encountering an error during the operation.
     */
    public function getGeofence(string $tenant, int $guestShareId, string $token): ?array
    {
        try {
            $connector = new CAD(tenant: 'pm'); // builds https://pm.cad.some.app
            $response = $connector->send(new Geofence(
                guestShareId: $guestShareId,
                token: $token,
            ));

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            \Log::error('Get Geofence Error' ,[
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'guestShareId' => $guestShareId,
                'token' => $token,
            ]);

            throw new \Exception('Failed to retrieve geofence data', 0, $e);
        }
    }
}
