<?php

use App\Http\Controllers\Guest\GuestController;
use Illuminate\Support\Facades\Route;


Route::get('/', [GuestController::class, 'show'])->name('guest.index');
