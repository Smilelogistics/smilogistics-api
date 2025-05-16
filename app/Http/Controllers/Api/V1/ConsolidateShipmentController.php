<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ConsolidateShipment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Models\ConsolidateShipmentDoc;
use Illuminate\Support\Facades\Validator;
use App\Models\ConsolidateShipmentCharges;
use App\Mail\ConsolidateShipmentCustomerMail;
use App\Mail\ConsolidateShipmentRecieverMail;
use App\Http\Requests\StoreConsolidateShipmentRequest;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use App\Notifications\DriverAcceptConsolidationDeliveryNotification;

class ConsolidateShipmentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        $consolidateShipments = ConsolidateShipment::where('branch_id', $branchId)
        ->with('customer.user', 'carrier', 'driver.user')
        ->latest()
        ->get();
        return response()->json(['consolidateShipments' => $consolidateShipments], 200);
    }
    public function show($id)
    {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        
        $consolidateShipment = ConsolidateShipment::where('branch_id', $branchId)
            ->where('id', $id)
            ->with('customer', 'carrier', 'driver', 'documents')
            ->firstOrFail();

        return response()->json(['consolidateShipment' => $consolidateShipment], 200);
    }

    public function store(StoreConsolidateShipmentRequest $request)
    {
       // dd($request->all());
        $validatedData = $request->validated();
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        $branch = $user->branch()->with('user')->first();

        $branch_prfx = $user->branch ? $user->branch->parcel_tracking_prefix : null;
        $shipment_prefix = $branch_prfx ? $branch_prfx : '';
        $branchId = $user->branch ? $user->branch->id : null;
        $customerId = $user->customer ? $user->customer->id : null;
        $handling_fee = $user->branch->handling_fee;
        $total_shipping_cost = $validatedData['total_weight'] * $handling_fee;
        
    	//dd(ConsolidateShipment::generateTrackingNumber());
        $consolidateShipment = ConsolidateShipment::create([
            'user_id' => $user->id,
            'branch_id' => $branchId,
            'customer_id' => $customerId,
            //'carrier_id' => $validatedData['carrier_id'],
            'driver_id' => $validatedData['driver_id'],
            'consolidate_tracking_number' => $shipment_prefix . ConsolidateShipment::generateTrackingNumber() ?? null,
            'consolidation_type' => $validatedData['consolidation_type'],
            'consolidated_for' => $validatedData['consolidated_for'],
            'customer_email' => $validatedData['customer_email'],
            'customer_phone' => $validatedData['customer_phone'],
            'receiver_name' => $validatedData['receiver_name'],
            'receiver_address' => $validatedData['receiver_address'],
            'receiver_email' => $validatedData['receiver_email'],
            'receiver_phone' => $validatedData['receiver_phone'],
            'origin_warehouse' => $validatedData['origin_warehouse'],
            'destination_warehouse' => $validatedData['destination_warehouse'],
            'expected_departure_date' => $validatedData['expected_departure_date'],
            'expected_arrival_date' => $validatedData['expected_arrival_date'],
            'total_weight' => $validatedData['total_weight'],
            'total_shipping_cost' => $total_shipping_cost,
            'payment_status' => $validatedData['payment_status'],
            'payment_method' => $validatedData['payment_method'],
        ]);

    
          if (!empty($validatedData['charge_type']) && is_array($validatedData['charge_type'])) {
                $total = 0;
                $totalDiscount = 0;

                // Process each charge
                foreach ($validatedData['charge_type'] as $i => $chargeType) {
                    $amount = (float)($validatedData['amount'][$i] ?? 0);
                    $discount = (float)($validatedData['discount'][$i] ?? 0);
                    
                    // Calculate totals
                    $total += $amount;
                    $totalDiscount += $discount;
                    $net_total = $total - $totalDiscount;
            
                    // Create charge record
                    ConsolidateShipmentCharges::create([
                        'shipment_id' => $consolidateShipment->id,
                        'branch_id' => $branchId ?? null,
                        'charge_type' => $chargeType,
                        'comment' => $validatedData['comment'][$i] ?? null,
                        'units' => $validatedData['units'][$i] ?? null,
                        'rate' => $validatedData['rate'][$i] ?? null,
                        'amount' => $amount,
                        'discount' => $discount,
                        'internal_notes' => $validatedData['internal_notes'][$i] ?? null,
                        'total' => $total,
                        'total_discount' => $totalDiscount,
                        'net_total' => $total - $totalDiscount
                    ]);
                }
            
                // Update shipment with calculated totals
                    $consolidateShipment->update([
                    'consolidate_total_charges' => $total,
                    'consolidate_net_total_charges' => $net_total,
                    'consolidate_total_discount_charges' => $totalDiscount
                ]);
            }

       // dd($consolidateShipment);

        // if($request->hasFile('proof_of_delivery_path')){
        //     $file = $request->file('proof_of_delivery_path');
        //     $uploadedFile = Cloudinary::upload($file->getRealPath(), [
        //         'folder' => 'consolidate_shipment'
        //     ]);

        //     $consolidateShipment->consolidateShipmentDocs()->create([
        //         'proof_of_delivery_path' => $uploadedFile->getSecurePath(),
        //         //'public_id' => $uploadedFile->getPublicId()
        //     ]);
        // }

        if($request->hasFile('proof_of_delivery_path')) {
            $uploadedFile = Cloudinary::upload($request->file('proof_of_delivery_path')->getRealPath(), [
                'folder' => 'Smile_logistics/consolidate_shipment'
            ]);
            
            ConsolidateShipmentDoc::create([
                'consolidate_shipment_id' => $consolidateShipment->id,
                'proof_of_delivery_path' => $uploadedFile->getSecurePath(),
                'public_id' => $uploadedFile->getPublicId()
            ]);
        }

        if($request->hasFile('invoice_path')){
            $uploadedFile = Cloudinary::upload($request->file('invoice_path')->getRealPath(), [
                'folder' => 'Smile_logistics/consolidate_shipment'
            ]);
            
            ConsolidateShipmentDoc::create([
                'consolidate_shipment_id' => $consolidateShipment->id,
                'invoice_path' => $uploadedFile->getSecurePath(),
                'public_id' => $uploadedFile->getPublicId()
            ]);
        }

        if ($request->hasFile('file_path')) {
            //dd($request->file('file_path'));
            $files = $request->file('file_path');
        
            // Normalize to array (even if it's one file)
            $files = is_array($files) ? $files : [$files];
        
            foreach ($files as $file) {
                if ($file->isValid()) {
                    $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                        'folder' => 'Smile_logistics/consolidate_shipment'
                    ]);
        
                    ConsolidateShipmentDoc::create([
                        'consolidate_shipment_id' => $consolidateShipment->id,
                        'file_path' => $uploadedFile->getSecurePath(),
                        'public_id' => $uploadedFile->getPublicId()
                    ]);
                }
            }
        }
    //dd($consolidateShipment->receiver_email);
        Mail::to($consolidateShipment->customer_email)->send(new ConsolidateShipmentCustomerMail($consolidateShipment, $branch));
        Mail::to($consolidateShipment->receiver_email)->send(new ConsolidateShipmentRecieverMail($consolidateShipment, $branch));

        return response()->json([
            'success' => true,
            'message' => 'Consolidate Shipment created successfully',
            'data' => $consolidateShipment
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        $branch = $user->branch()->with('user')->first();

        $branch_prfx = $user->branch ? $user->branch->parcel_tracking_prefix : null;
        $shipment_prefix = $branch_prfx ? $branch_prfx : '';
        $branchId = $user->branch ? $user->branch->id : null;
        $customerId = $user->customer ? $user->customer->id : null;
        
        try{
            $validatedData = Validator::make($request->all(), [
                'consolidation_type' => 'sometimes|string|nullable',
                'consolidated_for' => 'sometimes|string|nullable',
                'total_weight' => 'sometimes|numeric|nullable',
                'receiver_phone' => 'sometimes|string|nullable',
                'receiver_email' => 'sometimes|email|nullable',
                'origin_warehouse' => 'sometimes|string|nullable',
                'destination_warehouse' => 'sometimes|string|nullable',
                'expected_departure_date' => 'sometimes|date|nullable',
                'expected_arrival_date' => 'sometimes|date|nullable',
                'total_shipping_cost' => 'sometimes|numeric|nullable',
                'payment_status' => 'sometimes|string|nullable',
                'payment_method' => 'sometimes|string|nullable',

                'proof_of_delivery_path' => 'sometimes|nullable|file|mimes:pdf,jpg,png|max:2048',
                'invoice_path' => 'sometimes|nullable|file|mimes:pdf,jpg,png|max:2048',
                'file_path.*' => 'nullable|file|mimes:pdf,jpg,png,jpeg,doc,docx|max:2048',
                'file_path' => 'sometimes|array',
            ]);

            if($validatedData->fails()){
                return response()->json([
                    'success' => false,
                    'message' => $validatedData->errors(),
                ]);
            }

            DB::beginTransaction();
            $validatedShipment = $validatedData->validated();

            $consolidateShipment = ConsolidateShipment::findOrFail($id);

            $consolidateShipment->update([
                'consolidation_type' => $validatedShipment['consolidation_type'],
                'consolidated_for' => $validatedShipment['consolidated_for'],
                'total_weight' => $validatedShipment['total_weight'],
                'receiver_phone' => $validatedShipment['receiver_phone'],
                'receiver_email' => $validatedShipment['receiver_email'],
                'origin_warehouse' => $validatedShipment['origin_warehouse'],
                'destination_warehouse' => $validatedShipment['destination_warehouse'],
                'expected_departure_date' => $validatedShipment['expected_departure_date'],
                'expected_arrival_date' => $validatedShipment['expected_arrival_date'],
                'total_shipping_cost' => $validatedShipment['total_shipping_cost'],
                'payment_status' => $validatedShipment['payment_status'],
                'payment_method' => $validatedShipment['payment_method'],
            ]);

             // Handle charges
                if (!empty($validatedShipment['charge_type']) && is_array($validatedShipment['charge_type'])) {
                    $this->processCharges($consolidateShipment, $validatedShipment, $branchId);
                }



            if ($request->hasFile('proof_of_delivery_path')) {
                $uploadedFile = Cloudinary::upload($request->file('proof_of_delivery_path')->getRealPath(), [
                    'folder' => 'Smile_logistics/consolidate_shipment'
                ]);
                $consolidateShipment->documents()->updateOrCreate(
                    ['consolidate_shipment_id' => $consolidateShipment->id],
                    ['proof_of_delivery_path' => $uploadedFile->getSecurePath(),
                    'public_id' => $uploadedFile->getPublicId()
                    
                    ]
                );
            }
    
            if ($request->hasFile('invoice_path')) {
                $uploadedFile = Cloudinary::upload($request->file('invoice_path')->getRealPath(), [
                    'folder' => 'Smile_logistics/consolidate_shipment'
                ]);
                $consolidateShipment->documents()->updateOrCreate(
                    ['consolidate_shipment_id' => $consolidateShipment->id],
                    ['invoice_path' => $uploadedFile->getSecurePath(),
                    'public_id' => $uploadedFile->getPublicId()
                    ]
                );
            }
    
            if ($request->hasFile('file_path')) {
                //dd($request->file('file_path'));
                $files = $request->file('file_path');
            
                // Normalize to array (even if it's one file)
                $files = is_array($files) ? $files : [$files];
            
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                            'folder' => 'Smile_logistics/consolidate_shipment'
                        ]);
            
                        $consolidateShipment->documents()->updateOrCreate(
                            ['consolidate_shipment_id' => $consolidateShipment->id],
                            ['file_path' => $uploadedFile->getSecurePath(),
                            'public_id' => $uploadedFile->getPublicId()
                        ]);
                    }
                }
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Consolidate Shipment updated successfully',
                'data' => $consolidateShipment
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
     
    }

    protected function processCharges($consolidateShipment, $validatedData, $branchId)
    {
        // Debug the incoming data
        \Log::debug('Processing charges with data:', [
            'charge_type' => $validatedData['charge_type'] ?? null,
            'rate' => $validatedData['rate'] ?? null,
            'amount' => $validatedData['amount'] ?? null,
            'units' => $validatedData['units'] ?? null,
        ]);

        $total = 0;
        $totalDiscount = 0;
        
        // Delete existing charges
        ShipmentCharge::where('shipment_id', $shipment->id)->delete();

        // Convert all fields to arrays and ensure consistent length
        $chargeData = [
            'charge_type' => array_values((array)($validatedShipment['charge_type'] ?? [])),
            'comment' => array_values((array)($validatedShipment['comment'] ?? [])),
            'units' => array_values((array)($validatedShipment['units'] ?? [])),
            'rate' => array_values((array)($validatedShipment['rate'] ?? [])),
            'amount' => array_values((array)($validatedShipment['amount'] ?? [])),
            'discount' => array_values((array)($validatedShipment['discount'] ?? [])),
            'internal_notes' => array_values((array)($validatedShipment['internal_notes'] ?? [])),
        ];

        // Get the count based on charge_type (assuming it's the primary field)
        $chargeCount = count($chargeData['charge_type']);

        // Process each charge
        for ($i = 0; $i < $chargeCount; $i++) {
            try {
                $amount = (float)($chargeData['amount'][$i] ?? 0);
                $discount = (float)($chargeData['discount'][$i] ?? 0);
                
                $total += $amount;
                $totalDiscount += $discount;

                $charge = ConsolidateShipmentCharges::create([
                    'shipment_id' => $shipment->id,
                    'branch_id' => $branchId,
                    'charge_type' => $chargeData['charge_type'][$i] ?? null,
                    'comment' => $chargeData['comment'][$i] ?? null,
                    'units' => $chargeData['units'][$i] ?? null,
                    'rate' => $chargeData['rate'][$i] ?? null,
                    'amount' => $amount,
                    'discount' => $discount,
                    'internal_notes' => $chargeData['internal_notes'][$i] ?? null,
                ]);

                \Log::debug('Created charge:', $charge->toArray());

            } catch (\Exception $e) {
                \Log::error('Failed to create charge:', [
                    'index' => $i,
                    'error' => $e->getMessage(),
                    'data' => [
                        'charge_type' => $chargeData['charge_type'][$i] ?? null,
                        'rate' => $chargeData['rate'][$i] ?? null,
                        'amount' => $chargeData['amount'][$i] ?? null,
                    ]
                ]);
                continue;
            }
        }

        // Update shipment totals
        $consolidateShipment->update([
            'net_total_charges' => $total - $totalDiscount,
            'total_discount_charges' => $totalDiscount,
            'total_charges' => $total
        ]);

        \Log::debug('Updated shipment totals:', [
            'net_total_charges' => $total - $totalDiscount,
            'total_discount_charges' => $totalDiscount,
            'total_charges' => $total
        ]);
    }

    public function getPendingConslidatedDelivery()
    {
        $user = auth()->user();
        $consolidateShipment = ConsolidateShipment::with('driver')->where('accepted_status', 'pending')->first();

        return response()->json([
            'success' => true,
            'message' => 'Consolidate Shipment fetched successfully',
            'data' => $consolidateShipment
        ]);
    }

    public function getAcceptedConslidatedDelivery()
    {
        $user = auth()->user();
        $consolidateShipment = ConsolidateShipment::with('driver')->where('accepted_status', 'accepted')->get();

        return response()->json([
            'success' => true,
            'message' => 'Consolidate Shipment fetched successfully',
            'data' => $consolidateShipment
        ]);
    }

    public function acceptConsolidatedDelivery($id)
    {
        $driver = auth()->user();
        $consolidateShipment = ConsolidateShipment::with(['user'])->where('id', $id)->first();
        $consolidateShipment->update(['accepted_status' => 'accepted']);
    
        // Get the user who created the shipment
        if ($consolidateShipment->user_id) {
            $userToNotify = User::find($consolidateShipment->user_id);
            $userToNotify->notify(new DriverAcceptConsolidationDeliveryNotification($consolidateShipment, $driver));
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Consolidate Shipment accepted successfully',
            'data' => $consolidateShipment
        ]);
    }


    public function getPayments()
    {
        $user = auth()->user();
        $payments = ConsolidateShipment::with('branch','user')->where('payment_status', 'paid')->get();

        return response()->json($payments);
    }

    public function showPayment($id)
    {
        $payment = ConsolidateShipment::with('branch','user')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Consolidate Shipment fetched successfully',
            'data' => $payment
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
