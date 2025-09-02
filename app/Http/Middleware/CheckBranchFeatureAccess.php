<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBranchFeatureAccess
{

    //Heads up, this the New and working middlewsre, we changed due to the new update from the client
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // app/Http/Middleware/CheckFeatureAccess.php
    public function handle($request, Closure $next, $feature)
    {
        $user = $request->user();
        $branch = $user->branch ?? $user->customer?->branch ?? $user->driver?->branch;
        
        if (!$branch) {
            abort(403, 'No branch asso');
        }

        $subscription = $branch->activeSubscription();
        
        if (!$subscription) {
            abort(403, 'No active subscription or you need to upgrade');
        }

        // Check if feature exists in current or any lower plan
        $featureExists = $subscription->plan->allFeatures
            ->contains('slug', $feature);

        if (!$featureExists) {
            abort(403, 'Feature not included in your plan');
        }

        return $next($request);
    }

    protected function resolveBranchFromUser($user): ?Branch
    {
        // if ($user->isSuperAdmin()) {
        //     // Super admins can access all features
        //     return null;
        // }

        if ($user->branch) {
            // Direct branch association (for branch managers)
            return $user->branch;
        }

        if ($user->customer) {
            // Customer access
            return $user->customer->branch;
        }

        if ($user->driver) {
            // Driver access
            return $user->driver->branch;
        }

        return null;
    }
}
