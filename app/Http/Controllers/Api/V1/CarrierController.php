<?php

namespace App\Http\Controllers\Api\V1;

use DB;
use App\Models\User;
use App\Models\Carrier;
use App\Models\CarrierDocs;
use Illuminate\Http\Request;
use App\Traits\FileUploadTrait;
use App\Models\CarrierInsurance;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\CarrierAccountCreatedMail;
use Illuminate\Support\Facades\Validator;
use App\Mail\CarrierCreatedNotificationMail;
use App\Notifications\CarrierAccountCreated;
use App\Notifications\CarrierCreatedForAdmin;


class CarrierController extends Controller
{
    use FileUploadTrait;

    public function store(Request $request)
    {
        $authUser = auth()->user();
        $branchId = $authUser->branch ? $authUser->branch->id : null;

        $customerId = null;

        if ($authUser->role == 'customer') {
            $customerId = $authUser->customer->id;
        }

        //dd($customerId);

    
        if (!$branchId) {
            return response()->json(['message' => 'User is not associated with a branch'], 400);
        }
    
        try {
            $userData = $request->only(['name', 'email']);
            $carrierData = $request->except(['name', 'email', 'files', 'insurance']);
    
            // Validate user data
            $validateUser = Validator::make($userData, [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
            ]);
    
            if ($validateUser->fails()) {
                return response()->json(['errors' => $validateUser->errors()], 422);
            }
    
            // Validate carrier data
            $carrierValidator = Validator::make($carrierData, [
                'name' => 'nullable|string|max:255',
                'state_served' => 'nullable|string|max:255',
                'code' => 'nullable|string|max:50',
                'type' => 'nullable|string|max:100',
                
                // Carrier Identifiers
                'usdot_number' => 'nullable|string|max:50',
                'mc_number' => 'nullable|string|max:50',
                'scac' => 'nullable|string|max:50',
                'tax_id' => 'nullable|string|max:50',
                'carrier_number' => 'nullable|string|max:50',
                
                // Contact Information
                'contact_name' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'cell_phone' => 'nullable|string|max:20',
                'cell_carrier' => 'nullable|string|max:100',
                'office_phone' => 'nullable|string|max:20',
                'toll_free_number' => 'nullable|string|max:20',
                'fax_no' => 'nullable|string|max:20',
                
                // Address Information
                'primary_address' => 'nullable|string|max:255',
                'secondary_address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:50',
                'zip' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:50',
                
                // Operational Details
                'offices' => 'nullable|string|max:255',
                'carrier_access' => 'nullable|boolean',
                'show_payment_in_mobile_app' => 'nullable|boolean',
                'no_of_drivers' => 'nullable|integer|min:0',
                'power_units' => 'nullable|integer|min:0',
                'other_equipments' => 'nullable|string',
                'carrier_profile' => 'nullable|string|max:255',
                
                // Additional Carrier Information
                'rating' => 'nullable|numeric|between:0,5',
                'carries_this_cargo' => 'nullable|string',
                'note_about_choices' => 'nullable|string',
                'start_date' => 'nullable|date',
                'tag' => 'nullable|string|max:100',
                
                // Flash Notes
                'flash_note_to_riders_about_this_carrier' => 'nullable|string',
                'flash_note_to_payroll_about_this_carrier' => 'nullable|string',
                'internal_note' => 'nullable|string',
                'notes' => 'nullable|string',
                
                // Insurance Details
                'insurance_provider' => 'nullable|string|max:255',
                'insurance_expire' => 'nullable|date',
                'note_about_coverage' => 'nullable|string',
                'insurance_provider' => 'nullable|string|max:255',
                'insurance_expire' => 'nullable|date',
                'note_about_coverage' => 'nullable|string',
                'insurance' => 'nullable|array',
                'insurance.*.coverage' => 'nullable|string|max:255',
                'insurance.*.amount' => 'nullable|numeric',
                'insurance.*.policy_number' => 'nullable|string|max:255',
                'insurance.*.expires' => 'nullable|date',

                'insurance_provider' => 'nullable|string|max:255',
                'insurance_expire' => 'nullable|date',
                'note_about_coverage' => 'nullable|string',
                'insurance' => 'nullable|array',
                'insurance.*.coverage' => 'nullable|string|max:255',
                'insurance.*.amount' => 'nullable|numeric',
                'insurance.*.policy_number' => 'nullable|string|max:255',
                'insurance.*.expires' => 'nullable|date',
                
                // Payment Information
                'payment_terms' => 'nullable|string|max:255',
                'paid_via' => 'nullable|string|max:100',
                'account_number' => 'nullable|string|max:50',
                'routing_number' => 'nullable|string|max:50',
                'settlement_email_address' => 'nullable|email|max:255',
                'payment_mailling_address' => 'nullable|string|max:255',
                'payment_contact' => 'nullable|string|max:255',
                'payment_related_notes' => 'nullable|string',
                'payment_method' => 'nullable|string|max:100',
                'carrier_smile_id' => 'nullable|string|max:50',
                'data_exchange_option' => 'nullable|string|max:100',
                
                // File Upload
                'file' => 'nullable|array',
                'file.*' => 'file|mimes:jpeg,png,jpg,pdf|max:5120',
                'file_titles' => 'nullable|array',
                'file_titles.*' => 'string|max:255',


                'insurance_provider' => 'nullable|string|max:255',
                'insurance_expire' => 'nullable|date',
                'note_about_coverage' => 'nullable|string',
                'carrier_docs' => 'nullable|array',
                'carrier_docs.*' => 'file|max:5120', // Max 5MB for each file
                'carrier_docs_titles' => 'nullable|array',
                'carrier_docs_titles.*' => 'string|max:255',
                'insurance_coverage' => 'nullable|string|max:255',
                'insurance_amount' => 'nullable|numeric',
                'insurance_policy_number' => 'nullable|string|max:255',
                'insurance_expires' => 'nullable|date',
            ]);
    
            if ($carrierValidator->fails()) {
                return response()->json(['errors' => $carrierValidator->errors()], 422);
            }
    
            return DB::transaction(function () use ($userData, $carrierData, $customerId, $request, $branchId, $authUser, $carrierValidator) {
                // Create User
                $createUser = User::updateOrCreate(
                    ['email' => $userData['email']],
                    [
                        'fname' => $userData['name'],
                        'lname' => $userData['name'],
                        'email' => $userData['email'],
                        'user_type' => 'carrier',
                        'password' => Hash::make('12345678'),
                    ]
                );
    
                if ($createUser) {
                    $createUser->addRole('carrier');
                }
    
                // Create Carrier
                
                $carrier = Carrier::create([
                    'branch_id' => $branchId,
                    'customer_id' => $customerId,
                    'user_id' => $createUser->id,
                    ...$carrierValidator->validated()
                ]);
    
                // Store CarrierDocs (File Uploads)
                if ($request->hasFile('files')) {
                    foreach ($request->file('files') as $file) {
                        $path = $file->store('carrier_docs', 'public');
                        CarrierDocs::create([
                            'branch_id' => $branchId,
                            'carrier_id' => $carrier->id,
                            'file' => $path,
                            'file_title' => $file->getClientOriginalName(),
                        ]);
                    }
                }
    
                $insuranceData = $request->input('insurance') ?? [];
                //dd($insuranceData);
                foreach ($insuranceData as $insurance) {
                    CarrierInsurance::create([
                        'carrier_id' => $carrier->id,
                        'coverage' => $insurance['coverage'] ?? null,
                        'amount' => $insurance['amount'] ?? null,
                        'policy_number' => $insurance['policy_number'] ?? null,
                        'expires' => $insurance['expires'] ?? null,
                    ]);
                }
    
                //dd($userData);
                try {
                    Mail::to($userData['email'])->send(new CarrierAccountCreatedMail($userData));
                    Mail::to($authUser->email)->send(new CarrierCreatedNotificationMail($userData));
                    $authUser->notify(new CarrierCreatedForAdmin($userData));
                } catch (\Exception $e) {
                    \Log::error('Notification error: ' . $e->getMessage());
                }
    
                return response()->json([
                    'message' => 'Carrier created successfully',
                    'carrier' => $carrier
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id): JsonResponse
    {
        // Validate request
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'state_served' => 'nullable|string|max:255',
            'code' => 'required|string|max:50',
            'insurance_coverage' => 'nullable|string|max:255',
            'offices' => 'nullable|string|max:255',
            'carrier_number' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:100',
            'usdot_number' => 'nullable|string|max:50',
            'mc_number' => 'nullable|string|max:50',
            'scac' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
            'cell_phone' => 'nullable|string|max:20',
            'cell_carrier' => 'nullable|string|max:100',
            'carrier_access' => 'boolean',
            'show_payment_in_mobile_app' => 'boolean',
            'office_phone' => 'nullable|string|max:20',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'primary_address' => 'nullable|string|max:255',
            'secondary_address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'fax_no' => 'nullable|string|max:20',
            'toll_free_number' => 'nullable|string|max:20',
            'other_contact_info' => 'nullable|string|max:255',
            'no_of_drivers' => 'nullable|integer',
            'power_units' => 'nullable|integer',
            'other_equipments' => 'nullable|string|max:255',
            'rating' => 'nullable|numeric|min:0|max:5',
            'carries_this_cargo' => 'nullable|string|max:255',
            'note_about_choices' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'tag' => 'nullable|string|max:255',
            'flash_note_to_riders_about_this_carrier' => 'nullable|string|max:255',
            'flash_note_to_payroll_about_this_carrier' => 'nullable|string|max:255',
            'internal_note' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_expire' => 'nullable|date',
            'note_about_coverage' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string|max:100',
            'paid_via' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'routing_number' => 'nullable|string|max:50',
            'settlement_email_address' => 'nullable|email|max:255',
            'payment_mailling_address' => 'nullable|string|max:255',
            'payment_contact' => 'nullable|string|max:255',
            'payment_related_notes' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'carrier_smile_id' => 'nullable|string|max:100',
            'data_exchange_option' => 'nullable|string|max:255',
            'insurance' => 'nullable|array',
            'insurance.*.coverage' => 'required|string|max:100',
            'insurance.*.amount' => 'required|numeric|min:0',
            'insurance.*.policy_number' => 'required|string|max:100',
            'insurance.*.expires' => 'required|date',
        ]);
        $carrier = Carrier::findOrFail($id);
        $carrier->update($validatedData);

        if ($request->has('insurance')) {
            CarrierInsurance::where('carrier_id', $carrier->id)->delete();

            foreach ($request->insurance as $insurance) {
                CarrierInsurance::create([
                    'carrier_id' => $carrier->id,
                    'coverage' => $insurance['coverage'],
                    'amount' => $insurance['amount'],
                    'policy_number' => $insurance['policy_number'],
                    'expires' => $insurance['expires'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Carrier updated successfully',
            'carrier' => $carrier
        ], 200);
    }
}
