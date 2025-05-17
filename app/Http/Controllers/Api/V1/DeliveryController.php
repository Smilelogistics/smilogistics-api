<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bike;
use Illuminate\Support\Facades\DB;


class DeliveryController extends Controller
{
public function getMyDeliveries()	
{
    $user = auth()->user();
    $branchId = auth()->user()->getBranchId();
    $driverId = $user->driver ? $user->driver->id : null;
    $myDeliveries = Delivery::where('driver_id', $driverId)->where('branch_id', $branchId)->get();
    return response()->json(['deliveries' => $myDeliveries], 200);
}

public function makeRequest(Request $request)
{
    $validated = $request->validate([
        'from_address' => 'required|string|max:255',
        'to_address' => 'required|string|max:255',
        'distance_km' => 'required|numeric|min:0',
        'weight_kg' => 'required|numeric|min:0.1',
        'service_type' => 'required|in:standard,express,same-day',
        'delivery_cost' => 'required|numeric|min:0',
        'estimated_duration' => 'required|string',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
    ]);

    $lat = $validated['latitude'];
    $lng = $validated['longitude'];
    $radiusLevels = [10, 30, 60, 90, 120, 150];

    $driver = null;

    foreach ($radiusLevels as $radius) {
        $driver = Bike::select('*')
            ->selectRaw("
                (6371 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )) AS distance", [$lat, $lng, $lat])
            ->where('status', 'available')
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc')
            ->first();

        if ($driver) {
            break;

        }
    }

    if (!$driver) {
        return response()->json([
            'success' => false,
            'message' => 'No bikes available nearby.'
        ], 404);
    }

    // Assign driver
    $shipment = ConsolidateShipment::find($request->shipment_id);

    $delivery = Delivery::create([
        'shipment_id' => $shipment->id,
        'driver_id' => $driver->driver_id,
        'assigned_at' => now(),
        'driver_status' => 'pending'
    ]);

    // Notify driver
    $driverUser = User::find($driver->driver_id);
    if ($driverUser) {
        $driverUser->notify(new ShipmentAssigned($shipment));
    }

    return response()->json([
        'success' => true,
        'data' => $delivery
    ]);
}


    // public function makeRequest(Request $request)
    // {
    //     $validated = $request->validate([
    //         'from_address' => 'required|string|max:255',
    //         'to_address' => 'required|string|max:255',
    //         'distance_km' => 'required|numeric|min:0',
    //         'weight_kg' => 'required|numeric|min:0.1',
    //         'service_type' => 'required|in:standard,express,same-day',
    //         'delivery_cost' => 'required|numeric|min:0',
    //         'estimated_duration' => 'required|string',
    //         'latitude' => 'required|numeric', 'longitude' => 'required|numeric', 'driver_id' => 'required|exists:drivers,id'
    //     ]);

        
    //     $shipment = ConsolidateShipment::find($request->shipment_id);
    //     $getDriver = bikes::where('status', 'available')->first();

    //     $delivery = Delivery::update([
    //         'driver_id' => $getDriver->driver_id,
    //         'assigned_at' => now(),
    //         'driver_status' => 'pending'
    //     ]);


    //     $driver = User::find($request->driver_id);
    //     $driver->notify(new ShipmentAssigned($shipment));

    //     return response()->json([
    //         'success' => true,
    //         'data' => $delivery // return the assigned driver
    //     ]);
        
    //     // Calculate cost breakdown
    //     $baseRate = $this->calculateBaseRate($validated['service_type'], $validated['distance_km']);
    //     $weightSurcharge = $this->calculateWeightSurcharge($validated['weight_kg'], $baseRate);
    //     $serviceFee = $this->getServiceFee($validated['service_type']);
        
    //     $shipment = ConsolidateShipment::create([
    //         'origin_warehouse' => $validated['from_address'],
    //         'destination_warehouse' => $validated['to_address'],
    //         'total_weight' => $validated['weight_kg'],
    //         'distance_km' => $validated['distance_km'],
    //         'base_rate' => $baseRate,
    //         'weight_surcharge' => $weightSurcharge,
    //         'service_fee' => $serviceFee,
    //         'service_type' => $validated['service_type'],
    //         'total_shipping_cost' => $validated['delivery_cost'],
    //         'estimated_duration' => $validated['estimated_duration'],
    //         'status' => 'quote'
    //     ]);
        
    //     return response()->json([
    //         'success' => true,
    //         'data' => $shipment
    //     ]);
    // }

    private function calculateBaseRate($serviceType, $distanceKm)
    {
        switch($serviceType) {
            case 'express': return $distanceKm * 75;
            case 'same-day': return $distanceKm * 100;
            default: return $distanceKm * 50;
        }
    }

    private function calculateWeightSurcharge($weight, $baseRate)
    {
        if ($weight <= 5) return 0;
        return $baseRate * (Math.ceil(($weight - 5)/5) * 0.1);
    }

    private function getServiceFee($serviceType)
    {
        switch($serviceType) {
            case 'express': return 200;
            case 'same-day': return 300;
            default: return 100;
        }
    }

    // app/Http/Controllers/ShipmentController.php
    // public function assignDriver(Request $request)
    // {
    //     $request->validate([
    //         'latitude' => 'required|numeric', 'longitude' => 'required|numeric', 'driver_id' => 'required|exists:drivers,id'
    //     ]);
        
    //     $shipment = ConsolidateShipment::find($request->shipment_id);
    //     $getDriver = bikes::where('status', 'available')->first();

    //     $delivery = Delivery::update([
    //         'driver_id' => $getDriver->driver_id,
    //         'assigned_at' => now(),
    //         'driver_status' => 'pending'
    //     ]);


    //     $driver = User::find($request->driver_id);
    //     $driver->notify(new ShipmentAssigned($shipment));

    //     return response()->json([
    //         'success' => true,
    //         'data' => $delivery // return the assigned driver
    //     ]);
    // }

    public function getShipments(User $driver)
{
    $shipments = ConsolidateShipment::where('driver_id', $driver->id)
        ->where('driver_status', '!=', 'delivered')
        ->orderBy('driver_status')
        ->orderBy('assigned_at')
        ->get();
    
    return response()->json($shipments);
}

    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:pending,accepted,in_transit,delivered'
        ]);
        
        $delivery = Delivery::update(['driver_status' => $request->status]);
        
        // If delivered, record delivery time
        if ($request->status === 'delivered') {
            $shipment->update(['delivered_at' => now()]);
        }
        
        return response()->json(['success' => true]);
    }
}
