<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // public function handle(Request $request, Closure $next): Response
    // {
    //     $user = $request->user();

    //     if (!$user || !$user->isSubscribed()) {
    //         return response()->json([
    //             'error' => 'Subscription required to access this resource.'
    //         ], 403);
    //     }

    //     return $next($request);
    // }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

         if ($user->hasRole('superadministrator')) {
                return $next($request);
            }

        if (!$user) {
            return response()->json([
                'error' => 'Authentication required to access this resource.'
            ], 401);
        }

        // Check if user is directly subscribed (branch admin)
        if ($user->isSubscribed()) {
            return $next($request);
        }

        // Check subscription through customer's branch
        if ($user->customer && $user->customer->branch && $user->customer->branch->isSubscribed()) {
            return $next($request);
        }

        // Check subscription through driver's branch
        if ($user->driver && $user->driver->branch && $user->driver->branch->isSubscribed()) {
            return $next($request);
        }

        // If none of the above conditions are met
        return response()->json([
            'error' => 'Your branch subscription is required to access this resource.'
        ], 403);
    }
}
