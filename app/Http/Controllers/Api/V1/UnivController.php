<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Plan;
use App\Models\User;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Exception;
use App\Models\OfficeLocation;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class UnivController extends Controller
{



    public function getUserRole()
    {
        $user = auth()->user();
        return response()->json([
            'role' => $user->roles->pluck('name')->first()      
          ]);
    }
    
    public function getMapsData()
    {
        return response()->json([
            'apiKey' => config('services.google_maps.api')
        ]);
    }

    public function getBranches()
    {
        $user = auth()->user();
        $branchId = auth()->user()->getBranchId();

            $branches = Branch::with(['user', 'offices'])
                ->where('id', $branchId)
                ->latest()
                ->get();

                if($user->hasRole('businessadministrator')) {
                    $customers = Customer::with(['user', 'branch'])
                ->where('branch_id', $branchId)
                ->latest()
                ->get();
                }
                else {
                    $customers = Customer::with(['user', 'branch'])
                ->where('branch_id', $branchId)
                ->where('id', auth()->user()->customer->id)
                ->latest()
                ->get();
                }
        
        
        return response()->json([
            'branches' => $branches,
            'customers' => $customers
        ]);
    }

    public function getUsers()
    {
        $user = auth()->user();
        //dd($user);

        if($user->user_type == 'superadministrator') {
            //dd('ok');
            $customers = Branch::with(['user'])
                //->where('branch_id', $branchId)
                ->latest()
                ->get();

                return response()->json([
            'customers' => $customers,
            
        ]);
        }
        elseif($user->hasRole('businessadministrator')) {

        $branchId = auth()->user()->getBranchId();
        $customers = Customer::with(['branch', 'user'])
            ->where('branch_id', $branchId)
            ->latest()
            ->get();

        $drivers = Driver::with(['branch', 'user'])
            ->where('branch_id', $branchId)
            ->latest()
            ->get();

            return response()->json([
            'customers' => $customers,
            'drivers' => $drivers,
        ]);
        }

        
    }


    public function getOffices()
    {
        $user = auth()->user();
        $branchId = auth()->user()->getBranchId();

        $offices = OfficeLocation::where('branch_id', $branchId)
            ->latest()
            ->get();

        return response()->json([
            'offices' => $offices,
        ]);
    }
            
    public function destroyUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    // method to add user unlimited acceess
    public function grantUnlimitedAccess(Request $request)
    {
        $email = $request->input('email');
        
        if (!$email) {
            return response()->json(['error' => 'Email is required'], 400);
        }

        try {
            DB::beginTransaction();

            // Find user by email
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                throw new Exception("User with email {$email} not found.");
            }

            // Get the user's branch
            $branch = $user->branch;
            
            if (!$branch) {
                throw new Exception('User is not associated with any branch');
            }

            // Get the premium plan (highest plan)
            $plan = Plan::where('name', 'Premium')->first();
            
            if (!$plan) {
                // Fallback: get the plan with highest price or features
                $plan = Plan::orderBy('price', 'desc')->first();
            }
            
            if (!$plan) {
                throw new Exception('No premium plan found');
            }

            //dd($plan);

            // Set start and end dates (100 years from now)
            $startDate = now();
            //$endDate = now()->addYears(5);
            $endDate = null;

            // Cancel any existing active subscriptions
            $branch->subscriptions()->where('status', 'active')->update([
                'status' => 'canceled',
                'canceled_at' => now()
            ]);

            // Create new unlimited subscription
            $subscription = $branch->subscriptions()->create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'starts_at' => $startDate,
                'ends_at' => $endDate,
                'status' => 'active'
            ]);

            // Update branch subscription details
            $branch->update([
                'isSubscribed' => true,
                'subscription_end_date' => $endDate,
                'subscription_start_date' => $startDate,
                'subscription_type' => $plan->slug,
                'subscription_count' => $branch->subscription_count + 1
            ]);

            // Optional: Create a transaction record for tracking
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount' => 0, // Free for special user
                'currency' => 'NGN',
                'status' => 'success',
                'payment_method' => 'admin',
                'payment_type' => 'unlimited_grant',
                'payment_gateway_ref' => 'UNLIMITED_' . time() . '_' . $user->id,
                'customer_email' => $user->email,
                'description' => 'Unlimited access granted for special user'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Unlimited access granted successfully',
                'user' => $user->email,
                'plan' => $plan->name,
                'subscription_end_date' => optional($endDate)->format('Y-m-d H:i:s'),
                'subscription' => $subscription,
                'transaction' => $transaction
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

// Alternative: If you want to grant access by user ID instead of email
public function grantUnlimitedAccessById(Request $request)
{
    $userId = $request->input('user_id');
    
    if (!$userId) {
        return response()->json(['error' => 'User ID is required'], 400);
    }

    try {
        DB::beginTransaction();

        $user = User::findOrFail($userId);
        $branch = $user->branch;
        
        if (!$branch) {
            throw new Exception('User is not associated with any branch');
        }

        $plan = Plan::where('name', 'premium')->first() ?? Plan::orderBy('price', 'desc')->first();
        
        if (!$plan) {
            throw new Exception('No premium plan found');
        }

        $startDate = now();
        $endDate = now()->addYears(100);

        $branch->subscriptions()->where('status', 'active')->update([
            'status' => 'canceled',
            'canceled_at' => now()
        ]);

        $subscription = $branch->subscriptions()->create([
            'plan_id' => $plan->id,
            'starts_at' => $startDate,
            'ends_at' => $endDate,
            'status' => 'active',
            'is_unlimited' => true
        ]);

        $branch->update([
            'isSubscribed' => true,
            'subscription_end_date' => $endDate,
            'subscription_start_date' => $startDate,
            'subscription_type' => $plan->slug,
            'subscription_count' => $branch->subscription_count + 1
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount' => 0,
            'currency' => 'NGN',
            'status' => 'success',
            'payment_type' => 'unlimited_grant',
            'payment_gateway' => 'admin_grant',
            'payment_gateway_ref' => 'UNLIMITED_' . time() . '_' . $user->id,
            'customer_email' => $user->email,
            'description' => 'Unlimited access granted for special user'
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Unlimited access granted successfully',
            'user' => $user->email,
            'plan' => $plan->name,
            'subscription_end_date' => $endDate->format('Y-m-d H:i:s'),
            'subscription' => $subscription,
            'transaction' => $transaction
        ], 200);

    } catch (Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 400);
    }
}

}
