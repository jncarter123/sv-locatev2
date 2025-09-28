<?php

namespace App\Http\Controllers\Guest;

use App\Services\CADService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GuestController extends Controller
{
    protected CADService $cadService;

    public function __construct(CADService $cadService)
    {
            $this->cadService = $cadService;
    }


    /**
     * Display a basic CAD page requiring tenant, id, and token query parameters.
     *
     * Example: /cad?tenant=pm&id=123&token=abc
     */
    public function show(Request $request)
    {
        $tenant = $request->query('tenant');
        $id = $request->query('id');
        $token = $request->query('token');

        // If any required parameter is missing or empty, return a 404
        if (empty($tenant) || empty($id) || empty($token)) {
            abort(404);
        }

        try {
            $guestShare = $this->cadService->getGuestShare($tenant, $id, $token);

            $details = $this->cadService->getCallService($tenant, $id, $token, $guestShare['call_service_guid']);

            $regionId = $details['region_id'];
            $geofences = $this->cadService->getGeofence($tenant, $id, $token, $regionId);

            return view('guest.show', [
                'tenant' => $tenant,
                'id' => $id,
                'token' => $token,
                'mapsUrl' => config('services.google.maps.base_url'),
                'mapsKey' => config('services.google.maps.api_key'),
                'mapId' => config('services.google.maps.map_id'),
                'guestShare' => $guestShare,
                'details' => $details,
                'geofences' => $geofences,
            ]);
        } catch (\Exception $e) {
            abort(500, 'An error occurred while processing your request.');
        }
    }
}
