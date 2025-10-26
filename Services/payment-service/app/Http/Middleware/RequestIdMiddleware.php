<?php

namespace App\Http\Middleware;

use App\Support\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get request ID from Traefik header or generate one
        $requestId = $request->header('X-Request-ID');
        
        if (empty($requestId)) {
            $requestId = RequestContext::generateRequestId();
        }

        // Set the request ID in our context helper
        RequestContext::setRequestId($requestId);

        $response = $next($request);
        
        // Add request ID to response headers
        $response->headers->set('Request-ID', $requestId);

        return $response;
    }
}
