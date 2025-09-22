<?php

namespace App\Http\Controllers\API;

use App\Services\CADService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestLocationAPIController
{
    protected CADService $cadService;

    public function __construct(CADService $cadService)
    {
        $this->cadService = $cadService;
    }

    public function updateLocation(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'tenant' => 'required|string',
                'guestShareId' => 'required|integer',
                'token' => 'required|string',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);

            $tenant = $data['tenant'];
            $guestShareId = $data['guestShareId'];
            $token = $data['token'];
            $latitude = $data['latitude'];
            $longitude = $data['longitude'];

            $this->cadService->updateGuestLocation($tenant, $guestShareId, $token, $latitude, $longitude);

            return response()->json([
                'success' => true,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to update location', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tenant' => $tenant ?? null,
                'guestShareId' => $guestShareId ?? null,
                'token' => $token ?? null,
                'latitude' => $latitude ?? null,
                'longitude' => $longitude ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update location',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
