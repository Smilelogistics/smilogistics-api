<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function storeQuote(Request $request)
    {
        $validated = $request->validate([
            'from_address' => 'required|string|max:255',
            'to_address' => 'required|string|max:255',
            'distance_km' => 'required|numeric|min:0',
            'weight_kg' => 'required|numeric|min:0.1',
            'service_type' => 'required|in:standard,express,same-day',
            'delivery_cost' => 'required|numeric|min:0',
            'estimated_duration' => 'required|string'
        ]);
        
        // Calculate cost breakdown
        $baseRate = $this->calculateBaseRate($validated['service_type'], $validated['distance_km']);
        $weightSurcharge = $this->calculateWeightSurcharge($validated['weight_kg'], $baseRate);
        $serviceFee = $this->getServiceFee($validated['service_type']);
        
        $shipment = ConsolidateShipment::create([
            'origin_warehouse' => $validated['from_address'],
            'destination_warehouse' => $validated['to_address'],
            'total_weight' => $validated['weight_kg'],
            'distance_km' => $validated['distance_km'],
            'base_rate' => $baseRate,
            'weight_surcharge' => $weightSurcharge,
            'service_fee' => $serviceFee,
            'service_type' => $validated['service_type'],
            'total_shipping_cost' => $validated['delivery_cost'],
            'estimated_duration' => $validated['estimated_duration'],
            'status' => 'quote'
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $shipment
        ]);
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
    public function assignDriver(Request $request)
    {
        $getDriver = bikes::where('status', 'available')->first();


        $delivery = Delivery::update([
            'driver_id' => $getDriver->driver_id,
            'assigned_at' => now(),
            'driver_status' => 'pending'
        ]);


        $driver = User::find($request->driver_id);
        $driver->notify(new ShipmentAssigned($shipment));

        return response()->json([
            'success' => true,
            'data' => $delivery // return the assigned driver
        ]);
    }

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
