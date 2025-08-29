<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\OfficeLocation;
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

}
