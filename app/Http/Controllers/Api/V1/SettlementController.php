<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use App\Models\Driver;
use App\Models\Carrier;
use App\Models\Settlement;
use Illuminate\Http\Request;
use App\Models\SettlementDoc;
use App\Models\SettlementEscrow;
use App\Models\SettlementPayment;
use Illuminate\Support\Facades\DB;
use App\Models\SettlementDeduction;
use App\Http\Controllers\Controller;
use App\Notifications\SettlementNotification;
use App\Http\Requests\CreateSettlementRequest;
use App\Http\Requests\UpdateSettlementRequest;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class SettlementController extends Controller
{
    public function index()
    {
          $user = auth()->user();
        $branchId = auth()->user()->getBranchId();
        //$customerId = $user->customer ? $user->customer->id : null;
        $driverId = $user->driver ? $user->driver->id : null;
        //dd($branchId, $customerId);
        if ($user->hasRole('businessadministrator')) {
            $settlements = Settlement::where('branch_id', $branchId)
                            ->with('user','driver', 'truck', 'carrier', 'settlementDocs', 'settlementDeduction', 'settlementPayment', 'settlementEscrow')
                            ->latest()
                            ->get();
        }
        // elseif ($user->hasRole('customer')) {
        //     $settlements = Settlement::where('customer_id', $customerId)
        //                     ->with('branch', 'user', 'settlementDocs', 'settlementDeduction', 'settlementPayment', 'settlementEscrow')
        //                     ->latest()
        //                     ->get();
        // } 
        
        elseif ($user->hasRole('driver')) {
            $settlements = Settlement::where('driver_id', $driverId)
                            ->with('branch', 'user', 'settlementDocs', 'settlementDeduction', 'settlementPayment', 'settlementEscrow')
                            ->latest()
                            ->get();
        }
        else {
            $settlements = collect();
        }

        return response()->json(['settlements' => $settlements], 200);
    }

    public function show($id)
    {
        $settlement = Settlement::with(['branch', 'user', 'driver', 'settlementDocs', 'settlementDeduction', 'settlementPayment', 'settlementEscrow'])->findOrFail($id);
        return response()->json($settlement);
    }

    public function store(CreateSettlementRequest $request)
    {
        
        $branchId = auth()->user()->getBranchId();
        $branch = Branch::find($branchId);

        $validatedData = $request->validated();

          if (isset($validatedData['tags'])) {
            if (is_string($validatedData['tags'])) {
                $tagsArray = explode(',', $validatedData['tags']);
            } 
            elseif (is_string($validatedData['tags']) && json_decode($validatedData['tags'])) {
                $tagsArray = json_decode($validatedData['tags'], true);
            }
            else {
                $tagsArray = $validatedData['tags'];
            }
            
            $tagsArray = array_values(array_filter(array_map('trim', $tagsArray)));
            $validatedData['tags'] = !empty($tagsArray) ? $tagsArray : null;
        }

        DB::beginTransaction();

        try
        {
            $settlement = Settlement::create([
                'branch_id' => $branchId,
                //'customer_id' => $validatedData['customer_id'],
                'carrier_id' => $validatedData['carrier_id'],
                'truck_id' => $validatedData['truck_id'],
                'driver_id' => $validatedData['driver_id'],
                'office' => $validatedData['office'],
                'settlement_date' => $validatedData['settlement_date'],
                'settlement_type' => $validatedData['settlement_type'],
                'week_from' => $validatedData['week_from'],
                'week_to' => $validatedData['week_to'],
                'payee' => $validatedData['payee'],
                'payee_note' => $validatedData['payee_note'],
                'payment_method' => $validatedData['payment_method'],
                'check_payment_reference' => $validatedData['check_payment_reference'],
                'payment_date' => $validatedData['payment_date'],
                'payment_note' => $validatedData['payment_note'],
                'internal_notes' => $validatedData['internal_notes'],
                'tags' => $validatedData['tags']
            ]);
            

              if ($request->hasFile('file_path')) {
                //dd($request->file('file_path'));
                $files = $request->file('file_path');
            
                // Normalize to array (even if it's one file)
                $files = is_array($files) ? $files : [$files];
            
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                            'folder' => 'Smile_logistics/Settlement',
                        ]);
            
                        SettlementDoc::create([
                            'settlement_id' => $settlement->id,
                            'file_path' => $uploadedFile->getSecurePath(),
                            'public_id' => $uploadedFile->getPublicId()
                        ]);
                    }
                }
            }

             if (!empty($validatedData['payment_type']) && is_array($validatedData['payment_type'])) {
                $total = 0;
                $totalDiscount = 0;

                // Process each payment
                foreach ($validatedData['payment_type'] as $i => $paymentType) {
                    $amount = (float)($validatedData['amount'][$i] ?? 0);
                    $discount = (float)($validatedData['discount'][$i] ?? 0);
                    
                    // Calculate totals
                    $total += $amount;
                    $totalDiscount += $discount;
            
                    // Create payment record
                    SettlementPayment::create([
                        'settlement_id' => $settlement->id,
                        //'branch_id' => $branchId ?? null,
                        'payment_type' => $paymentType,
                        'comment' => $validatedData['comment'][$i] ?? null,
                        'units' => $validatedData['units'][$i] ?? null,
                        'rate' => $validatedData['rate'][$i] ?? null,
                        'amount' => $amount,
                        // 'payment_internal_notes' => $validatedData['payment_internal_notes'][$i] ?? null,
                        'payment_total' => $total,
                        'payment_discount' => $totalDiscount,
                        'net_total_payments' => $total - $totalDiscount
                    ]);
                }
            
                // Update shipmsettlement payment with calculated totals
                $settlement->update([
                    'total_payments' => $total,
                    'total_payments_discount' => $totalDiscount,
                    'net_total_payments' => $total - $totalDiscount
                ]);
            }

            //process deduction
            if (!empty($validatedData['deduction_amount']) && is_array($validatedData['deduction_amount'])) {
                $total = 0;

                // Process each payment
                foreach ($validatedData['deduction_amount'] as $i => $deductionAmount) {
                    $amount = (float)($validatedData['deduction_amount'][$i] ?? 0);
                    
                    // Calculate totals
                    $total += $amount;
            
                    // Create payment record
                    SettlementDeduction::create([
                        'settlement_id' => $settlement->id,
                        //'branch_id' => $branchId ?? null,
                        'deduction_amount' => $deductionAmount,
                        'deduction_type' => $validatedData['deduction_type'][$i] ?? null,
                        'deduction_comment' => $validatedData['deduction_comment'][$i] ?? null,
                        'deduction_note' => $validatedData['deduction_note'][$i] ?? null,
                        'total_deductions' => $total ?? null
                    ]);
                }
            
                // Update settlement deduction with calculated totals
                $settlement->update([
                    'total_deductions' => $total
                ]);
            }

            //escrow release
            if (!empty($validatedData['escrow_release_amount']) && is_array($validatedData['escrow_release_amount'])) {
                $total = 0;
                
                // Process each Escrow release
                foreach ($validatedData['escrow_release_amount'] as $i => $amountRelease) {
                    $amount = (float)($validatedData['escrow_release_amount'][$i] ?? 0);
                    
                    // Calculate totals
                    $total += $amount;
            
                    // Create payment record
                    SettlementEscrow::create([
                        'settlement_id' => $settlement->id,
                        //'branch_id' => $branchId ?? null,
                        'escrow_release_amount' => $amountRelease,
                        'escrow_release_account' => $validatedData['escrow_release_account'][$i] ?? null,
                        'escrow_release_comment' => $validatedData['escrow_release_comment'][$i] ?? null,
                        'escrow_release_note' => $validatedData['escrow_release_note'][$i] ?? null,
                        'total_escrow_release' => $total ?? null
                    ]);
                }
            
                // Update settlement deduction with calculated totals
                $settlement->update([
                    'total_escrow_release' => $total
                ]);
            }

            //send notification
            if($request->has('driver_id')) {
                $driver = Driver::with('user')->find($request->driver_id);
                $driver->user->notify(new SettlementNotification($settlement, $branch));
            }
            if($request->has('customer_id')) {
                $customer = Customer::with('user')->find($request->customer_id);
                $customer->user->notify(new SettlementNotification($settlement, $branch));
            }

            if ($request->has('carrier_id')) {
            $carrier = Carrier::find($request->carrier_id);

            if ($carrier && $carrier->email) {
                Mail::to($carrier->email)->send(new CarrierSettlementMail($settlement, $branch));
            }
        }
            // $settlement->users()->each(function ($user) use ($settlement) {
            //     $user->notify(new SettlementNotification($settlement));
            // });

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Settlement created successfully',
                'data' => $settlement
            ]);
            
        }
        catch(\Exception $e)
        {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong' . $e], 500);
        }


    }

   public function update(UpdateSettlementRequest $request, $id)
    {
        $branchId = auth()->user()->getBranchId();
        $branch = Branch::find($branchId);

        $validatedData = $request->validated();

        // Process tags similarly
        if (isset($validatedData['tags'])) {
            if (is_string($validatedData['tags'])) {
                $tagsArray = explode(',', $validatedData['tags']);
            } 
            elseif (is_string($validatedData['tags']) && json_decode($validatedData['tags'])) {
                $tagsArray = json_decode($validatedData['tags'], true);
            }
            else {
                $tagsArray = $validatedData['tags'];
            }

            $tagsArray = array_values(array_filter(array_map('trim', $tagsArray)));
            $validatedData['tags'] = !empty($tagsArray) ? $tagsArray : null;
        }

        DB::beginTransaction();

        try {
            $settlement = Settlement::findOrFail($id);

            // Update main Settlement fields
            $settlement->update([
                'branch_id' => $branchId,
                // 'customer_id' => $validatedData['customer_id'], // Uncomment if used
                'carrier_id' => $validatedData['carrier_id'] ?? null,
                'truck_id' => $validatedData['truck_id'] ?? null,
                'driver_id' => $validatedData['driver_id'] ?? null,
                'office' => $validatedData['office'] ?? null,
                'settlement_date' => $validatedData['settlement_date'] ?? null,
                'settlement_type' => $validatedData['settlement_type'] ?? null,
                'week_from' => $validatedData['week_from'] ?? null,
                'week_to' => $validatedData['week_to'] ?? null,
                'payee' => $validatedData['payee'] ?? null,
                'payee_note' => $validatedData['payee_note'] ?? null,
                'payment_method' => $validatedData['payment_method'] ?? null,
                'check_payment_reference' => $validatedData['check_payment_reference'] ?? null,
                'payment_date' => $validatedData['payment_date'] ?? null,
                'payment_note' => $validatedData['payment_note'] ?? null,
                'internal_notes' => $validatedData['internal_notes'] ?? null,
                'tags' => $validatedData['tags'] ?? null
            ]);

            // Handle file uploads if any new files sent
            if ($request->hasFile('file_path')) {
                $files = $request->file('file_path');
                $files = is_array($files) ? $files : [$files];

                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                            'folder' => 'Smile_logistics/Settlement',
                        ]);

                        SettlementDoc::create([
                            'settlement_id' => $settlement->id,
                            'file_path' => $uploadedFile->getSecurePath(),
                            'public_id' => $uploadedFile->getPublicId()
                        ]);
                    }
                }
            }

            // Update payments: Clear old payments and recreate
            SettlementPayment::where('settlement_id', $settlement->id)->delete();
            $totalPayments = 0;
            $totalDiscount = 0;

            if (!empty($validatedData['payment_type']) && is_array($validatedData['payment_type'])) {
                foreach ($validatedData['payment_type'] as $i => $paymentType) {
                    $amount = (float)($validatedData['amount'][$i] ?? 0);
                    $discount = (float)($validatedData['payment_discount'][$i] ?? 0);

                    $totalPayments += $amount;
                    $totalDiscount += $discount;

                    SettlementPayment::create([
                        'settlement_id' => $settlement->id,
                        'payment_type' => $paymentType,
                        'comment' => $validatedData['comment'][$i] ?? null,
                        'units' => $validatedData['units'][$i] ?? null,
                        'rate' => $validatedData['rate'][$i] ?? null,
                        'amount' => $amount,
                        'payment_discount' => $discount,
                        'payment_total' => $totalPayments,
                        'net_total_payments' => $totalPayments - $totalDiscount,
                    ]);
                }
            }

            $settlement->update([
                'total_payments' => $totalPayments,
                'total_payments_discount' => $totalDiscount,
                'net_total_payments' => $totalPayments - $totalDiscount,
            ]);

            // Update deductions: Clear old and recreate
            SettlementDeduction::where('settlement_id', $settlement->id)->delete();
            $totalDeductions = 0;

            if (!empty($validatedData['deduction_amount']) && is_array($validatedData['deduction_amount'])) {
                foreach ($validatedData['deduction_amount'] as $i => $deductionAmount) {
                    $amount = (float)$deductionAmount;
                    $totalDeductions += $amount;

                    SettlementDeduction::create([
                        'settlement_id' => $settlement->id,
                        'deduction_amount' => $amount,
                        'deduction_type' => $validatedData['deduction_type'][$i] ?? null,
                        'deduction_comment' => $validatedData['deduction_comment'][$i] ?? null,
                        'deduction_note' => $validatedData['deduction_note'][$i] ?? null,
                        'total_deductions' => $totalDeductions,
                    ]);
                }
            }

            $settlement->update([
                'total_deductions' => $totalDeductions
            ]);

            // Update escrow releases: Clear old and recreate
            SettlementEscrow::where('settlement_id', $settlement->id)->delete();
            $totalEscrowRelease = 0;

            if (!empty($validatedData['escrow_release_amount']) && is_array($validatedData['escrow_release_amount'])) {
                foreach ($validatedData['escrow_release_amount'] as $i => $escrowAmount) {
                    $amount = (float)$escrowAmount;
                    $totalEscrowRelease += $amount;

                    SettlementEscrow::create([
                        'settlement_id' => $settlement->id,
                        'escrow_release_amount' => $amount,
                        'escrow_release_account' => $validatedData['escrow_release_account'][$i] ?? null,
                        'escrow_release_comment' => $validatedData['escrow_release_comment'][$i] ?? null,
                        'escrow_release_note' => $validatedData['escrow_release_note'][$i] ?? null,
                        'total_escrow_release' => $totalEscrowRelease,
                    ]);
                }
            }

            $settlement->update([
                'total_escrow_release' => $totalEscrowRelease
            ]);

            // Send notifications as in store
            // if ($request->has('driver_id')) {
            //     $driver = Driver::with('user')->find($request->driver_id);
            //     if ($driver && $driver->user) {
            //         $driver->user->notify(new SettlementNotification($settlement, $branch));
            //     }
            // }

            // if ($request->has('customer_id')) {
            //     $customer = Customer::with('user')->find($request->customer_id);
            //     if ($customer && $customer->user) {
            //         $customer->user->notify(new SettlementNotification($settlement, $branch));
            //     }
            // }

            // if ($request->has('carrier_id')) {
            //     $carrier = Carrier::find($request->carrier_id);
            //     if ($carrier && $carrier->email) {
            //         Mail::to($carrier->email)->send(new CarrierSettlementMail($settlement, $branch));
            //     }
            // }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Settlement updated successfully',
                'data' => $settlement
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $settlement = Settlement::findOrFail($id);
        $settlement->delete();
        return response()->json(['message' => 'Settlement deleted successfully'], 200);
    }
}
