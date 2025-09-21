<?php

use App\Http\Controllers\API\CADWebhookController;
use App\Http\Controllers\Guest\GuestController;
use Illuminate\Support\Facades\Route;


Route::get('/', [GuestController::class, 'show'])->name('guest.index');

Route::post('/webhooks/cad/cache/call-service/clear', [CADWebhookController::class, 'clearCallServiceCache'])->name('webhook.cad.call-service.clear');
Route::post('/webhooks/cad/cache/geofence/clear', [CADWebhookController::class, 'clearGeofenceCache'])->name('webhook.cad.geofence.clear');
