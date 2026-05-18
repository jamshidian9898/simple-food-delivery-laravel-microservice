<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;

class JWTAuthMiddleware
{
    /**
     * Authenticate the incoming request using a JWT and set a transient User on the "api" guard.
     *
     * Constructs a User model from JWT claims (id from `sub`, email from `email`, name from `name` with a default of "JWT User", and `type`), marks it as existing to avoid persistence, and assigns it to the `api` guard. If token parsing fails, returns a JSON 401 response.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request.
     * @param \Closure $next The next middleware / request handler.
     * @return \Illuminate\Http\Response The response from the next middleware or a JSON 401 response when the token is invalid.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Parse the token
            $token = JWTAuth::parseToken();
            $payload = $token->getPayload();
            
            $subject = $payload->get('sub');

            if (empty($subject)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token subject is missing'
                ], 401);
            }

            // Create a user object from JWT claims without database lookup
            $user = new User();
            $user->id = $subject;
            $user->email = $payload->get('email');
            $user->name = $payload->get('name') ?? 'JWT User';
            $user->type = $payload->get('type');
            $user->exists = true; // Mark as existing to avoid save attempts
            
            // Set the authenticated user
            auth()->guard('api')->setUser($user);
            
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token is invalid'
            ], 401);
        }

        return $next($request);
    }
}
