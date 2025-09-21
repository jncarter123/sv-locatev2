<?php

namespace App\Http\Controllers\API;

use App\Services\CADService;
use Illuminate\Http\JsonResponse;

class CADWebhookController
{
    protected CADService $cadService;

    public function __construct(CADService $cadService)
    {
        $this->cadService = $cadService;
    }

    /**
     * Clears the cache for the specified tenant and guest share ID using the provided token.
     *
     * @param string $tenant The identifier for the tenant whose cache needs clearing.
     * @param int $guestShareId The unique identifier for the guest share.
     * @param string $token The authentication token used to perform the cache clearing operation.
     *
     * @return JsonResponse A JSON response indicating success or failure of the operation.
     */
    public function clearCallServiceCache(string $tenant, int $guestShareId, string $token): JsonResponse
    {
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
     * Clears the geofence cache for a specified tenant and region.
     *
     * @param string $tenant The unique identifier for the tenant whose geofence cache is to be cleared.
     * @param int $regionId The unique identifier for the region associated with the geofence cache.
     *
     * @return JsonResponse A JSON response detailing the outcome of the cache clearing operation.
     */
    public function clearGeofenceCache(string $tenant, int $regionId): JsonResponse
    {
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
