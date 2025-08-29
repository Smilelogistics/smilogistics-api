<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Bike;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;


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
    $validator = Validator::make($request->all(), [
        'from' => 'required|string|max:255',
        'drop_off_address' => 'required|string|max:255',
        'package_description' => 'nullable|string|max:500',
        'package_weight' => 'required|numeric|min:0.1',
        'isFragile' => 'nullable|boolean',
        'service_type' => 'required|in:standard,express,same-day',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $validated = $validator->validated();

    // Calculate delivery cost
    $distanceKm = $this->calculateDistance($validated['from'], $validated['drop_off_address']);
    $baseRate = $this->calculateBaseRate($validated['service_type'], $distanceKm);
    $weightSurcharge = $this->calculateWeightSurcharge($validated['package_weight'], $baseRate);
    $serviceFee = $this->getServiceFee($validated['service_type']);
    
    $deliveryCost = $baseRate + $weightSurcharge + $serviceFee;
    $estimatedDuration = $this->calculateEstimatedDuration($distanceKm, $validated['service_type']);

    // Find nearest available bike
    $lat = $validated['latitude'];
    $lng = $validated['longitude'];
    $radiusLevels = [10, 30, 60, 90, 120, 150];

    $bike = null;

    foreach ($radiusLevels as $radius) {
        $bike = Bike::select('*')
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

        if ($bike) {
            break;
        }
    }

    if (!$bike) {
        return response()->json([
            'success' => false,
            'message' => 'No bikes available nearby.'
        ], 404);
    }

    // Create shipment
    $shipment = ConsolidateShipment::create([
        'from' => $validated['from'],
        'drop_off_address' => $validated['drop_off_address'],
        'package_description' => $validated['package_description'] ?? '',
        'package_weight' => $validated['package_weight'],
        'isFragile' => $validated['isFragile'] ?? false,
        'service_type' => $validated['service_type'],
        'distance_km' => $distanceKm,
        'delivery_cost' => $deliveryCost,
        'estimated_duration' => $estimatedDuration,
        'status' => 'pending'
    ]);

    // Assign bike to delivery
    $delivery = Delivery::create([
        'shipment_id' => $shipment->id,
        'driver_id' => $bike->driver_id,
        'bike_id' => $bike->id,
        'assigned_at' => now(),
        'driver_status' => 'pending'
    ]);

    // Update bike status
    $bike->update(['status' => 'assigned']);

    // Notify driver
    $driverUser = User::find($bike->driver_id);
    if ($driverUser) {
        $driverUser->notify(new ShipmentAssigned($shipment));
    }

    return response()->json([
        'success' => true,
        'message' => 'Delivery request created successfully',
        'data' => [
            'shipment' => $shipment,
            'delivery' => $delivery,
            'assigned_bike' => $bike
        ]
    ]);
}

private function calculateDistance($from, $to)
{
    // This is a simplified implementation - you might want to use a proper geocoding service
    // For now, we'll return a fixed distance or implement a simple calculation
    // In production, use Google Maps Distance Matrix API or similar
    
    // Placeholder implementation - returns random distance between 5-50km
    return rand(5, 50);
}

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
    return $baseRate * (ceil(($weight - 5)/5) * 0.1);
}

private function getServiceFee($serviceType)
{
    switch($serviceType) {
        case 'express': return 200;
        case 'same-day': return 300;
        default: return 100;
    }
}

private function calculateEstimatedDuration($distanceKm, $serviceType)
{
    $baseHours = $distanceKm / 30; // Assuming average speed of 30km/h
    
    switch($serviceType) {
        case 'same-day': 
            return ceil($baseHours) . ' hours';
        case 'express': 
            return ceil($baseHours * 1.5) . ' hours';
        default: 
            return ceil($baseHours * 2) . ' hours';
    }
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
