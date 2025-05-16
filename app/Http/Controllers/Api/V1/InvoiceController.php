<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InvoiceDoc;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\InvoiceCharge;
use App\Mail\InvoiceCreatedMail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
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
        $branchId = $user->branch ? $user->branch->id : null;
        $customerId = $user->customer ? $user->customer->id : null;
        //dd($branchId, $customerId);
        if ($user->hasRole('businessadministrator')) {
            $invoices = Invoice::where('branch_id', $branchId)
                            ->with('customer', 'user')
                            ->latest()
                            ->get();
        }
        elseif ($user->hasRole('customer')) {
            $invoices = Invoice::where('customer_id', $customerId)
                            ->with('branch', 'user')
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
            'customer', 
            'user', 
            'invoicedocs', 
            'invoicepayments', 
            'invoicecharges', 
            'branch'
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
        $branchId = $user->branch ? $user->branch->id : null;
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

            if ($request->charge_type) {
                $total = 0;
                $totalDiscount = 0;
                foreach ($request->charge_type as $index => $type) {
                    $amount = (float)($request->amount[$index] ?? 0);
                    $discount = (float)($request->discount[$index] ?? 0);
                    
                    $total += $amount;
                    $totalDiscount += $discount;
                    InvoiceCharge::create([
                        'invoice_id' => $invoice->id,
                        'charge_type' => $type,
                        'units' => $request->units[$index] ?? null,
                        'unit_rate' => $request->unit_rate[$index] ?? null,
                        'amount' => $request->amount[$index] ?? null,
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

            // Insert into InvoiceDoc
            // if ($request->hasFile('file')) {
            //     foreach ($request->file('file') as $index => $file) {
            //         $filename = $file->store('invoices', 'public');
            //         InvoiceDoc::create([
            //             'invoice_id' => $invoice->id,
            //             'file' => $filename,
            //             'file_title' => $request->file_title[$index] ?? null,
            //         ]);
            //     }
            // }
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

            return response()->json(['message' => 'Invoice created successfully', 'invoice' => $invoice], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create invoice', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing invoice and its related models.
     */

     public function update(Request $request, $id)
{
    $invoice = Invoice::findOrFail($id);

    DB::beginTransaction();
    try {
        $invoice->update($request->all());

        // Handle Charges (single or array)
        $this->handleCharges($request, $id, $invoice);

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
    if (!$request->has('charge_type')) return;

    // Convert single item to array for consistent processing
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

    foreach ($charges['charge_type'] as $index => $type) {
         $units = (float)($charges['units'][$index] ?? 0);
            $rate = (float)($charges['rate'][$index] ?? 0);
            $discount = (float)($charges['discount'][$index] ?? 0);
            $amounts = (float)($charges['amount'][$index] ?? 0);
            
            $total += $amounts;
            $total_discount += $discount;
        InvoiceCharge::create([
            'invoice_id' => $invoiceId,
            'charge_type' => $type,
            'units' => $charges['units'][$index] ?? null,
            'unit_rate' => $charges['rate'][$index] ?? null,
            'amount' =>  $amounts ?? null, //$charges['amount'][$index] ?? null,
            'comment' => $charges['comment'][$index] ?? null,
            'discount' => $charges['discount'][$index] ?? null,
            'internal_notes' => $charges['internal_notes'][$index] ?? null
        ]);
    }
     $invoice->update([
            'net_total' => $total - $total_discount,
            'total_discount' => $total_discount
        ]);
}

protected function handleDocuments(Request $request, $invoiceId)
{
    // if (!$request->hasFile('file')) return;

    // // Handle both single file and multiple files
    // $files = $request->file('file');
    // if (!is_array($files)) {
    //     $files = [$files];
    // }

    // $fileTitles = is_array($request->file_title ?? []) ? $request->file_title : [$request->file_title];

    // InvoiceDoc::where('invoice_id', $invoiceId)->delete();

    // foreach ($files as $index => $file) {
    //     $filename = $file->store('invoices', 'public');
    //     InvoiceDoc::create([
    //         'invoice_id' => $invoiceId,
    //         'file' => $filename,
    //         'file_title' => $fileTitles[$index] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
    //     ]);
    // }

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

// protected function handlePayments(Request $request, $invoiceId)
// {
//     if (!$request->has('credit_amount')) {
//         return;
//     }
//     $payments = [
//         'credit_amount' => is_array($request->credit_amount) ? $request->credit_amount : [$request->credit_amount],
//         'credit_memo' => is_array($request->credit_memo ?? []) ? $request->credit_memo : [$request->credit_memo],
//         'credit_note' => is_array($request->credit_note ?? []) ? $request->credit_note : [$request->credit_note],
//         'credit_date' => is_array($request->credit_date ?? []) ? $request->credit_date : [$request->credit_date],
//         'check_number' => is_array($request->check_number ?? []) ? $request->check_number : [$request->check_number],
//         'notes' => is_array($request->notes ?? []) ? $request->notes : [$request->notes],
//         'payment_date' => is_array($request->payment_date ?? []) ? $request->payment_date : [$request->payment_date],
//         'payment_method' => is_array($request->payment_method ?? []) ? $request->payment_method : [$request->payment_method],
//         'processing_fee_flate_rate' => is_array($request->processing_fee_flate_rate ?? []) ? $request->processing_fee_flate_rate : [$request->processing_fee_flate_rate],
//         'processing_fee_percent' => is_array($request->processing_fee_percent ?? []) ? $request->processing_fee_percent : [$request->processing_fee_percent],
//         //'amount' => is_array($request->amount ?? []) ? $request->amount : [$request->amount],
//     ];

//     InvoicePaymentRecieved::where('invoice_id', $invoiceId)->delete();

//      foreach ($payments['credit_amount'] as $index => $type) {
//         InvoicePaymentRecieved::create([
//             'invoice_id' => $invoiceId,
//             'credit_memo' => $payments['credit_memo'][$index] ?? null,
//             'credit_amount' => $payments['credit_amount'][$index] ?? null,
//             'credit_note' => $payments['credit_note'][$index] ?? null,
//             'credit_date' => !empty($payments['credit_date'][$index]) 
//                 ? Carbon::createFromFormat('m/d/Y', $payments['credit_date'][$index])->format('Y-m-d')
//                 : null,
//             'check_number' => $payments['check_number'][$index] ?? null,
//             'notes' => $payments['notes'][$index] ?? null,
//             'payment_date' => !empty($payments['payment_date'][$index]) 
//                 ? Carbon::createFromFormat('m/d/Y', $payments['payment_date'][$index])->format('Y-m-d')
//                 : null,
//             'payment_method' => $payments['payment_method'][$index] ?? null,
//             'processing_fee_flate_rate' => $payments['processing_fee_flate_rate'][$index] ?? null,
//             'processing_fee_percent' => $payments['processing_fee_percent'][$index] ?? null,
//         ]);
//      }

    // Get all payments data
    // $payments = $request->invoicepayments;
    
    // // If we get a single payment (not in array format), convert to array
    // if (isset($payments['credit_memo'])) {
    //     $payments = [$payments];
    // }

    // // Process each payment
    // foreach ($payments as $index => $payment) {
    //     // Skip if essential data is missing
    //     if (empty($payment['credit_memo'])) {
    //         continue;
    //     }

    //     $paymentData = [
    //         'invoice_id' => $invoiceId,
    //         'credit_memo' => $payment['credit_memo'] ?? null,
    //         'credit_amount' => $payment['credit_amount'] ?? 0,
    //         'credit_note' => $payment['credit_note'] ?? null,
    //         'credit_date' => !empty($payment['credit_date']) 
    //             ? Carbon::createFromFormat('m/d/Y', $payment['credit_date'])->format('Y-m-d')
    //             : null,
    //         'check_number' => $payment['check_number'] ?? null,
    //         'notes' => $payment['notes'] ?? null,
    //         'payment_date' => $payment['payment_date'] ?? null,
    //         'payment_method' => $payment['payment_method'] ?? null,
    //         'processing_fee_flate_rate' => $payment['processing_fee_flate_rate'] ?? 0,
    //         'processing_fee_percent' => $payment['processing_fee_percent'] ?? 0,
    //     ];

    //     // Update existing or create new
    //     if (!empty($payment['id'])) {
    //         InvoicePaymentRecieved::where('id', $payment['id'])
    //             ->update($paymentData);
    //     } else {
    //         InvoicePaymentRecieved::create($paymentData);
    //     }
    // }

    // // Handle deleted payments
    // if ($request->has('deleted_payment_ids')) {
    //     $deletedIds = is_array($request->deleted_payment_ids) 
    //         ? $request->deleted_payment_ids 
    //         : explode(',', $request->deleted_payment_ids);
            
    //     InvoicePaymentRecieved::where('invoice_id', $invoiceId)
    //         ->whereIn('id', $deletedIds)
    //         ->delete();
    // }
//}
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();
        return response()->json(['message' => 'Invoice deleted successfully'], 200);
    }
}

