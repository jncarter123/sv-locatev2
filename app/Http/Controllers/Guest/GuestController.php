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
            $details = $this->cadService->getCallService($tenant, $id, $token);

            $geofence = $this->cadService->getGeofence($tenant, $id, $token);;

            return view('guest.show', [
                'tenant' => $tenant,
                'id' => $id,
                'token' => $token,
                'mapsUrl' => config('services.google.maps.base_url'),
                'mapsKey' => config('services.google.maps.api_key'),
            ]);
        } catch (\Exception $e) {
            abort(500, 'An error occurred while processing your request.');
        }
    }
}
