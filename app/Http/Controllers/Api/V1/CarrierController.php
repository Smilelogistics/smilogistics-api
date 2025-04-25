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
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;


class CarrierController extends Controller
{
    use FileUploadTrait;

    public function index()
    {
        // return response()->json(['invoices' => $invoices], 200);
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        $customerId = $user->customer ? $user->customer->id : null;
        //dd($branchId, $customerId);
        if ($user->hasRole('businessadministrator')) {
            $carriers = Carrier::where('branch_id', $branchId)
                            ->with('customer', 'user')
                            ->latest()
                            ->get();
        }
        elseif ($user->hasRole('customer')) {
            $carriers = Carrier::where('customer_id', $customerId)
                            ->with('branch', 'user')
                            ->latest()
                            ->get();
        } else {
            $carriers = collect();
        }

        return response()->json(['carriers' => $carriers], 200);
    }

    public function show($id)
    {
        try {
            $carrier = Carrier::with(['branch', 'carrierDocs', 'carrierInsurance'])->findOrFail($id);
            return response()->json(['carrier' => $carrier], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Carrier not found'], 404);
        }
    }

    public function store(Request $request)
{
    $authUser = auth()->user();
    $branchId = $authUser->branch->id ?? null;
    $customerId = $authUser->role === 'customer' ? $authUser->customer->id : null;
    $openAccount = false;
    
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

            'state_served' => 'nullable|array',
            'state_served.*' => 'nullable|string|max:50',
            'carries_this_cargo' => 'nullable|array',
            'carries_this_cargo.*' => 'nullable|string|max:255',
            'carrier_profile' => 'nullable|array',
            'carrier_profile.*' => 'nullable|string|max:255',
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
            'office_phone' => 'nullable|string|max:20',

            // Address Information
            'primary_address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:50',
            'fax_no' => 'nullable|string|max:20',
            'toll_free' => 'nullable|string|max:20',
            'other_contact_info' => 'nullable|string|max:255',
            'no_of_drivers' => 'nullable|integer',
            'power_units' => 'nullable|integer',
            'other_equipments' => 'nullable|string|max:255',
            'rating' => 'nullable|string|max:255',
            'note_about_choices' => 'nullable|string|max:255',

            // Insurance Details
            'insurances' => 'nullable|array',
            'insurance.*.coverage' => 'nullable|string|max:255',
            'insurance.*.amount' => 'nullable|numeric',
            'insurance.*.policy_number' => 'nullable|string|max:255',
            'insurance.*.expires' => 'nullable',

            // File Uploads
            'file' => 'nullable|array',
            'file.*' => 'file|mimes:jpeg,png,jpg,pdf|max:5120',

            'carrier_docs' => 'nullable|array',
            'carrier_docs.*' => 'file|max:5120',
        ]);

        if ($carrierValidator->fails()) {
            return response()->json(['errors' => $carrierValidator->errors()], 422);
        }

