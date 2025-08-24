<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Shipment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ConsolidatedShipment;
use App\Models\ShipmentConsolidation;

class ConsolidatedShipmentController extends Controller
{
    public function consolidateShipment(Request $request) {
        $user = auth()->user();
        $branch_prfx = $user->branch ? $user->branch->parcel_tracking_prefix : null;

        //dd($branch_prfx);

        $branchId = $user->branch ? $user->branch->id : null;

        //dd($request->all());

        $validatedData = $request->validate([
            'shipment_ids' => 'required|array',
            'shipment_ids.*' => 'exists:shipments,id',
            'origin' => 'required|string',
            'destination' => 'nullable|string',
            'customer_fname' => 'required|string',
            'customer_lname' => 'required|string',
            'customer_mname' => 'nullable|string',
            'customer_address' => 'required|string',
            'customer_country' => 'nullable|string',
            'customer_state' => 'nullable|string',
            'customer_zip' => 'nullable|string',
            'customer_city' => 'nullable|string',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string',
            'recipient_fname' => 'nullable|string',
            'recipient_lname' => 'nullable|string',
            'recipient_mname' => 'nullable|string',
            'recipient_email' => 'nullable|email',
            'recipient_phone' => 'nullable|string',
            'recipient_address' => 'nullable|string',
            'recipient_country' => 'nullable|string',
            'recipient_state' => 'nullable|string',
            'recipient_zip' => 'nullable|string',
            'recipient_city' => 'nullable|string',
            'shipping_method' => 'nullable|string',
            'type_of_packaging' => 'nullable|string',
            'courier_company' => 'nullable|string',
            'service_mode' => 'nullable|string',
            'delivery_time' => 'nullable|string',
            'delivery_date' => 'nullable|date',
            'payment_method' => 'nullable|string',
            'delivery_status' => 'nullable|string',
            'images' => 'nullable|string',
        ]);
        
        $shipmentIds = $request->shipment_ids;
        $shipments = Shipment::whereIn('id', $shipmentIds)
            ->where('shipment_status', 'pending')
            ->get();

            if ($shipments->count() > 0) {
            // Create a new consolidated shipment
            $consolidatedShipment = ConsolidatedShipment::create([
                'tracking_number' => $branch_prfx . ConsolidatedShipment::generateTrackingNumber(),
                'origin' => $validatedData['origin'],
                'destination' => $validatedData['destination'],
                'status' => 'pending',
                'total_weight' => $shipments->sum('total_weight'),
                'total_cost' => $shipments->sum('total_cost'),
                'customer_fname' => $validatedData['customer_fname'],
                'customer_lname' => $validatedData['customer_lname'],
                'customer_mname' => $validatedData['customer_mname'] ?? null,
                'customer_address' => $validatedData['customer_address'],
                'customer_country' => $validatedData['customer_country'],
                'customer_state' => $validatedData['customer_state'],
                'customer_zip' => $validatedData['customer_zip'],
                'customer_city' => $validatedData['customer_city'],
                'customer_email' => $validatedData['customer_email'],
                'customer_phone' => $validatedData['customer_phone'],
                'recipient_fname' => $validatedData['recipient_fname'],
                'recipient_lname' => $validatedData['recipient_lname'],
                'recipient_mname' => $validatedData['recipient_mname'] ?? null,
                'recipient_email' => $validatedData['recipient_email'],
                'recipient_phone' => $validatedData['recipient_phone'],
                'recipient_address' => $validatedData['recipient_address'],
                'recipient_country' => $validatedData['recipient_country'],
                'recipient_state' => $validatedData['recipient_state'],
                'recipient_zip' => $validatedData['recipient_zip'],
                'recipient_city' => $validatedData['recipient_city'],
                'shipping_method' => $validatedData['shipping_method'],
                'type_of_packaging' => $validatedData['type_of_packaging'],
                'courier_company' => $validatedData['courier_company'],
                'service_mode' => $validatedData['service_mode'],
                'delivery_time' => $validatedData['delivery_time'],
                'delivery_date' => $validatedData['delivery_date'],
                'payment_method' => $validatedData['payment_method'],
                'delivery_status' => $validatedData['delivery_status'],
                'images' => $validatedData['images'] ?? null,
            ]);
        
            // Attach shipments to consolidated shipment
            foreach ($shipments as $shipment) {
                ShipmentConsolidation::create([
                    'consolidated_shipment_id' => $consolidatedShipment->id,
                    'shipment_id' => $shipment->id,
                ]);
            }
            

            //send shipement mail
        
            return response()->json([
                'message' => 'Consolidation successful!',
                'tracking_number' => $consolidatedShipment->tracking_number,
                'consolidated_shipment' => $consolidatedShipment
            ], 201);
        } else {
            return response()->json(['message' => 'No shipments found for consolidation.'], 404);
        }
    }
    public function getConsolidatedShipment(Request $request) {
        $consolidatedShipments = ConsolidatedShipment::with('shipments')->get();
    
        if ($consolidatedShipments->isEmpty()) {
            return response()->json(['message' => 'No consolidated shipments found.'], 404);
        }
    
        return response()->json([
            'consolidated_shipments' => $consolidatedShipments
        ], 200);
    }

    public function pendingConsolidatedShipment() {
        $consolidatedShipments = ConsolidatedShipment::with('shipments')->where('status', 'pending')->get();
    
        if ($consolidatedShipments->isEmpty()) {
            return response()->json(['message' => 'No pending consolidated shipments found.'], 404);
        }
    
        return response()->json([
            'consolidated_shipments' => $consolidatedShipments
        ], 200);
    }

    public function getConsolidatedShipmentByCustomrEmail(Request $request) {
        $consolidatedShipments = ConsolidatedShipment::with('shipments')->where('customer_email', $request->customer_email)->get();
    
        if ($consolidatedShipments->isEmpty()) {
            return response()->json(['message' => 'No consolidated shipments found.'], 404);
        }
    
        return response()->json([
            'consolidated_shipments' => $consolidatedShipments
        ], 200);
    }
    
}
