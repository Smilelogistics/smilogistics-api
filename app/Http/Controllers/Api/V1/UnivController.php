<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use App\Models\Driver;
use App\Models\Customer;
use Illuminate\Http\Request;
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
            
    public function destroyUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

}
