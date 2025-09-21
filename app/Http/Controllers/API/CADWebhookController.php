<?php

namespace App\Http\Controllers\API;

use App\Services\CADService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CADWebhookController
{
    protected CADService $cadService;

    public function __construct(CADService $cadService)
    {
        $this->cadService = $cadService;
    }

    /**
     * Clears the call service cache for a specific tenant and guest share ID.
     *
     * This method validates the incoming request to ensure it includes the required
     * parameters: tenant, guestShareId, and token. It then attempts to clear the cache
     * using the provided information. If the operation is successful, a success response
     * with relevant details is returned. In case of failure, an error response is returned.
     *
     * @param Request $request The incoming HTTP request containing required parameters.
     * @return JsonResponse A JSON response indicating success or failure of the operation.
     * @throws \Exception If an unexpected error occurs during the cache-clearing process.
     */
    public function clearCallServiceCache(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant' => 'required|string',
            'guestShareId' => 'required|integer',
            'token' => 'required|string',
        ]);

        $tenant = $validated['tenant'];
        $guestShareId = $validated['guestShareId'];
        $token = $validated['token'];

        try {
            $this->cadService->clearCallServiceCache($tenant, $guestShareId, $token);
            return response()->json([
                'message' => 'Cache cleared successfully',
                'tenant' => $tenant,
                'guestShareId' => $guestShareId,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage(),
                'tenant' => $tenant,
                'guestShareId' => $guestShareId,
            ], 500);
        }
    }

    /**
     * Clears the geofence cache for a specific tenant and region ID.
     *
     * This method validates the incoming request to ensure it includes the required
     * parameters: tenant and regionId. It then attempts to clear the geofence cache
     * using the provided information. If the operation is successful, a success response
     * with relevant details is returned. In case of failure, an error response is returned.
     *
     * @param Request $request The incoming HTTP request containing required parameters.
     * @return JsonResponse A JSON response indicating success or failure of the operation.
     * @throws \Exception If an unexpected error occurs during the cache-clearing process.
     */
    public function clearGeofenceCache(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant' => 'required|string',
            'regionId' => 'required|integer',
        ]);

        $tenant = $validated['tenant'];
        $regionId = $validated['regionId'];

        try {
            $this->cadService->clearGeofenceCache($tenant, $regionId);
            return response()->json([
                'message' => 'Cache cleared successfully',
                'tenant' => $tenant,
                'regionId' => $regionId,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage(),
                'tenant' => $tenant,
                'regionId' => $regionId,
            ], 500);
        }
    }
}
