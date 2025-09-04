<?php

namespace App\Http\Controllers\Api\V1;

use Log;
use Exception;
use App\Models\User;
use App\Models\Driver;
use App\Models\DriverDocs;
use App\Mail\newDriverMail;
use Illuminate\Http\Request;
use App\Models\AppIntegration;
use Illuminate\Support\Carbon;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class DriverController extends Controller
{
    use FileUploadTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $branchId = auth()->user()->getBranchId();
        $driver = Driver::with(['branch', 'user', 'driverDocs', 'providers'])
        ->where('branch_id', $branchId)
        ->get();
        return response()->json($driver);
    }

    public function getFlashMessageShipment($id)
    {
        $user = auth()->user();
        $branchId = $user->getBranchId();

        $flashmessage = DB::table('drivers')
            ->where('id', $id)
            ->value('flash_notes_to_dispatch');

        return response()->json([
            'flash' => $flashmessage ?? null
        ]);
    }

    public function getFlashMessageAccounting($id)
    {
        $user = auth()->user();
        $branchId = $user->getBranchId();

        $flashmessage = DB::table('drivers')
            ->where('id', $id)
            ->value('flash_notes_to_payroll');

        return response()->json([
            'flash' => $flashmessage ?? null
        ]);
    }

    public function getTruckDrivers()
    {
        $user = auth()->user();
        $branchId = auth()->user()->getBranchId();
        $truckDriver = Driver::with(['branch', 'user', 'driverDocs', 'providers'])
        ->where('branch_id', $branchId)
        ->where('transport_type', '1')
        ->get();
        return response()->json($truckDriver);
    }
    

    public function getBikeDrivers()
    {
        $now = Carbon::now();
        
        $user = auth()->user();
        $branchId = auth()->user()->getBranchId();
        $bikeDriver = Driver::with(['branch', 'user', 'driverDocs', 'providers'])
        ->where('branch_id', $branchId)
        ->where('cdl_expires', '>', $now)
        ->where('transport_type', '2')
        ->get();
        return response()->json($bikeDriver);
    }

    /**
     * Show the form for creating a new resource.
     */
 

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            
            $validator = Validator::make(request()->all(), [
                'transport_type' => 'required|integer',
                'fname' => 'required|string|max:255',
                'lname' => 'nullable|string|max:255',
                'mname' => 'nullable|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',

                'driver_type' => 'required|integer',
                'truck_id' => 'nullable|integer|exists:trucks,id',	
                'driver_phone' => 'nullable|string|max:20',
                'driver_phone_carrier' => 'nullable|string|max:255',
                'driver_primary_address' => 'nullable|string|max:255',
                'driver_secondary_address' => 'nullable|string|max:255',
                'driver_city' => 'nullable|string|max:255',
                'driver_state' => 'nullable|string|max:255',
                'driver_zip' => 'nullable|string|max:20',
                'emergency_contact_info' => 'nullable|string|max:255',
                'hired_on' => 'nullable|date',
                'years_of_experience' => 'nullable|integer|min:0',
                'endorsements' => 'nullable|string',
                'rating' => 'nullable|numeric|min:0|max:5',
                'tags' => 'nullable',
                'notes_about_the_choices_made' => 'nullable|string',
                'isAccessToMobileApp' => 'nullable|integer',
                'mobile_settings' => 'nullable|string',
                'pay_via' => 'nullable|string|max:255',
                'company_name_paid_to' => 'nullable|string|max:255',
                'employer_identification_number' => 'nullable|string|max:255',
                'send_settlements_mail' => 'nullable|email',
                'print_settlements_under_this_company' => 'nullable|string',
                'flash_notes_to_dispatch' => 'nullable|string',
                'flash_notes_to_payroll' => 'nullable|string',
                'internal_notes' => 'nullable|string',

                //form w-9
                'name_tax_return' => 'nullable|string|max:80',
                'different_bussiness_name' => 'nullable|string|max:80',
                'wtype' => 'nullable|string|max:80',
                'other_type' => 'nullable|string|max:80',
                'waddress' => 'nullable|string|max:150',
                'wstate' => 'nullable|string|max:50',
                'wcity' => 'nullable|string|max:50',
                'wzip' => 'nullable|string|max:20',
                'wtaxid' => 'nullable|string|max:80',
                'wwssn' => 'nullable|string|max:80',
                'wwein' => 'nullable|string|max:80',
                'wpaid_via' => 'nullable|string|max:80',
                'waccountNumber' => 'nullable|string|max:20',
                'wroutingNumber' => 'nullable|string',
                'winternal_notes' => 'nullable|string',
                'licensessn' => 'nullable|string|max:30',
                'dob' => 'nullable|date|date_format:Y-m-d',
                'cdlnumber' => 'nullable|string|max:80',
                'license_state' => 'nullable|string|max:50',
                'cdl_expires' => 'nullable|date',
                'medical_number' => 'nullable|string|max:100',
                'medical_expires' => 'nullable',
                'twic_number' => 'nullable|string|max:80',
                'twic_expires' => 'nullable',
                'sealink_expires' => 'nullable',
                'annual_mvr' => 'nullable',
                'clearing_annual' => 'nullable',
                'liability_insurance_expires' => 'nullable',
                'insurance_provider' => 'nullable|string|max:100',
                'insurance_coverage' => 'nullable|string|max:30',
                'date_1' => 'nullable',
                'date_2' => 'nullable',
                'date_3' => 'nullable',
                'date_4' => 'nullable',
                'date_5' => 'nullable',
                'date_6' => 'nullable',
                'license_internal_notes' => 'nullable|string',

                
                'providers' => 'nullable',
                'providers.*card_device_linking_number' => 'nullable|string|max:255',
                'providers.*app_provider' => 'nullable|string|max:255',
                'providers.*quick_note' => 'nullable|string',


                  // Files
                'file_path' => 'nullable|array',
                'file_path.*' => 'file|mimes:jpeg,png,jpg,pdf|max:5120', // Only allow jpeg, png, jpg, and pdf files
                // 'file_titles' => 'nullable|array',
                // 'file_titles.*' => 'string|max:255',
                //'file_path' => 'nullable|string|max:255',
            ]);

            if($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validateData = $validator->validate();

            if (isset($validateData['tags'])) {
                if (is_string($validateData['tags'])) {
                    $tagsArray = explode(',', $validateData['tags']);
                } 
                elseif (is_string($validateData['tags']) && json_decode($validateData['tags'])) {
                    $tagsArray = json_decode($validateData['tags'], true);
                }
                else {
                    $tagsArray = $validateData['tags'];
                }
                
                $tagsArray = array_values(array_filter(array_map('trim', $tagsArray)));
                $validateData['tags'] = !empty($tagsArray) ? $tagsArray : null;
            }
            //dd($validateData);

            DB::beginTransaction();

            $authUser = Auth::user();
            $branchId = auth()->user()->getBranchId();
            //return $authUser;
            Log::info('Authenticated User Branch ID:', ['branch_id' => $branchId]);

            $createUser = User::create([
                'fname' => $validateData['fname'],
                'lname' => $validateData['lname'],
                'mname' => $validateData['mname'],
                'email' => $validateData['email'],
                'password' => Hash::make('123456789'), //10 zeros is your default password to the app
                'user_type' => 'driver',
            ]);
            $createUser->addRole($createUser->user_type);

            $driver = Driver::create([
                'user_id' => $createUser->id,
                'branch_id' => $branchId,
                'transport_type' => $validateData['transport_type'] ?? null,
                'driver_type' => $validateData['driver_type'] ?? null,
                'driver_phone' => $validateData['driver_phone'] ?? null,
                'driver_phone_carrier' => $validateData['driver_phone_carrier'] ?? null,
                'driver_primary_address' => $validateData['driver_primary_address'] ?? null,
                'driver_secondary_address' => $validateData['driver_secondary_address'] ?? null,
                'driver_city' => $validateData['driver_city'] ?? null,
                'driver_state' => $validateData['driver_state'] ?? null,
                'driver_zip' => $validateData['driver_zip'] ?? null,
                'emergency_contact_info' => $validateData['emergency_contact_info'] ?? null,
                'hired_on' => $validateData['hired_on'] ?? null,
                'years_of_experience' => $validateData['years_of_experience'] ?? null,
                'endorsements' => $validateData['endorsements'] ?? null,
                'rating' => $validateData['rating'] ?? null,
                'tags' => $validateData['tags'] ?? null,
                'notes_about_the_choices_made' => $validateData['notes_about_the_choices_made'] ?? null,
                'isAccessToMobileApp' => $validateData['isAccessToMobileApp'] ?? null,
                'mobile_settings' => $validateData['mobile_settings'] ?? null,
                'pay_via' => $validateData['pay_via'] ?? null,
                'company_name_paid_to' => $validateData['company_name_paid_to'] ?? null,
                'employer_identification_number' => $validateData['employer_identification_number'] ?? null,
                'send_settlements_mail' => $validateData['send_settlements_mail'] ?? null,
                'print_settlements_under_this_company' => $validateData['print_settlements_under_this_company'] ?? null,
                'flash_notes_to_dispatch' => $validateData['flash_notes_to_dispatch'] ?? null,
                'flash_notes_to_payroll' => $validateData['flash_notes_to_payroll'] ?? null,
                'internal_notes' => $validateData['internal_notes'] ?? null,


                //form w-9
                'name_tax_return' => $validateData['name_tax_return'] ?? null,
                'different_bussiness_name' => $validateData['different_bussiness_name'] ?? null,
                'wtype' => $validateData['wtype'] ?? null,
                'other_type' => $validateData['other_type'] ?? null,
                'waddress' => $validateData['waddress'] ?? null,
                'wstate' => $validateData['wstate'] ?? null,
                'wcity' => $validateData['wcity'] ?? null,
                'wzip' => $validateData['wzip'] ?? null,
                'wtaxid' => $validateData['wtaxid'] ?? null,
                'wwssn' => $validateData['wwssn'] ?? null,
                'wwein' => $validateData['wwein'] ?? null,
                'wpaid_via' => $validateData['wpaid_via'] ?? null,
                'waccountNumber' => $validateData['waccountNumber'] ?? null,
                'wroutingNumber' => $validateData['wroutingNumber'] ?? null,
                'winternal_notes' => $validateData['winternal_notes'] ?? null,
                'licensessn' => $validateData['licensessn'] ?? null,
                'dob' => $validateData['dob'] ?? null,
                'cdlnumber' => $validateData['cdlnumber'] ?? null,
                'license_state' => $validateData['license_state'] ?? null,
                'cdl_expires' => $validateData['cdl_expires'] ?? null,
                'medical_number' => $validateData['medical_number'] ?? null,
                'medical_expires' => $validateData['medical_expires'] ?? null,
                'twic_number' => $validateData['twic_number'] ?? null,
                'twic_expires' => $validateData['twic_expires'] ?? null,
                'sealink_expires' => $validateData['sealink_expires'] ?? null,
                'annual_mvr' => $validateData['annual_mvr'] ?? null,
                'clearing_annual' => $validateData['clearing_annual'] ?? null,
                'liability_insurance_expires' => $validateData['liability_insurance_expires'] ?? null,
                'insurance_provider' => $validateData['insurance_provider'] ?? null,
                'insurance_coverage' => $validateData['insurance_coverage'] ?? null,
                'license_internal_notes' => $validateData['license_internal_notes'] ?? null,

                'date_1' => $validateData['date_1'] ?? null,
                'date_2' => $validateData['date_2'] ?? null,
                'date_3' => $validateData['date_3'] ?? null,
                'date_4' => $validateData['date_4'] ?? null,
                'date_5' => $validateData['date_5'] ?? null,
                'date_6' => $validateData['date_6'] ?? null,

            ]);

            if (isset($validateData['providers'])) {
                foreach ($validateData['providers'] as $provider) {
                    AppIntegration::create([
                        'driver_id' => $driver->id,
                        'card_device_linking_number' => $provider['card_device_linking_number'],
                        'app_provider' => $provider['app_provider'],
                        'quick_note' => $provider['quick_note'],
                    ]);
                }
            }


            if ($request->hasFile('file_path')) {
                //dd($request->file('file_path'));
                $files = $request->file('file_path');
            
                // Normalize to array (even if it's one file)
                $files = is_array($files) ? $files : [$files];
            
                foreach ($files as $file) {
                    if ($file->isValid()) {

                         $filename = time() . '_' . $file->getClientOriginalName();
                        $path = $file->storeAs(
                            'driver',    // folder inside Wasabi bucket
                            $filename,  // unique filename
                            'wasabi'    // disk name from config/filesystems.php
                        );

                        $url = Storage::disk('wasabi')->url($path);
                        
                        // $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                        //     'folder' => 'Smile_logistics/Drivers'
                        // ]);
            
                        DriverDocs::create([
                            'driver_id' => $driver->id,
                            'file' => $url
                        ]);
                    }
                }
            } else {
                \Log::error('No files found in the request.');
            }
            //dd($request->email);
            ///if($createUser->email) wanted to check if email is present in the request but no need since its required in validation
            Mail::to($request->email)->send(new newDriverMail($createUser));

            DB::commit();
           

            return response()->json([
                'message' => 'Driver created successfully 🚀',
                'driver' => $driver
            ], 200);
            
        }
        catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong 😫.'. $e], 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $driver = Driver::with(['branch', 'user', 'driverDocs', 'providers'])->findOrFail($id);
        return response()->json(['driver' => $driver], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'transport_type' => 'sometimes|nullable|integer',
            'driver_number' => 'sometimes|nullable|string|max:255|unique:drivers,driver_number,' . $id,
            'driver_phone' => 'sometimes|nullable|string|max:25|regex:/^\+?[0-9]{7,15}$/',
            'driver_phone_carrier' => 'sometimes|nullable|string|max:255',
            'driver_primary_address' => 'sometimes|nullable|string|max:500',
            'driver_secondary_address' => 'sometimes|nullable|string|max:500',
            'driver_country' => 'sometimes|nullable|string|max:255',
            'driver_state' => 'sometimes|nullable|string|max:255',
            'driver_city' => 'sometimes|nullable|string|max:255',
            'driver_zip' => 'sometimes|nullable|string|max:20',
            'office' => 'sometimes|nullable|string|max:255',
            'driver_type' => 'sometimes|nullable|integer',
            'isAccessToMobileApp' => 'sometimes|nullable|boolean',
            'mobile_settings' => 'sometimes|nullable|integer',
            'emergency_contact_info' => 'sometimes|nullable|string|max:500',
            'hired_on' => 'sometimes|nullable|date',
            'terminated_on' => 'sometimes|nullable|date|after_or_equal:hired_on',
            'years_of_experience' => 'sometimes|nullable|integer|min:0|max:50',
            'tags' => 'sometimes|nullable|array',
            'tags.*' => 'sometimes|string|max:255',
            'endorsements' => 'sometimes|nullable|array',
            'endorsements.*' => 'sometimes|string|max:255',
            'rating' => 'sometimes|nullable|numeric|min:1|max:5',
            'notes_about_the_choices_made' => 'sometimes|nullable|string|max:1000',
            'pay_via' => 'sometimes|nullable|string|max:255',
            'company_name_paid_to' => 'sometimes|nullable|string|max:255',
            'employer_identification_number' => 'sometimes|nullable|string|max:20',
            'send_settlements_mail' => 'sometimes|nullable|email',
            'print_settlements_under_this_company' => 'sometimes|nullable|string|max:500',
            'flash_notes_to_dispatch' => 'sometimes|nullable|string|max:500',
            'flash_notes_to_payroll' => 'sometimes|nullable|string|max:500',
            'internal_notes' => 'sometimes|nullable|string|max:1000',
            'driver_status' => 'sometimes|nullable|string|in:active,inactive,suspended',

            //form w-9
            'name_tax_return' => 'nullable|string|max:80',
            'different_bussiness_name' => 'nullable|string|max:80',
            'wtype' => 'nullable|string|max:80',
            'other_type' => 'nullable|string|max:80',
            'waddress' => 'nullable|string|max:150',
            'wstate' => 'nullable|string|max:50',
            'wcity' => 'nullable|string|max:50',
            'wzip' => 'nullable|string|max:20',
            'wtaxid' => 'nullable|string|max:80',
            'wwssn' => 'nullable|string|max:80',
            'wwein' => 'nullable|string|max:80',
            'wpaid_via' => 'nullable|string|max:80',
            'waccountNumber' => 'nullable|string|max:20',
            'wroutingNumber' => 'nullable|string',
            'winternal_notes' => 'nullable|string',
            'licensessn' => 'nullable|string|max:30',
            'dob' => 'nullable|date|date_format:Y-m-d',
            'cdlnumber' => 'nullable|string|max:80',
            'license_state' => 'nullable|string|max:50',
            'cdl_expires' => 'nullable|date|date_format:Y-m-d',
            'medical_number' => 'nullable|string|max:100',
            'medical_expires' => 'nullable|date|date_format:Y-m-d',
            'twic_number' => 'nullable|string|max:80',
            'twic_expires' => 'nullable|date|date_format:Y-m-d',
            'sealink_number' => 'nullable|string|max:80',
            'sealink_expires' => 'nullable|date|date_format:Y-m-d',
            'annual_mvr' => 'nullable|date|date_format:Y-m-d',
            'clearing_annual' => 'nullable|date|date_format:Y-m-d',
            'liability_insurance_expires' => 'nullable|date|date_format:Y-m-d',
            'insurance_provider' => 'nullable|string|max:100',
            'insurance_coverage' => 'nullable|string|max:30',
            'date_1' => 'nullable|date|date_format:Y-m-d',
            'date_2' => 'nullable|date|date_format:Y-m-d',
            'date_3' => 'nullable|date|date_format:Y-m-d',
            'date_4' => 'nullable|date|date_format:Y-m-d',
            'date_5' => 'nullable|date|date_format:Y-m-d',
            'date_6' => 'nullable|date|date_format:Y-m-d',
            'license_internal_notes' => 'nullable|string',
            'providers' => 'nullable',
            'providers.*card_device_linking_number' => 'nullable|string|max:255',
            'providers.*app_provider' => 'nullable|string|max:255',
            'providers.*quick_note' => 'nullable|string',

            'file' => 'sometimes|nullable|string|max:500',
        ]);
        
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (isset($request->tags)) {
            if (is_string($request->tags)) {
                $tagsArray = explode(',', $validatedData->tags);
            } 
            elseif (is_string($request->tags) && json_decode($request->tags)) {
                $tagsArray = json_decode($requestt->tags, true);
            }
            else {
                $tagsArray = $request->tags;
            }
            
            $tagsArray = array_values(array_filter(array_map('trim', $tagsArray)));
            $request->tags = !empty($tagsArray) ? $tagsArray : null;
        }
    
        $driver = Driver::findOrFail($id);
    
        $driver->update($request->only([
            'transport_type',
            'driver_number',
            'driver_phone',
            'driver_phone_carrier',
            'driver_primary_address',
            'driver_secondary_address',
            'driver_country',
            'driver_state',
            'driver_city',
            'driver_zip',
            'office',
            'driver_type',
            'isAccessToMobileApp',
            'mobile_settings',
            'emergency_contact_info',
            'hired_on',
            'terminated_on',
            'years_of_experience',
            'tags',
            'endorsements',
            'rating',
            'notes_about_the_choices_made',
            'pay_via',
            'company_name_paid_to',
            'employer_identification_number',
            'send_settlements_mail',
            'print_settlements_under_this_company',
            'flash_notes_to_dispatch',
            'flash_notes_to_payroll',
            'internal_notes',
            'driver_status',
            'name_tax_return',
            'different_bussiness_name',
            'wtype',
            'other_type',
            'waddress',
            'wstate',
            'wcity',
            'wzip',
            'wphone',
            'wemail',
            'wtaxid',
            'wwssn',
            'wwein',
            'wpaid_via',
            'waccountNumber',
            'wroutingNumber',
            'winternal_notes',
            'licensessn',
            'dob',
            'cdlnumber',
            'license_state',
            'cdl_expires',
            'medical_number',
            'medical_expires',
            'twic_number',
            'twic_expires',
            'sealink_number',
            'sealink_expires',
            'annual_mvr',
            'clearing_annual',
            'liability_insurance_expires',
            'insurance_provider',
            'insurance_coverage',
            'date_1',
            'date_2',
            'date_3',
            'date_4',
            'date_5',
            'date_6',
            'license_internal_notes'

        ]));
    
        // Update or create DriverDocs record
        // $driverDoc = DriverDocs::updateOrCreate(
        //     ['driver_id' => $driver->id],
        //     ['file' => $request->file_path]
        // );

        if (isset($validateData['providers'])) {
            foreach ($validateData['providers'] as $providerData) {
                AppIntegration::updateOrCreate(
                    [
                        'driver_id' => $driver->id
                    ],
                    [
                        'app_provider' => $providerData['app_provider'],
                        'card_device_linking_number' => $providerData['card_device_linking_number'],
                        'quick_note' => $providerData['quick_note'],
                    ]
                );
            }
        }


        if ($request->hasFile('file_path')) {
            //dd($request->file('file_path'));
            $files = $request->file('file_path');
        
            // Normalize to array (even if it's one file)
            $files = is_array($files) ? $files : [$files];
        
            foreach ($files as $file) {
                if ($file->isValid()) {

                     $filename = time() . '_' . $file->getClientOriginalName();
                        $path = $file->storeAs(
                            'driver',    // folder inside Wasabi bucket
                            $filename,  // unique filename
                            'wasabi'    // disk name from config/filesystems.php
                        );

                        $url = Storage::disk('wasabi')->url($path);
                    // $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                    //     'folder' => 'Smile_logistics/Drivers'
                    // ]);
        
                    DriverDocs::create([
                        'driver_id' => $driver->id,
                        'file' => $url
                    ]);
                }
            }
        }
        
    
        return response()->json(['message' => 'Data updated successfully', 'driver' => $driver]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->delete();
        return response()->json(['message' => 'Driver deleted successfully']);
    }
}
