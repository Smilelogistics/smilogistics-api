<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\ConsolidateShipment;
use App\Http\Controllers\Controller;

class ConsolidateShipmentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        $consolidateShipments = ConsolidateShipment::where('branch_id', $branchId)->with('customer', 'carrier', 'driver')->latest()->get();
        return response()->json(['consolidateShipments' => $consolidateShipments], 200);
    }
    public function show($id)
    {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        $consolidateShipment = ConsolidateShipment::where('branch_id', $branchId)->with('customer', 'carrier', 'driver')->findOrFail($id);
        return response()->json(['consolidateShipment' => $consolidateShipment], 200);
    }
    public function store(StoreConsolidateShipmentRequest $request)
    {
        $validatedData = $request->validated();
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;

        $consolidateShipment = ConsolidateShipment::create([
            'user_id' => $user->id,
            'branch_id' => $branchId,
            'customer_id' => $validatedData['customer_id'],
            'carrier_id' => $validatedData['carrier_id'],
            'driver_id' => $validatedData['driver_id'],
            'consolidation_type' => $validatedData['consolidation_type'],
            'consolidated_for' => $validatedData['consolidated_for'],
            'customer_contact' => $validatedData['customer_contact'],
            'receiver_name' => $validatedData['receiver_name'],
            'receiver_address' => $validatedData['receiver_address'],
            'receiver_contact' => $validatedData['receiver_contact'],
            'origin_warehouse' => $validatedData['origin_warehouse'],
            'destination_warehouse' => $validatedData['destination_warehouse'],
            'expected_departure_date' => $validatedData['expected_departure_date'],
            'expected_arrival_date' => $validatedData['expected_arrival_date'],
            'total_weight' => $validatedData['total_weight'],
            'total_shipping_cost' => $validatedData['total_shipping_cost'],
            'payment_status' => $validatedData['payment_status'],
            'payment_method' => $validatedData['payment_method'],
            'accepted_status' => $validatedData['accepted_status'],
            'status' => $validatedData['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consolidate Shipment created successfully',
            'data' => $consolidateShipment
        ]);
    }

    public function update(Request $request, $id)
    {
        $consolidateShipment = ConsolidateShipment::findOrFail($id);
        $consolidateShipment->update($request->all());
        return response()->json([
            'success' => true,
            'message' => 'Consolidate Shipment updated successfully',
            'data' => $consolidateShipment
        ]);
    }
    
    public function destroy($id)
    {
        $consolidateShipment = ConsolidateShipment::findOrFail($id);
        $consolidateShipment->delete();
        return response()->json([
            'success' => true,
            'message' => 'Consolidate Shipment deleted successfully',
            'data' => $consolidateShipment
        ]);
    }
}