        return DB::transaction(function () use ($userData, $carrierData, $customerId, $request, $branchId, $authUser, $openAccount, $carrierValidator) {
            $createUser = null;

            // Create User if needed
            if ($openAccount) {
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
            }

            $arrayFields = [
                'state_served' => $request->input('state_served', []),
                'carrier_profile' => $request->input('carrier_profile', []),
                'carries_this_cargo' => $request->input('carries_this_cargo', [])
            ];

            // Convert single values to arrays if needed
            foreach ($arrayFields as $field => $value) {
                if (!is_array($value)) {
                    $arrayFields[$field] = [$value];
                }
            }
 $arrayFields = [
                'state_served' => $request->input('state_served', []),
                'carrier_profile' => $request->input('carrier_profile', []),
                'carries_this_cargo' => $request->input('carries_this_cargo', [])
            ];

            // Convert single values to arrays if needed
            foreach ($arrayFields as $field => $value) {
                if (!is_array($value)) {
                    $arrayFields[$field] = [$value];
                }
            }

            // Create Carrier
            $carrier = Carrier::create([
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'user_id' => $createUser ? $createUser->id : null,
                'status' => 'active',
                'state_served' => !empty($carrierData['state_served']) 
                ? json_encode(array_filter($carrierData['state_served'])) 
                : null,
            'carrier_profile' => !empty($carrierData['carrier_profile']) 
                ? json_encode(array_filter($carrierData['carrier_profile'])) 
                : null,
            'carries_this_cargo' => !empty($carrierData['carries_this_cargo']) 
                ? json_encode(array_filter($carrierData['carries_this_cargo'])) 
                : null,
                'name' => $request->input('name'),
                'code' => $request->input('code'),
                'type' => $request->input('type'),
                'usdot_number' => $request->input('usdot_number'),
                'mc_number' => $request->input('mc_number') ?? 0000,
                'scac' => $request->input('scac'),
                'tax_id' => $request->input('tax_id'),
                'contact_name' => $request->input('contact_name'),
                'email' => $request->input('email'),
                'cell_phone' => $request->input('cell_phone'),
                'office_phone' => $request->input('office_phone'),
                'primary_address' => $request->input('primary_address'),
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'zip' => $request->input('zip'),
                'country' => $request->input('country'),
                'fax_no' => $request->input('fax_no'),
                'toll_free_number' => $request->input('toll_free_number'),
                'other_contact_info' => $request->input('other_contact_info'),
                'rating' => $request->input('rating'),
                'note_about_choices' => $request->input('note_about_choices'),
                'other_equipments' => $request->input('other_equipments'),
                'no_of_drivers' => $request->input('no_of_drivers'),
                'power_units' => $request->input('power_units'),
                'insurance_provider' => $request->input('insurance_provider'),
                'insurance_expire' => $request->input('insurance_expire'),
                'payment_terms' => $request->input('payment_terms'),
                'paid_via' => $request->input('paid_via'),
                'account_number' => $request->input('account_number'),
                'routing_number' => $request->input('routing_number'),
                'note_about_coverage' => $request->input('note_about_coverage'),
                'settlement_email_address' => $request->input('settlement_email_address'),
                'payment_mailling_address' => $request->input('payment_mailling_address'),
                'payment_contact' => $request->input('payment_contact'),
                'payment_related_notes' => $request->input('payment_related_notes'),

            ]);

            // Store CarrierDocs (File Uploads)
            // if ($request->has('insurance')) {
            //     foreach ($request->insurance as $insurance) {
            //         $carrier->carrierInsurance()->create(
            //             ['carrier_id' => $carrier->id ],
            //             [
            //                 'policy_number' => $insurance['policy_number'],
            //                 'coverage' => $insurance['coverage'],
            //                 'amount' => $insurance['amount'],
            //                 'expires' => $insurance['expires']
            //             ]
            //         );
            //     }
            // }
            
    
    
            if ($request->hasFile('file')) {
                $files = $request->file('file');
            
                // Normalize to array (even if it's one file)
                $files = is_array($files) ? $files : [$files];
            
                foreach ($files as $file) {
                    if ($file->isValid()) {
                        $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                            'folder' => 'Smile_logistics/Carrier',
                        ]);
            
                        $carrier->carrierDocs()->create(
                            [
                                'file' => $uploadedFile->getSecurePath(),
                                'public_id' => $uploadedFile->getPublicId()
                        ]);
                    }
                }
            }
    
            // Store Insurance Data
            foreach ($request->input('insurances', []) as $insurance) {
                CarrierInsurance::create([
                    'carrier_id' => $carrier->id,
                    'coverage' => $insurance['coverage'] ?? null,
                    'amount' => $insurance['amount'] ?? null,
                    'policy_number' => $insurance['policy_number'] ?? null,
                    'expires' => $insurance['expires'] ?? null,
                ]);
            }

