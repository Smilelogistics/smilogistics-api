<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Plan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    // Heads we no longer use this mmidleware, its just here for future reference
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

    public function handle($request, Closure $next, $requiredPlan)
    {
        $user = $request->user();
        
        // Get the user's branch through different relationships
        $branch = $user->branch ?? $user->customer?->branch ?? $user->driver?->branch;
        
        if (!$branch) {
            return response()->json(['message' => 'No branch associated'], 403);
        }

        $subscription = $branch->activeSubscription();
        
        if (!$subscription) {
            return response()->json(['message' => 'No active subscription or you need to upgrade'], 403);
        }

        // Get required plan level
        $requiredPlanLevel = Plan::where('slug', $requiredPlan)->value('level');
        
        if ($subscription->plan->level < $requiredPlanLevel) {
            return response()->json([
                'message' => 'Upgrade to ' . $requiredPlan . ' plan to access this feature',
                'required_plan' => $requiredPlan,
                'current_plan' => $subscription->plan->slug
            ], 403);
        }

        return $next($request);
    }
}
