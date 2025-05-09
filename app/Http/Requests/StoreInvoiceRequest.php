<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
                 // General shipment & invoice fields
                 'shipment_id' => 'nullable|exists:shipments,id',
                 //'user_id' => 'nullable|exists:users,id',
                 'customer_id' => 'required|exists:customers,id',
                 'invoice_date' => 'nullable|date',
                 'isFactored' => 'nullable|boolean',
                 'override_default_company' => 'nullable|string|max:255',
                 'invoice_type' => 'nullable|string|max:255',
                 'invoice_note' => 'nullable|string',
                 'office' => 'nullable|string|max:255',
                 'bill_to' => 'nullable|string|max:255',
                 'bill_to_note' => 'nullable|string',
                 'invoice_terms' => 'nullable|string|max:255',
                 'invoice_due_date' => 'nullable|date',
                 'attention_invoice_to' => 'nullable|string|max:255',
                 'note_bill_to_party' => 'nullable|string',
                 'loads_on_invoice' => 'nullable|string|max:255',
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
                 'total' => 'nullable|numeric',
                // 'total.*' => 'nullable|numeric',
             
                 // Invoice Documents (single & array support)
                 'file' => 'nullable',
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
        
                 'credit_memo' => 'nullable|array',
                 'credit_memo.*' => 'nullable|string|sometimes',
                 'credit_amount' => 'nullable|array',
                 'credit_amount.*' => 'nullable|numeric|sometimes',
                 'credit_date' => 'nullable|array',
                 'credit_date.*' => 'nullable|date|sometimes',
                 'credit_note' => 'nullable|array',
                 'credit_note.*' => 'nullable|string|sometimes',
        ];
    }

    public function messages()
    {
        return [
            'customer_id.required' => 'Please select a valid bill to.',
            'customer_id.exists' => 'The selected bill to does not exist.',
            'amount.required' => 'Amount is required and must be a valid number.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount must be at least 1.',
        ];
    }

    
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422));
    }
}
