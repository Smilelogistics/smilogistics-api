<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCustomerRequest extends FormRequest
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
        'customer_name' => 'required|string|max:255',
        'customer_email' => 'nullable|email|max:255',
        'customer_phone' => 'nullable|string|max:20',
        'customer_primary_address' => 'nullable|string|max:255',
        'customer_secondary_address' => 'nullable|string|max:255',
        'customer_code' => 'nullable|string|max:50',
        'customer_sales_rep' => 'nullable|string|max:100',
        'customer_office' => 'nullable|string|max:100',
        'customer_type' => 'nullable|string|max:50',
        'customer_city' => 'nullable|string|max:100',
        'customer_state' => 'nullable|string|max:100',
        'customer_zip' => 'nullable|string|max:20',
        'customer_country' => 'nullable|string|max:100',
        'fax_no' => 'nullable|string|max:20',
        'toll_free' => 'nullable|string|max:20',
        'other_notes' => 'nullable|string',
        'credit_limit' => 'nullable|numeric',
        'alert_percentage' => 'nullable|numeric|between:0,100',
        'outstanding_balance' => 'nullable|numeric',
        'start_date' => 'nullable',
        'send_invoice_under_this_company' => 'nullable|string|max:255',
        'account_code' => 'nullable|string|max:50',
        'invoice_footer_note' => 'nullable|string',
        'isSubAccount' => 'nullable|integer',
        'create_invoices_under_this_parent' => 'nullable|string|max:50',
        'subAccount_of' => 'nullable|string|max:255',
        'factoringCompany' => 'nullable|string|max:255',
        'isFactoredInvoice' => 'nullable|integer',
        'isPrepaid' => 'nullable|integer',
        'isNonBillable' => 'nullable|integer',
        'flash_note_for_accounting' => 'nullable|string',
        'note' => 'nullable|string',
        'tag' => 'nullable|string',	
       // 'print_settlements_under_this_company' => 'nullable|string',
        'flash_note_for_drivers' => 'nullable|string',
       //'flash_notes_to_payroll' => 'nullable|string',
        'internal_note' => 'nullable|string',
        'file_path' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:2048',
 
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
