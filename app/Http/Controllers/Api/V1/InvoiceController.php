<?php

namespace App\Http\Controllers\Api\V1;

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
use App\Models\InvoicePaymentRecieved;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreInvoiceRequest;
use App\Notifications\InvoiceBilltoNotification;

class InvoiceController extends Controller
{
    
    /**
     * Get all invoices with related data.
     */
    public function showAll()
    {
        $invoices = Invoice::with(['charges', 'docs', 'payments'])->get();
        return response()->json(['invoices' => $invoices], 200);
    }

    /**
     * Get a single invoice with related data.
     */
    public function show($id)
    {
        $invoice = Invoice::with(['charges', 'docs', 'payments'])->findOrFail($id);
        return response()->json(['invoice' => $invoice], 200);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $invoices = Invoice::where('invoice_number', 'like', '%' . $query . '%')->get();
        return response()->json(['invoices' => $invoices], 200);
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

            // Update related InvoiceCharge records
            if ($request->charge_type) {
                InvoiceCharge::where('invoice_id', $id)->delete();
                foreach ($request->charge_type as $index => $type) {
                    InvoiceCharge::create([
                        'invoice_id' => $id,
                        'charge_type' => $type,
                        'units' => $request->units[$index] ?? null,
                        'unit_rate' => $request->unit_rate[$index] ?? null,
                        'amount' => $request->amount[$index] ?? null,
                    ]);
                }
            }

            // Update InvoiceDoc (remove old and add new)
            if ($request->hasFile('file')) {
                InvoiceDoc::where('invoice_id', $id)->delete();
                foreach ($request->file('file') as $index => $file) {
                    $filename = $file->store('invoices', 'public');
                    InvoiceDoc::create([
                        'invoice_id' => $id,
                        'file' => $filename,
                        'file_title' => $request->file_title[$index] ?? null,
                    ]);
                }
            }

            // Update InvoicePayment (remove old and add new)
            if ($request->payment_date) {
                InvoicePayment::where('invoice_id', $id)->delete();
                foreach ($request->payment_date as $index => $date) {
                    InvoicePaymentRecieved::create([
                        'invoice_id' => $id,
                        'payment_date' => $date,
                        'payment_method' => $request->payment_method[$index] ?? null,
                        'amount' => $request->amount[$index] ?? null,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['message' => 'Invoice updated successfully', 'invoice' => $invoice], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update invoice', 'error' => $e->getMessage()], 500);
        }
    }

}
