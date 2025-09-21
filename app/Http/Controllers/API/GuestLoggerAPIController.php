<?php

namespace App\Http\Controllers\API;

use App\Services\GuestLoggerService;
use Illuminate\Http\Request;

class GuestLoggerAPIController
{
    protected GuestLoggerService $loggerService;

    public function __construct(GuestLoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
    }

    public function logger(Request $request)
    {
        $data = $request->validate([
            'level' => 'required|string',
            'message' => 'required|string',
            'context' => 'nullable|array',
        ]);


        // Extract data
        $level = $data['level'];
        $message = $data['message'];
        $context = $data['context'] ?? [];

        $this->loggerService->log($level, $message, $context);

        return response()->json([
            'success' => true,
            'message' => 'Log recorded successfully'
        ]);
    }
}
