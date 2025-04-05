<?php

namespace App\Http\Controllers\Api\V1;

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
        $branchId = $user->branch ? $user->branch->id : null;
        $customerId = $user->customer ? $user->customer->id : null;

        if ($user->hasRole('businessadministrator')) {
            $invoice = Invoice::where('branch_id', $branchId)
                            ->with('customer', 'user', 'invoicedocs', 'invoicepayments')
                            ->latest()
                            ->findOrFail($id);
        }
        elseif ($user->hasRole('customer')) {
            $invoice = Invoice::where('customer_id', $customerId)
                            ->with('branch', 'user', 'invoicedocs', 'invoicepayments')
                            ->latest()
                            ->findOrFail($id);
        } else {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // $invoice = Invoice::with(['charges', 'docs', 'payments'])->findOrFail($id);
        return response()->json(['invoice' => $invoice], 200);
    }

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
                foreach ($request->charge_type as $index => $type) {
                    InvoiceCharge::create([
                        'invoice_id' => $invoice->id,
                        'charge_type' => $type,
                        'units' => $request->units[$index] ?? null,
                        'unit_rate' => $request->unit_rate[$index] ?? null,
                        'amount' => $request->amount[$index] ?? null,
                    ]);
                }
            }

            // Insert into InvoiceDoc
            if ($request->hasFile('file')) {
                foreach ($request->file('file') as $index => $file) {
                    $filename = $file->store('invoices', 'public');
                    InvoiceDoc::create([
                        'invoice_id' => $invoice->id,
                        'file' => $filename,
                        'file_title' => $request->file_title[$index] ?? null,
                    ]);
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
        $this->handleCharges($request, $id);

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

protected function handleCharges(Request $request, $invoiceId)
{
    if (!$request->has('charge_type')) return;

    // Convert single item to array for consistent processing
    $charges = [
        'charge_type' => is_array($request->charge_type) ? $request->charge_type : [$request->charge_type],
        'units' => is_array($request->units ?? []) ? $request->units : [$request->units],
        'unit_rate' => is_array($request->unit_rate ?? []) ? $request->unit_rate : [$request->unit_rate],
        'amount' => is_array($request->amount ?? []) ? $request->amount : [$request->amount],
    ];

    InvoiceCharge::where('invoice_id', $invoiceId)->delete();

    foreach ($charges['charge_type'] as $index => $type) {
        InvoiceCharge::create([
            'invoice_id' => $invoiceId,
            'charge_type' => $type,
            'units' => $charges['units'][$index] ?? null,
            'unit_rate' => $charges['unit_rate'][$index] ?? null,
            'amount' => $charges['amount'][$index] ?? null,
        ]);
    }
}

protected function handleDocuments(Request $request, $invoiceId)
{
    if (!$request->hasFile('file')) return;

    // Handle both single file and multiple files
    $files = $request->file('file');
    if (!is_array($files)) {
        $files = [$files];
    }

    $fileTitles = is_array($request->file_title ?? []) ? $request->file_title : [$request->file_title];

    InvoiceDoc::where('invoice_id', $invoiceId)->delete();

    foreach ($files as $index => $file) {
        $filename = $file->store('invoices', 'public');
        InvoiceDoc::create([
            'invoice_id' => $invoiceId,
            'file' => $filename,
            'file_title' => $fileTitles[$index] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
        ]);
    }
}

protected function handlePayments(Request $request, $invoiceId)
{
    if (!$request->has('credit_memo')) return;

    // Convert single payment to array
    $payments = [
        'payment_ids' => is_array($request->payment_ids ?? []) ? $request->payment_ids : [$request->payment_ids],
        'credit_memo' => is_array($request->credit_memo) ? $request->credit_memo : [$request->credit_memo],
        'credit_amount' => is_array($request->credit_amount) ? $request->credit_amount : [$request->credit_amount],
        'credit_date' => is_array($request->credit_date) ? $request->credit_date : [$request->credit_date],
        'credit_note' => is_array($request->credit_note ?? []) ? $request->credit_note : [$request->credit_note],
        'payment_method' => is_array($request->payment_method ?? []) ? $request->payment_method : [$request->payment_method],
        'balance_before_credit' => is_array($request->balance_before_credit ?? []) ? $request->balance_before_credit : [$request->balance_before_credit],
        'processing_fee_flate_rate' => is_array($request->processing_fee_flate_rate ?? []) ? $request->processing_fee_flate_rate : [$request->processing_fee_flate_rate],
        'processing_fee_percent' => is_array($request->processing_fee_percent ?? []) ? $request->processing_fee_percent : [$request->processing_fee_percent],
    ];

    // Handle deleted payments
    if ($request->has('deleted_payments')) {
        $deleted = is_array($request->deleted_payments) ? $request->deleted_payments : [$request->deleted_payments];
        InvoicePaymentRecieved::where('invoice_id', $invoiceId)
            ->whereIn('id', $deleted)
            ->delete();
    }

    // Process payments
    foreach ($payments['credit_memo'] as $index => $creditMemo) {
        $paymentData = [
            'invoice_id' => $invoiceId,
            'credit_memo' => $creditMemo,
            'credit_amount' => $payments['credit_amount'][$index] ?? 0,
            'credit_date' => $payments['credit_date'][$index],
            'credit_note' => $payments['credit_note'][$index] ?? null,
            'payment_method' => $payments['payment_method'][$index] ?? null,
            'balance_before_credit' => $payments['balance_before_credit'][$index] ?? 0,
            'processing_fee_flate_rate' => $payments['processing_fee_flate_rate'][$index] ?? 0,
            'processing_fee_percent' => $payments['processing_fee_percent'][$index] ?? 0,
        ];

        if (!empty($payments['payment_ids'][$index])) {
            InvoicePaymentRecieved::where('id', $payments['payment_ids'][$index])
                ->update($paymentData);
        } else {
            InvoicePaymentRecieved::create($paymentData);
        }
    }
}
}

