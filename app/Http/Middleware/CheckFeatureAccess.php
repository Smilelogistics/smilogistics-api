<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Feature;

class CheckFeatureAccess
{
    public function handle($request, Closure $next, ...$features)
    {
        $user = $request->user();
        $branch = $user->branch ?? $user->customer?->branch ?? $user->driver?->branch;
        
        if (!$branch) {
            return response()->json(['message' => 'No branch associated'], 403);
        }

        $subscription = $branch->activeSubscription();
        
        if (!$subscription) {
            return response()->json(['message' => 'No active subscription'], 403);
        }

        // Check all required features
        foreach ($features as $featureSlug) {
            if (!$subscription->plan->allFeatures->contains('slug', $featureSlug)) {
                return response()->json([
                    'message' => 'Feature not available: ' . $featureSlug,
                    'missing_feature' => $featureSlug,
                    'required_plan' => Feature::where('slug', $featureSlug)
                        ->first()
                        ?->plans()
                        ->orderBy('level', 'desc')
                        ->first()
                        ?->slug
                ], 403);
            }
        }

        return $next($request);
    }
}