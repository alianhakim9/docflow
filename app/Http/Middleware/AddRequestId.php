<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddRequestId
{
    /**
     * Handle request dan tambahkan request ID
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate atau ambil request ID
        $requestId = $request->header('X-Request-ID') ?? 'req_' . Str::random(12);

        // Tambahkan ke log context
        Log::withContext([
            'request_id' => $requestId,
            'user_id' => auth()->guard()->user()?->id,
            'ip' => $request->ip(),
        ]);

        // Proses request
        $response = $next($request);

        // Tambahkan request ID ke response header
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
