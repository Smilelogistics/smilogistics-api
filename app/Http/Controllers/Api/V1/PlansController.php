<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Plan;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
            'features' => 'nullable|array',
            'features.*' => 'string|max:255',
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

        if (isset($validatedData['features'])) {
            $validatedData['features'] = json_encode($validatedData['features']);
        }
    
        $plan = Plan::create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Plan created successfully',
            'data' => $plan
        ]);
    }

    public function destroy($id){
        $plan = Plan::findOrFail($id);
        $plan->delete();
        return response()->json(['message' => 'Plan deleted successfully'], 200);
    }

    public function getFeatures()
    {
        $features = Feature::all();
        return response()->json($features);
    }
public function newPlan(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'slug' => 'required|string|unique:plans,slug',
        'price' => 'required|numeric',
        'interval' => 'required|string|in:monthly,yearly',
        'description' => 'nullable|string',
        'features' => 'array',
        'features.*' => 'exists:features,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()
        ], 422);
    }

        $validated = $validator->validated();

        try{

            DB::beginTransaction();
            $plan = Plan::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'price' => $validated['price'],
            'interval' => $validated['interval'],
            'description' => $validated['description'] ?? '',
            'is_active' => true,
        ]);

        $plan->features()->attach($validated['features'] ?? []);

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Plan created successfully',
            'data' => $plan
        ]);
        
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function storeFeature(Request $request)
    {
        $validator = Validator::make($request->all(), [
         'name' => 'required|string|max:255',
         'slug' => 'required|string|max:255|unique:features,slug',
         'description' => 'nullable|string'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()
        ], 422);
    }

        $validated = $validator->validated();

        try{

            DB::beginTransaction();
            $feature = Feature::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? '',
        ]);

        DB::commit();


        return response()->json([
            'status' => 'success',
            'message' => 'Feature created successfully',
            'data' => $feature
        ]);
        
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

}
