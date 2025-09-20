<?php

namespace App\Http\Controllers\Guest;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GuestController extends Controller
{
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

        return view('guest.show', [
            'tenant' => $tenant,
            'id' => $id,
            'token' => $token,
        ]);
    }
}
