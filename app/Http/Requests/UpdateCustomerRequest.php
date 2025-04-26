<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCustomerRequest extends FormRequest
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
            'customer_name' => 'sometimes|string|max:255',
            'customer_email' => 'sometimes|email|max:255',
            'customer_phone' => 'sometimes|string|max:20',
            'customer_primary_address' => 'sometimes|string|max:255',
            'customer_secondary_address' => 'sometimes|string|max:255',
            'customer_code' => 'sometimes|string|max:50',
            'customer_sales_rep' => 'sometimes|string|max:100',
            'customer_office' => 'sometimes|string|max:100',
            'customer_type' => 'sometimes|string|max:50',
            'customer_city' => 'sometimes|string|max:100',
            'customer_state' => 'sometimes|string|max:100',
            'customer_zip' => 'sometimes|string|max:20',
            'customer_country' => 'sometimes|string|max:100',
            'fax_no' => 'sometimes|string|max:20',
            'toll_free' => 'sometimes|string|max:20',
            'other_notes' => 'sometimes|string',
            'credit_limit' => 'sometimes|numeric',
            'alert_percentage' => 'sometimes|numeric|between:0,100',
            'outstanding_balance' => 'sometimes|numeric',
            'start_date' => 'sometimes',
            'send_invoice_under_this_company' => 'sometimes|string|max:255',
            'account_code' => 'sometimes|string|max:50',
            'invoice_footer_note' => 'sometimes|string',
            'isSubAccount' => 'sometimes|integer',
            'create_invoices_under_this_parent' => 'sometimes|integer',
            'subAccount_of' => 'sometimes|string|max:255',
            'factoringCompany' => 'sometimes|string|max:255',
            'isFactoredInvoice' => 'sometimes|integer',
            'isPrepaid' => 'sometimes|integer',
            'isNonBillable' => 'sometimes|integer',
            'flash_note_for_accounting' => 'sometimes|string',
            'note' => 'sometimes|string',
            'tag' => 'sometimes',	
        // 'print_settlements_under_this_company' => 'sometimes|string',
            'flash_note_for_drivers' => 'sometimes|string',
        //'flash_notes_to_payroll' => 'sometimes|string',
            'internal_note' => 'sometimes|string',
            'file_path' => 'sometimes|file|mimes:pdf,jpg,png,doc,docx|max:2048',
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
