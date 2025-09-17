<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSettlementRequest extends FormRequest
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
            'office' => 'nullable|string|max:255',
            // 'settlement_no' => 'nullable|string|max:255',
            'settlement_date' => 'nullable|date',
            'settlement_week' => 'nullable|string',
            'driver_id' => 'nullable|integer|exists:drivers,id',
            'truck_id' => 'nullable|integer|exists:trucks,id',
            'carrier_id' => 'nullable|integer|exists:carriers,id',
            'settlement_type' => 'nullable|string|max:255',
            'week_from' => 'nullable|date',
            'week_to' => 'nullable|date|after_or_equal:week_from',
            'payee' => 'nullable|string|max:255',
            'payee_note' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|string|max:100',
            'check_payment_reference' => 'nullable|string|max:255',
            'payment_date' => 'nullable|date',
            'payment_note' => 'nullable|string|max:1000',
            'internal_notes' => 'nullable|string|max:2000',
            'tags' => 'nullable',

            // payments
            'payment_type.*' => 'nullable|string',
            'comment.*' => 'nullable|string',
            'units.*' => 'nullable|numeric',
            'rate.*' => 'nullable|numeric',
            'amount.*' => 'nullable|numeric',
            'payment_discount.*' => 'nullable|numeric',
            'payment_total.*' => 'nullable|numeric',

            // deductions
            'deduction_type.*' => 'nullable|string',
            'deduction_amount.*' => 'nullable|numeric',
            'deduction_comment.*' => 'nullable|string',
            'deduction_note.*' => 'nullable|string',
            'total_deductions.*' => 'nullable|numeric',
            
            // escrow release
            'escrow_release_account.*' => 'nullable|string',
            'escrow_release_comment.*' => 'nullable|string',
            'escrow_release_note.*' => 'nullable|string',
            'escrow_release_amount.*' => 'nullable|numeric',
            'total_escrow_release.*' => 'nullable|numeric',

            // Backup docs
            'file_path.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
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
