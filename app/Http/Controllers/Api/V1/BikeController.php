<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Bike;
use App\Models\BikeDoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBikeRequest;

class BikeController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        $bikes = Bike::where('branch_id', $branchId)->with('customer', 'driver')->latest()->get();
        return response()->json(['bikes' => $bikes], 200);
    }

    public function show($id)    
    {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        $bike = Bike::where('branch_id', $branchId)->with('customer', 'driver')->findOrFail($id);
        return response()->json(['bike' => $bike], 200);
    }
    public function store(CreateBikeRequest $request)
    {
        $validatedData = $request->validated();
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;

        try {
            DB::beginTransaction();
    
            $bike = Bike::create([
                'branch_id' => $branchId,
                'driver_id' => $validatedData['driver_id'],
                'customer_id' => $validatedData['customer_id'],
                'bike_type' => $validatedData['bike_type'],
                'bike_number' => $validatedData['bike_number'],
                'bike_office' => $validatedData['bike_office'],
                'make_model' => $validatedData['make_model'],
                'license_plate_number' => $validatedData['license_plate_number'],
            ]);
    
            if (isset($validatedData['file_path']) && is_array($validatedData['file_path'])) {
                foreach ($validatedData['file_path'] as $file) {
                    $filename = $file->store('bikes', 'public');
                    BikeDoc::create([
                        'bike_id' => $bike->id,
                        'file' => $filename,
                    ]);
                }
            }
    
            DB::commit();
    
            return response()->json(['message' => 'Bike created successfully'], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create bike', 'details' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $bike = Bike::findOrFail($id);
    
        DB::beginTransaction();
    
        try {
            $bike->update($request->except('file_path'));
    
            if ($request->hasFile('file_path')) {
                $files = $request->file('file_path');
    
                if (!is_array($files)) {
                    $files = [$files];
                }
    
                foreach ($files as $file) {
                    $filename = $file->store('bikes', 'public');
    
                    BikeDoc::create([
                        'bike_id' => $bike->id,
                        'file' => $filename,
                    ]);
                }
            }
    
            DB::commit();
    
            return response()->json(['message' => 'Bike updated successfully'], 200);
    
        } catch (\Exception $e) {
            DB::rollBack();
    
            return response()->json([
                'error' => 'Update failed',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $bike = Bike::findOrFail($id);
        $bike->delete();
        return response()->json(['message' => 'Bike deleted successfully'], 200);
    }
}
