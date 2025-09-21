<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestLoggerService
{
    private const array SUPPORTED_LEVELS = ['debug', 'info', 'warning', 'error'];

    /**
     *
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $normalizedLevel = $this->normalizeLevel($level);
        $method = $this->resolveLogMethod($normalizedLevel);
        \Log::$method($message, $context);
    }

    /**
     *
     */
    private function normalizeLevel(string $level): string
    {
        return strtolower($level);
    }

    /**
     *
     */
    private function resolveLogMethod(string $normalizedLevel): string
    {
        // Map normalized levels to Log facade methods
        $map = [
            'debug' => 'debug',
            'info' => 'info',
            'warning' => 'warning',
            'error' => 'error',
        ];

        return $map[$normalizedLevel] ?? 'info';
    }
}
