<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LogService
{
    public function info(string $message, array $context = []): void
    {
        Log::info($message, $this->enrichContext($context));
    }

    public function warning(string $message, array $context = []): void
    {
        Log::warning($message, $this->enrichContext($context));
    }

    public function error(string $message, array $context = []): void
    {
        Log::error($message, $this->enrichContext($context));
    }

    public function debug(string $message, array $context = []): void
    {
        Log::debug($message, $this->enrichContext($context));
    }

    /**
     * Tambahkan context otomatis (request ID, user, IP)
     */
    private function enrichContext(array $context): array
    {
        $request = request();

        return array_merge($context, [
            'request_id' => $request->header('X-Request-ID') ?? uniqid('req_'),
            'user_id' => auth()->guard()->user()?->id,
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);
    }
}
