<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SettingsController extends Controller
{
    public function index() {
        $customers = Branch::with('customers')->get();
        return response()->json($branches);
    }
}
