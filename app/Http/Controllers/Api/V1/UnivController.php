<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UnivController extends Controller
{
    public function getUserRole()
    {
        $user = auth()->user();
        return response()->json([
            'role' => $user->roles->pluck('name')->first()      
          ]);
    }
}
