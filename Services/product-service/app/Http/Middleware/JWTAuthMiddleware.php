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
