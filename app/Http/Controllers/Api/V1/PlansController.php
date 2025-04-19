<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Plan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PlansController extends Controller
{
    public  function index(){
       $plans = Plan::all();

       return response()->json([
           'status' => 'success',
           'message' => 'Plans retrieved successfully',
           'plans' => $plans
       ]);
    }

    public function show($id){
        $plan = Plan::find($id);

        return response()->json($plan);
    }

    public function store(Request $request){
        $validatedData = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'description' => 'required|string',
            'billing_cycle' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'trial_days' => 'nullable|integer|min:0',
            'currency' => 'nullable|string|size:3',
            'sort_order' => 'nullable|integer',
            'features' => 'nullable|json',
            'max_users' => 'nullable|integer|min:0',
            'storage_limit' => 'nullable|integer|min:0',
            'plan_code' => 'nullable|string|max:255|unique:plans,plan_code',
            'setup_fee' => 'nullable|numeric|min:0',
            'support_level' => 'nullable|string|max:255',
            'shipment_count' => 'nullable|integer|min:0',
            'truck_count' => 'nullable|integer|min:0',
            'driver_count' => 'nullable|integer|min:0',
            'customer_count' => 'nullable|integer|min:0',
        ]);
        
        if ($validatedData->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validatedData->errors()
            ], 422);
        }
        
        $validatedData = $validatedData->validated();

        $plan = Plan::create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Plan created successfully',
            'data' => $plan
        ]);
    }
}