            // Send notifications
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
        return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
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
            'state_served' => 'nullable|array',
            'state_served.*' => 'nullable|string|max:50',
            'carries_this_cargo' => 'nullable|array',
            'carries_this_cargo.*' => 'nullable|string|max:255',
            'carrier_profile' => 'nullable|array',
            'carrier_profile.*' => 'nullable|string|max:255',

            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:50',
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
            'note_about_choices' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'tag' => 'nullable|string|max:255',
            'flash_note_to_riders_about_this_carrier' => 'nullable|string|max:255',
            'flash_note_to_payroll_about_this_carrier' => 'nullable|string|max:255',
            'internal_note' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',

            'insurance_provider' => 'nullable|string|max:255',
            'insurance_expire' => 'nullable|date',
            'note_about_coverage' => 'nullable|string',
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
            'insurances' => 'nullable|array',
            'insurance.*.coverage' => 'nullable|string|max:100',
            'insurance.*.amount' => 'nullable|numeric|min:0',
            'insurance.*.policy_number' => 'nullable|string|max:100',
            'insurance.*.expires' => 'nullable',

            // File Upload
            'file' => 'nullable|array',
            'file.*' => 'file|mimes:jpeg,png,jpg,pdf|max:5120',
            'file_titles' => 'nullable|array',
            'file_titles.*' => 'string|max:255',
        ]);
        $arrayFields = [
            'state_served' => $request->input('state_served', []),
            'carrier_profile' => $request->input('carrier_profile', []),
            'carries_this_cargo' => $request->input('carries_this_cargo', [])
        ];

        // Convert single values to arrays if needed
        foreach ($arrayFields as $field => $value) {
            if (!is_array($value)) {
                $arrayFields[$field] = [$value];
            }
        }
$arrayFields = [
            'state_served' => $request->input('state_served', []),
            'carrier_profile' => $request->input('carrier_profile', []),
            'carries_this_cargo' => $request->input('carries_this_cargo', [])
        ];

        // Convert single values to arrays if needed
        foreach ($arrayFields as $field => $value) {
            if (!is_array($value)) {
                $arrayFields[$field] = [$value];
            }
        }
    

        $carrier = Carrier::findOrFail($id);
        $carrier->update([
            'state_served' => !empty($carrierData['state_served']) 
            ? json_encode(array_filter($carrierData['state_served'])) 
            : null,
        'carrier_profile' => !empty($carrierData['carrier_profile']) 
            ? json_encode(array_filter($carrierData['carrier_profile'])) 
            : null,
        'carries_this_cargo' => !empty($carrierData['carries_this_cargo']) 
            ? json_encode(array_filter($carrierData['carries_this_cargo'])) 
            : null,
            ...$validatedData
        ]);

        if ($request->has('insurances')) {
            foreach ($request->insurances as $insurance) {
                $carrier->carrierInsurance()->updateOrCreate(
                    ['carrier_id' => $carrier->id ],
                    [
                        'policy_number' => $insurance['policy_number'],
                        'coverage' => $insurance['coverage'],
                        'amount' => $insurance['amount'],
                        'expires' => $insurance['expires']
                    ]
                );
            }
        }
        


        if ($request->hasFile('file')) {
            $files = $request->file('file');
        
            // Normalize to array (even if it's one file)
            $files = is_array($files) ? $files : [$files];
        
            foreach ($files as $file) {
                if ($file->isValid()) {
                    $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                        'folder' => 'Smile_logistics/Carrier',
                    ]);
        
                    $carrier->carrierDocs()->updateOrCreate(
                        [ 
                            'carrier_id' => $carrier->id],
                        [
                            'file' => $uploadedFile->getSecurePath(),
                            'public_id' => $uploadedFile->getPublicId()
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Carrier updated successfully',
            'carrier' => $carrier
        ], 200);
    }

    public function destroy($id)
    {
        $carrier = Carrier::findOrFail($id);
        $carrier->delete();
        return response()->json(['message' => 'Carrier deleted successfully'], 200);
    }
}
