<?php

namespace App\Http\Controllers\Api\V1;

use Pusher\Pusher;
use App\Models\Bike;
use App\Models\BikeDoc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBikeRequest;

class BikeController extends Controller
{
    // public function index()
    // {
    //     $user = auth()->user();
    //     $branchId = $user->branch ? $user->branch->id : null;
    //     $bikes = Bike::where('branch_id', $branchId)->with('customer', 'driver.user', 'bikeDocs')->latest()->get();
    //     return response()->json(['bikes' => $bikes], 200);
    // }

    // In your BikeController
public function index(Request $request)
{
    $user = auth()->user();
    $branchId = $user->branch ? $user->branch->id : null;
    $query = Bike::with(['customer','driver.user', 'bikeDocs'])
        ->where('branch_id', $branchId)
        ->when($request->search, function($q) use ($request) {
            $q->where(function($query) use ($request) {
                $query->where('license_plate_number', 'like', '%'.$request->search.'%')
                      ->orWhere('bike_type', 'like', '%'.$request->search.'%')
                      ->orWhere('bike_office', 'like', '%'.$request->search.'%')
                      ->orWhereHas('driver.user', function($q) use ($request) {
                          $q->where('fname', 'like', '%'.$request->search.'%')
                            ->orWhere('lname', 'like', '%'.$request->search.'%');
                      });
            });
        })
        ->orderBy('created_at', 'desc');

    $perPage = $request->per_page ?? 10;
    $bikes = $query->paginate($perPage);

    return response()->json([
        'data' => $bikes->items(),
        'current_page' => $bikes->currentPage(),
        'last_page' => $bikes->lastPage(),
        'total' => $bikes->total(),
    ]);
}

    public function show($id)    
    {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        $bike = Bike::where('branch_id', $branchId)->with('customer', 'driver.user', 'bikeDocs')->findOrFail($id);
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
                //'customer_id' => $validatedData['customer_id'],
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

    public function updateLocation(Request $request, $id)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' =>'required|numeric',
        ]);

        try{
            $bike = Bike::findOrFail($id);
            $bike->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                config('broadcasting.connections.pusher.options')
            );
            $pusher->trigger('bike-location', 'location-updated', [
                'id' => $bike->id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                
            ]);
            return response()->json(['message' => 'Location updated successfully',
            'latitude' => $bike->latitude,
            'longitude' => $bike->longitude,
        ], 200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Failed to update location', 'details' => $e->getMessage()], 500);
        }

       
    }

    public function destroy($id)
    {
        $bike = Bike::findOrFail($id);
        $bike->delete();
        return response()->json(['message' => 'Bike deleted successfully'], 200);
    }
}
