<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InvoiceDoc;
use Illuminate\Http\Request;
use App\Models\InvoiceCharge;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\InvoicePaymentRecieved;
use Illuminate\Support\Facades\Validator;
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

    /**
     * Store a new invoice with related charges, documents, and payments.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // General shipment & invoice fields
            'shipment_id' => 'nullable|exists:shipments,id',
            'user_id' => 'nullable|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'invoice_number' => 'nullable|string|max:255',
            'invoice_date' => 'nullable|date',
            'invoice_prefix' => 'nullable|string|max:255',
            'isFactored' => 'nullable|boolean',
            'override_default_company' => 'nullable|boolean',
            'invoice_type' => 'nullable|string|max:255',
            'invoice_note' => 'nullable|string',
            'office' => 'nullable|string|max:255',
            'bill_to' => 'nullable|string|max:255',
            'bill_to_note' => 'nullable|string',
            'invoice_terms' => 'nullable|string|max:255',
            'invoice_due_date' => 'nullable|date',
            'attention_invoice_to' => 'nullable|string|max:255',
            'note_bill_to_party' => 'nullable|string',
            'loads_on_invoice' => 'nullable|integer',
            'reference_number' => 'nullable|string|max:255',
            'po_number' => 'nullable|string|max:255',
            'booking_number' => 'nullable|string|max:255',
            'bill_of_landing_number' => 'nullable|string|max:255',
            'move_date' => 'nullable|date',
            'trailer' => 'nullable|string|max:255',
            'container' => 'nullable|string|max:255',
            'chasis' => 'nullable|string|max:255',
            'load_weight' => 'nullable|numeric',
            'commodity' => 'nullable|string|max:255',
            'no_of_packages' => 'nullable|integer',
            'from_address' => 'nullable|string|max:255',
            'to_address' => 'nullable|string|max:255',
            'stop_address' => 'nullable|string|max:255',
            'credit_memo' => 'nullable|string',
            'credit_amount' => 'nullable|numeric',
            'credit_date' => 'nullable|date',
            'credit_note' => 'nullable|string',
        
            // Invoice Charges (single & array support)
            'invoice_id' => 'nullable|array',
            'invoice_id.*' => 'exists:invoices,id',
            'load_number' => 'nullable|array',
            'load_number.*' => 'nullable|string|max:255',
            'charge_type' => 'nullable|array',
            'charge_type.*' => 'nullable|string|max:255',
            'comment' => 'nullable|array',
            'comment.*' => 'nullable|string',
            'units' => 'nullable|array',
            'units.*' => 'nullable|integer',
            'unit_rate' => 'nullable|array',
            'unit_rate.*' => 'nullable|numeric',
            'amount' => 'nullable|array',
            'amount.*' => 'nullable|numeric',
            'discount' => 'nullable|array',
            'discount.*' => 'nullable|numeric',
            'internal_notes' => 'nullable|array',
            'internal_notes.*' => 'nullable|string',
            'general_internal_notes' => 'nullable|array',
            'general_internal_notes.*' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string|max:255',
            'isAccessorial' => 'nullable|array',
            'isAccessorial.*' => 'nullable|boolean',
            'total' => 'nullable|array',
            'total.*' => 'nullable|numeric',
        
            // Invoice Documents (single & array support)
            'file' => 'nullable|array',
            'file.*' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
            'file_title' => 'nullable|array',
            'file_title.*' => 'nullable|string|max:255',
        
            // Invoice Payments (single & array support)
            'payment_date' => 'nullable|array',
            'payment_date.*' => 'nullable|date',
            'payment_method' => 'nullable|array',
            'payment_method.*' => 'nullable|string|max:255',
            'check_number' => 'nullable|array',
            'check_number.*' => 'nullable|string|max:255',
            'amount' => 'nullable|array',
            'amount.*' => 'nullable|numeric',
            'processing_fee_percent' => 'nullable|array',
            'processing_fee_percent.*' => 'nullable|numeric',
            'processing_fee_flate_rate' => 'nullable|array',
            'processing_fee_flate_rate.*' => 'nullable|numeric',
            'notes' => 'nullable|array',
            'notes.*' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        DB::beginTransaction();
        try {

            $invoice = Invoice::create($validator->validated());

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
            if ($request->payment_date) {
                foreach ($request->payment_date as $index => $date) {
                    InvoicePaymentRecieved::create([
                        'invoice_id' => $invoice->id,
                        'payment_date' => $date,
                        'payment_method' => $request->payment_method[$index] ?? null,
                        'amount' => $request->amount[$index] ?? null,
                    ]);
                }
            }

            $customer = Customer::find($request->customer_id);
            dd($customer);
            $customer->notify(new InvoiceBilltoNotification($invoice));

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
