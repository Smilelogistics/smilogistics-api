<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreShipmentRequest extends FormRequest
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
             //'shipment_prefix' => 'nullable|string|max:255',
            //'agency_id' => 'nullable|exists:agencies,id',
            //'branch_id' => 'required|exists:branches,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'user_id' => 'nullable|exists:users,id',
            'carrier_id' => 'nullable|exists:carriers,id',
            'truck_id' => 'nullable|exists:trucks,id',
            'bike_id' => 'nullable|exists:bikes,id',
            'shipment_tracking_number' => 'nullable|string|max:255',
            'shipment_status' => 'nullable|string|max:255',
            'signature' => 'nullable|string|max:255',
            'office' => 'nullable|string|max:255',
            'load_type' => 'nullable|string|max:255',
            'load_type_note' => 'nullable|string|max:255',
            'brokered' => 'nullable|string|max:255',
            'shipment_image' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'bill_of_laden_number' => 'nullable|string|max:255',
            'booking_number' => 'nullable|string|max:255',
            'po_number' => 'nullable|string|max:255',
            'shipment_weight' => 'nullable|numeric',
            'commodity' => 'nullable|string|max:255',
            'pieces' => 'nullable|integer',
            'pickup_number' => 'nullable|string|max:255',
            'overweight_hazmat' => 'nullable|string|max:255',
            'tags' => 'nullable',
            'genset_number' => 'nullable|string|max:255',
            'reefer_temp' => 'nullable|string|max:255',
            'seal_number' => 'nullable|string|max:255',
            'total_miles' => 'nullable|numeric',
            'loaded_miles' => 'nullable|numeric',
            'empty_miles' => 'nullable|numeric',
            'dh_miles' => 'nullable|numeric',
            'fuel_rate_per_gallon' => 'nullable|numeric',
            'mpg' => 'nullable|numeric',
            'total_fuel_cost' => 'nullable|numeric',
            'broker_name' => 'nullable|string|max:255',
            'broker_email' => 'nullable|email|max:255',
            'broker_phone' => 'nullable|string|max:20',
            'broker_reference_number' => 'nullable|string|max:255',
            'broker_batch_number' => 'nullable|string|max:255',
            'broker_seq_number' => 'nullable|string|max:255',
            'broker_sales_rep' => 'nullable|string|max:255',
            'broker_edi_api_shipment_number' => 'nullable|string|max:255',
            'broker_notes' => 'nullable|string|max:1000',
            //chargeTable
            'charges' => 'nullable|array',
            'charges.*.charge_type' => 'nullable|string|max:255',
            'charges.*.comment' => 'nullable|string|max:500',
            'charges.*.units' => 'nullable|integer|min:1',
            'charges.*.rate' => 'nullable|numeric|min:0',
            'charges.*.amount' => 'nullable|numeric|min:0',
            'charges.*.discount' => 'nullable|numeric|min:0|max:100',
            'charges.*.internal_notes' => 'nullable|string|max:500',
            'charges.*.billed' => 'nullable|boolean',
            'charges.*.invoice_number' => 'nullable|string|unique:invoices,invoice_number|max:50',
            'charges.*.invoice_date' => 'nullable|date',
            'charges.*.total' => 'nullable|numeric|min:0',
            'charges.*.net_total' => 'nullable|numeric|min:0',
            //notes starts here
            'notes' => 'nullable|array',
            'notes.*.note' => 'nullable|string',
            //expense starts here
            'expenses' => 'nullable|array',
            'expenses.*.expense_type' => 'nullable|string|max:255',
            'expenses.*.expense_unit' => 'nullable|integer|min:1',
            'expenses.*.expense_rate' => 'nullable|numeric|min:0',
            'expenses.*.expense_amount' => 'nullable|numeric|min:0',
            'expenses.*.credit_reimbursement_amount' => 'nullable|numeric|min:0',
            'expenses.*.vendor_invoice_name' => 'nullable|string|max:255',
            'expenses.*.vendor_invoice_number' => 'nullable|string|max:100',
            'expenses.*.payment_reference_note' => 'nullable|string|max:255',
            'expenses.*.disputed_note' => 'nullable|string',
            'expenses.*.billed' => 'nullable|boolean',
            'expenses.*.paid' => 'nullable|boolean',
            'expenses.*.expense_disputed' => 'nullable|boolean',
            'expenses.*.disputed_amount' => 'nullable|numeric|min:0',
            //'disputed_date' => 'nullable|date',

            //container details
            'containers' => 'nullable|array',
            'containers.*.container' => 'nullable|string|max:255',
            'containers.*.container_size' => 'nullable|string|max:255',
            'containers.*.container_type' => 'nullable|string|max:255',
            'containers.*.container_number' => 'nullable|string|max:255',
            'containers.*.chasis' => 'nullable|string|max:255',
            'containers.*.chasis_size' => 'nullable|string|max:255',
            'containers.*.chasis_type' => 'nullable|string|max:255',
            'containers.*.chasis_vendor' => 'nullable|string|max:255',
            'containers.*.isLoaded' => 'nullable|string|max:255',

            //billto starts here
            
            'bill_tos' => 'nullable|array',
            'bill_tos.*.bill_to' => 'nullable|string|max:255',
            'bill_tos.*.quick_note' => 'nullable|integer|min:1',
            'bill_tos.*.branch_id' => 'nullable|integer|exists:branches,id',
            'bill_tos.*.customer_id' => 'nullable|integer|exists:customers,id',
            'bill_tos.*.driver_id' => 'nullable|integer|exists:drivers,id',
            'bill_tos.*.carrier_id' => 'nullable|integer|exists:carriers,id',
            
            //Ocean shipment
            'shipment_type' => 'nullable|string',
            'shipper_name' => 'nullable|string',
            'ocean_shipper_address' => 'nullable|string',
            'ocean_shipper_reference_number' => 'nullable|string',
            'carrier_name' => 'nullable|string',
            'carrier_reference_number' => 'nullable|string',
            'ocean_bill_of_ladening_number' => 'nullable|string',
            'consignee' => 'nullable|string',
            'consignee_phone' => 'nullable|string',
            'consignee_email' => 'nullable|email',
            'first_notify_party_name' => 'nullable|string',
            'first_notify_party_phone' => 'nullable|string',
            'first_notify_party_email' => 'nullable|email',
            'second_notify_party_name' => 'nullable|string',
            'second_notify_party_phone' => 'nullable|string',
            'second_notify_party_email' => 'nullable|email',
            'pre_carrier' => 'nullable|string',
            'vessel_aircraft_name' => 'nullable|string',
            'voyage_number' => 'nullable|string',
            'port_of_discharge' => 'nullable|string',
            'place_of_delivery' => 'nullable|string',
            'final_destination' => 'nullable|string',
            'port_of_landing' => 'nullable|string',
            'ocean_note' => 'nullable|string',
            'ocean_freight_charges' => 'nullable|numeric|min:0',
            'ocean_total_containers_in_words' => 'nullable|string',
            'no_original_bill_of_landing' => 'nullable|integer',
            'original_bill_of_landing_payable_at' => 'nullable|string',
            'shipped_on_board_date' => 'nullable|date',
            'signature' => 'nullable|file|mimes:jpg,jpeg,png,svg|max:2048',
            //'signature' => 'nullable|string',
            'goods' => 'nullable|array',
            'goods.*.goods_name' => 'nullable|string|max:255',
            'goods.*.branch_id' => 'nullable|integer|exists:branches,id',
            'goods.*.ocean_vin' => 'nullable|string|max:255',
            'goods.*.ocean_weight' => 'nullable|string|max:255',


            'file_path' => 'nullable', 
            'file_path.*' => 'file|mimes:jpeg,png,jpg,pdf|max:150',
            'file_path' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:150', 

            'delivery_type' => 'nullable|string',
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
