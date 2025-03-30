<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
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
        $branch = Branch::with(['customer', 'user'])->get();

        return response()->json(['branch' => $branch]);
    }
}
