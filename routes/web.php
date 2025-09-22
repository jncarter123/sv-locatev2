<?php

use App\Http\Controllers\API\CADWebhookController;
use App\Http\Controllers\API\GuestLocationAPIController;
use App\Http\Controllers\API\GuestLoggerAPIController;
use App\Http\Controllers\Guest\GuestController;
use Illuminate\Support\Facades\Route;


Route::get('/', [GuestController::class, 'show'])->name('guest.index');

Route::post('/api/guest/logger', [GuestLoggerAPIController::class, 'logger'])->name('api.guest.logger');
Route::post('/api/guest/location',[GuestLocationAPIController::class, 'updateLocation'])->name('api.guest.location.update');

Route::post('/webhooks/cad/cache/call-service/clear', [CADWebhookController::class, 'clearCallServiceCache'])->name('webhook.cad.call-service.clear');
Route::post('/webhooks/cad/cache/geofence/clear', [CADWebhookController::class, 'clearGeofenceCache'])->name('webhook.cad.geofence.clear');
