<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserType
{
    /**
     * Handle an incoming request.
     * Checks if user is of the required type.
     */
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }
        
        $allowed = match($type) {
            'individual' => $user->isIndividual(),
            'business' => $user->isBusiness(),
            'client' => $user->isClient(),
            'mobile' => $user->isMobileUser(),
            default => false,
        };
        
        if (!$allowed) {
            return response()->json([
                'success' => false,
                'message' => 'This feature is not available for your account type.',
                'error_code' => 'INVALID_USER_TYPE',
                'required_type' => $type,
                'your_type' => $user->user_type,
            ], 403);
        }

        return $next($request);
    }
}
