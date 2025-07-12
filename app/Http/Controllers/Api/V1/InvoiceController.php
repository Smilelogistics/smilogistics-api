<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\InvoiceDoc;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\InvoiceCharge;
use App\Models\ShipmentCharge;
use App\Mail\InvoiceCreatedMail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\InvoicePaymentRecord;
use Illuminate\Support\Facades\Mail;
use App\Mail\invoiceStatusUpdateMail;
use App\Models\InvoicePaymentRecieved;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreInvoiceRequest;
use App\Notifications\InvoiceBilltoNotification;
use App\Notifications\invoiceStatusNotification;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class InvoiceController extends Controller
{
    
    /**
     * Get all invoices with related data.
     */
    public function showAll()
    {
        // $invoices = Invoice::with(['charges', 'docs', 'payments'])->get();
        // return response()->json(['invoices' => $invoices], 200);
        $user = auth()->user();
        $branchId = auth()->user()->getBranchId();
        $customerId = $user->customer ? $user->customer->id : null;
        //dd($branchId, $customerId);
        if ($user->hasRole('businessadministrator')) {
            $invoices = Invoice::where('branch_id', $branchId)
                            ->with('customer.user', 'user')
                            ->latest()
                            ->get();
        }
        elseif ($user->hasRole('customer')) {
            $invoices = Invoice::where('customer_id', $customerId)
                            ->with('branch.user', 'user')
                            ->latest()
                            ->get();
        } else {
            $invoices = collect();
        }

        return response()->json(['invoices' => $invoices], 200);
    }

    /**
     * Get a single invoice with related data.
     */
    public function show($id)
    {
        $user = auth()->user();
        
        $invoice = Invoice::with([
            'customer.user', 
            'user', 
            'invoicedocs', 
            'invoicepayments', 
            'invoicecharges', 
            'paymentRecord',
            'branch.user'
        ]);

        if ($user->hasRole('businessadministrator')) {
            if ($user->branch) {
                $invoice->where('branch_id', $user->branch->id);
            }
        } 
        elseif ($user->hasRole('customer') && $user->customer) {
            $invoice->where('customer_id', $user->customer->id);
        } 
        else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $invoice = $invoice->findOrFail($id);
            return response()->json(['invoice' => $invoice]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }
    }

    // public function show($id)
    // {
    //     $user = auth()->user();
    //     $branchId = $user->branch ? $user->branch->id : null;
    //     $customerId = $user->customer ? $user->customer->id : null;

    //     if ($user->hasRole('businessadministrator')) {
    //         $invoice = Invoice::where('branch_id', $branchId)
    //                         ->with('customer', 'user', 'invoicedocs', 'invoicepayments', 'invoicecharges', 'branch')
    //                         ->latest()
    //                         ->findOrFail($id);
    //     }
    //     elseif ($user->hasRole('customer')) {
    //         $invoice = Invoice::where('customer_id', $customerId)
    //                         ->with('branch', 'user', 'invoicedocs', 'invoicepayments', 'invoicecharges')
    //                         ->latest()
    //                         ->findOrFail($id);
    //     } else {
    //         return response()->json(['message' => 'Unauthorized'], 401);
    //     }

    //     // $invoice = Invoice::with(['charges', 'docs', 'payments'])->findOrFail($id);
    //     return response()->json(['invoice' => $invoice], 200);
    // }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $invoices = Invoice::where('invoice_number', 'like', '%' . $query . '%')->get();
        return response()->json(['invoices' => $invoices], 200);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:awaiting payment,paid,unpaid,cancelled'
        ]);

        $invoice = Invoice::findOrFail($id);
        $previousStatus = $invoice->status;
        $invoice->status = $validated['status'];
        $invoice->save();

        // Get customer and associated user
        //dd($invoice->customer_id);
        
        $customer = Customer::find($invoice->customer_id);

        // Get the authenticated user who made the update
        $updater = auth()->user();

        // Send email notification to customer
        if ($customer) {
            $customer->notify(new invoiceStatusNotification($invoice, $previousStatus));
            //Mail::to($customerUser)->send(new invoiceStatusUpdateMail($invoice, $previousStatus));
        }

        // Send database notification to the updater
        $updater->notify(new invoiceStatusNotification($invoice, $previousStatus));

        return response()->json([
            'message' => 'Invoice status updated successfully',
            'invoice' => $invoice
        ]);
    }
    public function getCustomer()
    {
        $customers = Customer::with('branch', 'user')->get();
        return response()->json(['customers' => $customers], 200);
    }


    /**
     * Store a new invoice with related charges, documents, and payments.
     */
    public function store(StoreInvoiceRequest $request)
    {
      
        $validatedData = $request->validated();
        //dd($validatedData);
        $arrayFields = ['credit_memo', 'credit_amount', 'credit_date', 'credit_note'];
        $invoiceData = Arr::except($validatedData, ['credit_memo', 'credit_amount', 'credit_date', 'credit_note']);


        foreach ($arrayFields as $field) {
            if (isset($validatedData[$field]) && is_array($validatedData[$field])) {
                $validatedData[$field] = implode(',', $validatedData[$field]); // Convert array to a comma-separated string
            }
        }

        $user = auth()->user();
        $branchId = auth()->user()->getBranchId();
        $invoicePrefx = $user->branch ? $user->branch->invoice_prefix : null;
        $invoiceNumber = $invoicePrefx . Invoice::generateInvoiceNumber();
        DB::beginTransaction();
        try {
            
            $invoice = Invoice::create([
                'user_id' => auth()->user()->id,
                'branch_id' => $branchId,
                'invoice_number' => $invoiceNumber,
                ...$validatedData
            ]);

            
                $total = 0;
                $totalDiscount = 0;
                $remainingBalance = 0;

            if ($request->charge_type) {
                foreach ($request->charge_type as $index => $type) {
                    //$amount = (float)($request->amount[$index] ?? 0);
                    $amount = (float)($request->unit_rate[$index] ?? 0) * (float)($request->units[$index] ?? 0);
                    $discount = (float)($request->discount[$index] ?? 0);
                    
                    $total += $amount;
                    //dd($total);
                    $totalDiscount += $discount;
                    InvoiceCharge::create([
                        'invoice_id' => $invoice->id,
                        'charge_type' => $type,
                        'units' => $request->units[$index] ?? null,
                        'unit_rate' => $request->unit_rate[$index] ?? null,
                        'amount' => $amount ?? null,
                        'total_discount' => $totalDiscount,
                        'net_total' => $total - $totalDiscount
                    ]);
                }
                $invoice->update([
                    //'total' => $total, 
                    'total_discount' => $totalDiscount,
                    'net_total' => $total - $totalDiscount
                ]);
            }

            //REpayment Record
            if ($request->has('payment_amount')) {
                $totalPayments = 0;
                $totals = $total - $totalDiscount;
            
            foreach ($request->payment_amount as $index => $amount) {
                $amount_paid = (float)($amount ?? 0);
                
                if ($amount_paid <= 0) {
                    continue;
                }

                $totalPayments += $amount_paid;

                //dd($totalPayments);
                InvoicePaymentRecord::create([
                    'invoice_id' => $invoice->id,
                    'branch_id' => $branchId,
                    'payment_date' => $request->payment_date[$index] ?? null,
                    'payment_amount' => $amount_paid,
                    'paid_via' => $request->paid_via[$index] ?? null,
                    'check_number' => $request->check_number[$index] ?? null,
                    'processing_fee_per' => $request->processing_fee_per[$index] ?? null,
                    'processing_fee_flat' => $request->processing_fee_flat[$index] ?? null,
                    'payment_notes' => $request->payment_notes[$index] ?? null
                ]);
            }
          //  dd($totalPayments);
            
            if ($totalPayments > 0) {
                // Calculate remaining balance
                //$remainingBalance = $invoice->net_total - $totalPayments;
                $remainingBalance = $totals - $totalPayments;
                //dd($remainingBalance);
                
                // Determine payment status
                $paymentStatus = 'unpaid';
                if ($remainingBalance <= 0) {
                    $paymentStatus = 'paid';
                    //dd($paymentStatus);
                    // If payments exceed net_total, you might want to handle credit balance
                    $remainingBalance = 0; // Prevent negative balance if needed
                } elseif ($totalPayments > 0) {
                    $paymentStatus = 'partially_paid';
                    //dd($paymentStatus);
                }
                
                // Update invoice
                $invoice->update([
                    'total_repayment_amount' => $totalPayments,
                    'remaining_balance' => $remainingBalance,
                    'status' => $paymentStatus
                ]);
            }
        }

            // Insert into InvoiceDoc
      
            if ($request->hasFile('file')) {
                //dd($request->file('file_path'));
                $files = $request->file('file');
            
                // Normalize to array (even if it's one file)
                $files = is_array($files) ? $files : [$files];
            
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                            'folder' => 'Smile_logistics/invoice',
                        ]);
            
                        InvoiceDoc::create([
                            'invoice_id' => $invoice->id,
                            'file' => $uploadedFile->getSecurePath(),
                            'public_id' => $uploadedFile->getPublicId()
                        ]);
                    }
                }
            }

            // Insert into InvoicePayment
            if ($request->credit_amount) {
                foreach ($request->credit_amount as $index => $date) {
                    InvoicePaymentRecieved::create([
                        'invoice_id' => $invoice->id,
                        'credit_memo' => $request->credit_memo[$index] ?? null,
                        'credit_amount' => $request->credit_amount[$index] ?? null,
                        'credit_date' => $request->credit_date[$index] ?? null,
                        'credit_note' => $request->credit_note[$index] ?? null,
                        'payment_method' => $request->payment_method[$index] ?? null,
                        'amount' => $request->amount[$index] ?? null,
                    ]);
                }
            }

            $customer = Customer::with('branch')->find($request->customer_id);
            //$branch = Branch::with('branch', 'customer')->find($request->branch_id);
            $customer->notify(new InvoiceBilltoNotification($invoice));

            //dd($customer->user->email);
            //we can pass the branch data later
            Mail::to($customer->user->email)->send(new InvoiceCreatedMail($invoice));
           

            DB::commit();

            return response()->json([
                'message' => 'Invoice created successfully', 
                'remaining_balance' => $invoice->remaining_balance,
                'status' => $paymentStatus,
                'invoice' => $invoice], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create invoice', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing invoice and its related models.
     */

     public function updateBasicInvoice(Request $request, $id)
    {
        //dd($request->all());
        
         $invoice = Invoice::findOrFail($id);

         DB::beginTransaction();
        try {
            $invoice->update($request->all());

           
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Invoice basic charges updated successfully', 'invoice' => $invoice], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update invoice', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateCharges(Request $request, $id)
    {
        $request->validate([
            'charge_type' => 'required|array',
            'charge_type.*' => 'required|string|max:255',

            'units' => 'nullable|array',
            'units.*' => 'nullable|numeric|min:0',

            'unit_rate' => 'nullable|array',
            'unit_rate.*' => 'nullable|numeric|min:0',

            'rate' => 'nullable|array',
            'rate.*' => 'nullable|numeric|min:0',

            // 'amount' => 'nullable|array',
            // 'amount.*' => 'nullable|numeric|min:0',

            'discount' => 'nullable|array',
            'discount.*' => 'nullable|numeric|min:0',

            'comment' => 'nullable|array',
            'comment.*' => 'nullable|string|max:1000',

            'internal_notes' => 'nullable|array',
            'internal_notes.*' => 'nullable|string|max:1000',
        ]);

        $invoice = Invoice::findOrFail($id);

        $invoiceCharges = $this->handleCharges($request, $id, $invoice);
        $shipmentCharges = $this->handleShipmentCharges($request, $id, $invoice);

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully',
            'invoice_charges' => $invoiceCharges,
            'shipment_charges' => $shipmentCharges
        ], 200);
    }

public function updateRepayment(Request $request, $id)
{
    $validated = $request->validate([
        'paid_via' => 'nullable|array',
        'paid_via.*' => 'nullable|string|max:255',

        'payment_date' => 'required|array',
        'payment_date.*' => 'required|date',

        'payment_amount' => 'required|array',
        'payment_amount.*' => 'required|numeric|min:0',

        'check_number' => 'nullable|array',
        'check_number.*' => 'nullable|string|max:255',

        'processing_fee_per' => 'nullable|array',
        'processing_fee_per.*' => 'nullable|numeric|min:0',

        'processing_fee_flat' => 'nullable|array',
        'processing_fee_flat.*' => 'nullable|numeric|min:0',

        'payment_notes' => 'nullable|array',
        'payment_notes.*' => 'nullable|string|max:1000',
    ]);

    $invoice = Invoice::findOrFail($id);

    $payments = [];
    foreach ($validated['payment_amount'] as $index => $amount) {
        $payments[] = [
            'payment_date' => $validated['payment_date'][$index] ?? null,
            'payment_amount' => $amount ?? 0,
            'paid_via' => $validated['paid_via'][$index] ?? null,
            'check_number' => $validated['check_number'][$index] ?? null,
            'processing_fee_per' => $validated['processing_fee_per'][$index] ?? null,
            'processing_fee_flat' => $validated['processing_fee_flat'][$index] ?? null,
            'payment_notes' => $validated['payment_notes'][$index] ?? null,
        ];
    }

    $repayment = $this->handleRepaymentRecords($payments, $invoice);

    return response()->json([
        'success' => true,
        'message' => 'Invoice updated successfully',
        'repayment' => $repayment
    ], 200);
}

public function updateCreditMemo(Request $request, $id)
{
    try {
        $this->handlePayments($request, $id);
        return response()->json([
            'success' => true,
            'message' => 'Credit memo payments updated successfully.'], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to update credit memo payments.', 'error' => $e->getMessage()], 500);
    }
}


   

    public function updateDocs(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
        $this->handleDocuments($request, $id, $invoice);
        return response()->json(['message' => 'Invoice updated successfully', 'invoice' => $invoice], 200);
    }


















     //This updates everythung at once but we needed a situation where we can update each section separately as such we can swiftly update the repayment record

     public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        ///dd($request->all());

        DB::beginTransaction();
        try {
            $invoice->update($request->all());

            // Handle Charges (single or array)
            $this->handleCharges($request, $id, $invoice);
            $this->handleShipmentCharges($request, $id, $invoice = null);

            // Handle Documents (single or array)
            $this->handleDocuments($request, $id);

            // Handle Payments (single or array)
            $this->handlePayments($request, $id);

            DB::commit();
            return response()->json(['message' => 'Invoice updated successfully', 'invoice' => $invoice], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update invoice', 'error' => $e->getMessage()], 500);
        }
    }

protected function handleCharges(Request $request, $invoiceId, $invoice)
{
    if (!$request->has('charge_type')) return null;

    $charges = [
        'charge_type' => is_array($request->charge_type) ? $request->charge_type : [$request->charge_type],
        'units' => is_array($request->units ?? []) ? $request->units : [$request->units],
        'rate' => is_array($request->rate ?? []) ? $request->rate : [$request->rate],
        'amount' => is_array($request->amount ?? []) ? $request->amount : [$request->amount],
        'discount' => is_array($request->discount ?? []) ? $request->discount : [$request->discount],
        'comment' => is_array($request->comment ?? []) ? $request->comment : [$request->comment],
        'internal_notes' => is_array($request->internal_notes ?? []) ? $request->internal_notes : [$request->internal_notes],
    ];

    InvoiceCharge::where('invoice_id', $invoiceId)->delete();

    $total = 0;
    $total_discount = 0;
    $createdCharges = [];

    foreach ($charges['charge_type'] as $index => $type) {
        $units = (float)($charges['units'][$index] ?? 0);
        $rate = (float)($charges['rate'][$index] ?? 0);
        $discount = (float)($charges['discount'][$index] ?? 0);
        $amounts = $units * $rate;
        //dd($amounts);

        $total += $amounts;
        $total_discount += $discount;

        $created = InvoiceCharge::create([
            'invoice_id' => $invoiceId,
            'charge_type' => $type,
            'units' => $charges['units'][$index] ?? null,
            'unit_rate' => $charges['rate'][$index] ?? null,
            'amount' => $amounts,
            'comment' => $charges['comment'][$index] ?? null,
            'discount' => $charges['discount'][$index] ?? null,
            'internal_notes' => $charges['internal_notes'][$index] ?? null,
        ]);

        $createdCharges[] = $created;
    }

    $invoice->update([
        'net_total' => $total - $total_discount,
        'total_discount' => $total_discount
    ]);

    return [
        'invoice_charges' => $createdCharges,
        'net_total' => $total - $total_discount,
        'total_discount' => $total_discount
    ];
}


protected function handleShipmentCharges(Request $request, $invoiceId, $invoice = null)
{
    if (!$request->has('charge_type')) return null;

    $invoice = $invoice ?? Invoice::find($invoiceId);
    if (!$invoice) return null;

    $shipment = $invoice->shipment;
    if (!$shipment) return null;

    $charges = [
        'charge_type' => is_array($request->charge_type) ? $request->charge_type : [$request->charge_type],
        'units' => is_array($request->units ?? []) ? $request->units : [$request->units],
        'rate' => is_array($request->rate ?? []) ? $request->rate : [$request->rate],
        //'amount' => is_array($request->amount ?? []) ? $request->amount : [$request->amount],
        'discount' => is_array($request->discount ?? []) ? $request->discount : [$request->discount],
        'comment' => is_array($request->comment ?? []) ? $request->comment : [$request->comment],
        'internal_notes' => is_array($request->internal_notes ?? []) ? $request->internal_notes : [$request->internal_notes],
    ];

    ShipmentCharge::where('shipment_id', $shipment->id)->delete();

    $total = 0;
    $total_discount = 0;
    $createdCharges = [];

    foreach ($charges['charge_type'] as $index => $type) {
        $amount = (float)($charges['units'][$index]) * (float)($charges['rate'][$index]);
        $discount = (float)($charges['discount'][$index] ?? 0);

        $total += $amount;
        $total_discount += $discount;

        $created = ShipmentCharge::create([
            'shipment_id' => $shipment->id,
            'branch_id' => $shipment->branch_id,
            'charge_type' => $type,
            'units' => $charges['units'][$index] ?? null,
            'rate' => $charges['rate'][$index] ?? null,
            'amount' => $amount,
            'discount' => $discount,
            'comment' => $charges['comment'][$index] ?? null,
            'internal_notes' => $charges['internal_notes'][$index] ?? null,
        ]);

        $createdCharges[] = $created;
    }

    $net_total = $total - $total_discount;

    $shipment->update([
        'total_charges' => $total,
        'total_discount_charges' => $total_discount,
        'net_total_charges' => $net_total,
        'total_shipment_cost' => ($shipment->total_shipment_cost ?? 0) + $net_total,
    ]);

    return [
        'shipment_charges' => $createdCharges,
        'net_total' => $net_total,
        'total_discount' => $total_discount
    ];
}


protected function handleRepaymentRecords(array $payments, $invoice)
{
    $branchId = auth()->user()->getBranchId();

    InvoicePaymentRecord::where('invoice_id', $invoice->id)->delete();

    $createdRecords = [];
    $totalPayments = 0;

    foreach ($payments as $payment) {
        $amountPaid = (float)($payment['payment_amount'] ?? 0);

        if ($amountPaid <= 0) continue;

        $totalPayments += $amountPaid;

        $created = InvoicePaymentRecord::create([
            'invoice_id' => $invoice->id,
            'branch_id' => $branchId,
            'payment_date' => $payment['payment_date'] ?? null,
            'payment_amount' => $amountPaid,
            'paid_via' => $payment['paid_via'] ?? null,
            'check_number' => $payment['check_number'] ?? null,
            'processing_fee_per' => $payment['processing_fee_per'] ?? null,
            'processing_fee_flat' => $payment['processing_fee_flat'] ?? null,
            'payment_notes' => $payment['payment_notes'] ?? null
        ]);

        $createdRecords[] = $created;
    }

    $netTotal = $invoice->net_total ?? 0;
    $existingPayments = 0;
    $newTotalPayments = $existingPayments + $totalPayments;
    $remainingBalance = max(0, $netTotal - $newTotalPayments);

    $paymentStatus = 'unpaid';
    if ($remainingBalance <= 0) {
        $paymentStatus = 'paid';
    } elseif ($newTotalPayments > 0) {
        $paymentStatus = 'partially_paid';
    }

    $invoice->update([
        'total_repayment_amount' => $newTotalPayments,
        'remaining_balance' => $remainingBalance,
        'status' => $paymentStatus,
    ]);

    return [
        'repayment_records' => $createdRecords,
        'total_repayment_amount' => $newTotalPayments,
        'remaining_balance' => $remainingBalance,
        'status' => $paymentStatus,
    ];
}




protected function handleDocuments(Request $request, $invoiceId)
{

    if ($request->hasFile('file')) {
        //dd($request->file('file_path'));
        $files = $request->file('file');
    
        // Normalize to array (even if it's one file)
        $files = is_array($files) ? $files : [$files];
    
        foreach ($files as $file) {
            if ($file->isValid()) {
                $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                    'folder' => 'Smile_logistics/invoice',
                ]);
    
                InvoiceDoc::create([
                    'invoice_id' => $invoiceId,
                    'file' => $uploadedFile->getSecurePath(),
                    'public_id' => $uploadedFile->getPublicId()
                ]);
            }
        }
    }
}

