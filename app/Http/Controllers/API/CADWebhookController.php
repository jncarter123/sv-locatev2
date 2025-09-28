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

    public function clearGuestShareCache(Request $request): JsonResponse
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
            $this->cadService->clearGuestShareCache($tenant, $guestShareId, $token);
            return response()->json([
                'message' => 'Guest share cache cleared successfully',
                'tenant' => $tenant,
                'guestShareId' => $guestShareId,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear guest share cache',
                'error' => $e->getMessage(),
                'tenant' => $tenant,
                'guestShareId' => $guestShareId,
            ], 500);
        }
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
            'callServiceGUID' => 'required|string',
        ]);

        $tenant = $validated['tenant'];
        $callServiceGUID = $validated['callServiceGUID'];

        try {
            $this->cadService->clearCallServiceCache($tenant, $callServiceGUID);
            return response()->json([
                'message' => 'Cache cleared successfully',
                'tenant' => $tenant,
                'callServiceGUID' => $callServiceGUID,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage(),
                'tenant' => $tenant,
                'callServiceGUID' => $callServiceGUID,
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

    /**
     * Handles the request to refresh the call service cache for a given tenant and guest share.
     *
     * Validates the input request for required fields including tenant, guestShareId, token, and callServiceGUID.
     * If validation is successful, it attempts to refresh the call service cache using the provided parameters.
     *
     * In case of success, returns a JSON response indicating the cache refresh was successful along with the tenant and callServiceGUID.
     * In case of failure, returns a JSON response with an error message and exception details.
     *
     * @param Request $request The incoming HTTP request containing the required parameters.
     * @return JsonResponse JSON response indicating the status of the cache refresh operation.
     * @throws \Illuminate\Validation\ValidationException If the validation of the request fails.
     */
    public function refreshCallServiceCache(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant' => 'required|string',
            'guestShareId' => 'required|integer',
            'token' => 'required|string',
            'callServiceGUID' => 'required|string',
        ]);

        $tenant = $validated['tenant'];
        $guestShareId = $validated['guestShareId'];
        $token = $validated['token'];
        $callServiceGUID = $validated['callServiceGUID'];

        try {
            $this->cadService->refreshCallServiceCache($tenant, $guestShareId, $token, $callServiceGUID);
            return response()->json([
                'message' => 'Call service cache refreshed successfully',
                'tenant' => $tenant,
                'callServiceGUID' => $callServiceGUID,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to refresh call service cache',
                'error' => $e->getMessage(),
                'tenant' => $tenant,
                'callServiceGUID' => $callServiceGUID,
            ], 500);
        }
    }
}
