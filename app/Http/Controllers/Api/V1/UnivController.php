<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
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
        $branchId = $user->branch ? $user->branch->id : null;
        $branch = Customer::with(['branch', 'user'])
        ->where('branch_id', $branchId)
        ->get();

        return response()->json(['branch' => $branch]);
    }
}