protected function handlePayments(Request $request, $invoiceId)
{
     if (!$request->has('credit_amount')) return;

    // Convert all inputs to arrays consistently
    $payments = [
        'credit_amount' => (array)$request->credit_amount,
        'credit_memo' => (array)($request->credit_memo ?? []),
        'credit_note' => (array)($request->credit_note ?? []),
        'credit_date' => (array)($request->credit_date ?? []),
        'check_number' => (array)($request->check_number ?? []),
        'notes' => (array)($request->notes ?? []),
        'payment_date' => (array)($request->payment_date ?? []),
        'payment_method' => (array)($request->payment_method ?? []),
        'processing_fee_flate_rate' => (array)($request->processing_fee_flate_rate ?? []),
        'processing_fee_percent' => (array)($request->processing_fee_percent ?? []),
    ];

    // Ensure all arrays have the same length
    $paymentCount = count($payments['credit_amount']);
    $payments = array_map(function ($item) use ($paymentCount) {
        return array_pad($item, $paymentCount, null);
    }, $payments);

    DB::beginTransaction();
    try {
        InvoicePaymentRecieved::where('invoice_id', $invoiceId)->delete();

        foreach ($payments['credit_amount'] as $index => $amount) {
            $paymentData = [
                'invoice_id' => $invoiceId,
                'credit_memo' => $payments['credit_memo'][$index],
                'credit_amount' => $amount,
                'credit_note' => $payments['credit_note'][$index],
                'check_number' => $payments['check_number'][$index],
                'notes' => $payments['notes'][$index],
                'payment_method' => $payments['payment_method'][$index],
                'processing_fee_flate_rate' => $payments['processing_fee_flate_rate'][$index],
                'processing_fee_percent' => $payments['processing_fee_percent'][$index],
            ];

            // Handle date formatting safely
            if (!empty($payments['credit_date'][$index])) {
                try {
                    $paymentData['credit_date'] = Carbon::createFromFormat('m/d/Y', $payments['credit_date'][$index])->format('Y-m-d');
                } catch (\Exception $e) {
                    // Log error or handle invalid date
                    $paymentData['credit_date'] = null;
                }
            }

            if (!empty($payments['payment_date'][$index])) {
                try {
                    $paymentData['payment_date'] = Carbon::createFromFormat('m/d/Y', $payments['payment_date'][$index])->format('Y-m-d');
                } catch (\Exception $e) {
                    // Log error or handle invalid date
                    $paymentData['payment_date'] = null;
                }
            }

            InvoicePaymentRecieved::create($paymentData);
        }

        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

public function RepaymentRecord(Request $request, $id)
{
    // Start database transaction
    DB::beginTransaction();

    try {
        $invoice = Invoice::findOrFail($id);
        $branchId = auth()->user()->getBranchId();
        
        // Validate request data
        $validated = $request->validate([
                 'paid_via' => 'nullable|array',
                 'paid_via.*' => 'nullable|string|max:255',
                 'payment_date' => 'nullable|array',
                 'payment_date.*' => 'nullable|date',
                 'payment_amount' => 'required|array',
                 'payment_amount.*' => 'required|numeric',
                 'check_number' => 'nullable|array',
                 'check_number.*' => 'nullable|string|max:255',
                 'processing_fee_per' => 'nullable|array',
                 'processing_fee_per.*' => 'nullable|numeric',
                 'processing_fee_flat' => 'nullable|array',
                 'processing_fee_flat.*' => 'nullable|numeric',
                 'payment_notes' => 'nullable|array',
                 'payment_notes.*' => 'nullable|string',
        ]);

        if (!$request->has('payment_amount')) {
            return back()->with('error', 'No payment amounts provided');
        }

        $totalPayments = 0;
        $netTotal = $invoice->net_total;
        $existingPayments = $invoice->total_repayment_amount ?? 0;
        
        // Process each payment
        foreach ($request->payment_amount as $index => $amount) {
            $amountPaid = (float)$amount;
            
            if ($amountPaid <= 0) {
                continue;
            }

            $totalPayments += $amountPaid;

            InvoicePaymentRecord::create([
                'invoice_id' => $invoice->id,
                'branch_id' => $branchId,
                'payment_date' => $validated['payment_date'][$index] ?? null,
                'payment_amount' => $amountPaid,
                'paid_via' => $validated['paid_via'][$index] ?? null,
                'check_number' => $validated['check_number'][$index] ?? null,
                'processing_fee_per' => $validated['processing_fee_per'][$index] ?? null,
                'processing_fee_flat' => $validated['processing_fee_flat'][$index] ?? null,
                'payment_notes' => $validated['payment_notes'][$index] ?? null,
                'created_by' => auth()->id()
            ]);
        }

        if ($totalPayments > 0) {
            // Calculate new totals including existing payments
            $newTotalPayments = $existingPayments + $totalPayments;
            $remainingBalance = max(0, $netTotal - $newTotalPayments); // Prevent negative balance

            // Determine payment status
            $paymentStatus = 'unpaid';
            if ($remainingBalance <= 0) {
                $paymentStatus = 'paid';
            } elseif ($newTotalPayments > 0) {
                $paymentStatus = 'partially_paid';
            }

            // Update invoice
            $invoice->update([
                'total_repayment_amount' => $newTotalPayments,
                'remaining_balance' => $remainingBalance,
                'status' => $paymentStatus,
            ]);

        }

        DB::commit();

        return back()->with('success', 'Payments recorded successfully');

    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
        
   
}

    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();
        return response()->json(['message' => 'Invoice deleted successfully'], 200);
    }
}

