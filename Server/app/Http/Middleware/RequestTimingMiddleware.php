<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestTimingMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $requestId = (string) Str::uuid();

        $response = $next($request);

        $durationMs = (microtime(true) - $startedAt) * 1000;
        $roundedMs = (int) round($durationMs);

        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('X-Request-Duration-Ms', (string) $roundedMs);

        $context = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $roundedMs,
            'user_id' => $request->user()?->id,
            'company_id' => $request->user()?->company_id,
        ];

        if ($roundedMs >= 800) {
            Log::warning('Slow request detected', $context);
        } else {
            Log::info('Request timing', $context);
        }

        return $response;
    }
}
