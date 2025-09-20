<?php

namespace App\Services;

use App\Http\Integrations\CAD\CAD;
use App\Http\Integrations\CAD\Requests\CallService;
use App\Http\Integrations\CAD\Requests\Geofence;

class CADService
{
    /**
     * Handles the process of sending a request to the call service using a provided guest share ID and token.
     *
     * @param int $guestShareId The ID associated with the guest share.
     * @param string $token The token used for authentication in the call service request.
     *
     * @return mixed|null Returns the response as an array if the request is successful, or null if unsuccessful.
     *
     * @throws \Exception Logs an error if an exception occurs during the process, including the message, stack trace, guest share ID, and token.
     */
    public function getCallService(int $guestShareId, string $token): ?array
    {
        try {
            $connector = new CAD(tenant: 'pm'); // builds https://pm.cad.some.app
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
     * Retrieves geofence data based on the provided guest share ID and token.
     *
     * @param int $guestShareId The ID associated with the guest share.
     * @param string $token The authentication token.
     *
     * @return array|null The geofence data as an array if successful, or null on failure.
     *
     * @throws \Exception Catches and logs any exceptions that occur during the process.
     */
    public function getGeofence(int $guestShareId, string $token): ?array
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
