<?php

namespace App\Http\Controllers\Api\V1;

use Log;
use Exception;
use App\Models\User;
use App\Models\Driver;
use App\Models\DriverDocs;
use App\Mail\newDriverMail;
use Illuminate\Http\Request;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DriverController extends Controller
{
    use FileUploadTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $driver = Driver::with(['branch', 'user', 'driverDocs'])->get();
        return response()->json($driver);
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
                'fname' => 'required|string|max:255',
                'lname' => 'required|string|max:255',
                'mname' => 'nullable|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'driver_type' => 'required|integer',
                'truck_id' => 'nullable|integer|exists:trucks,id',	
                'quick_note' => 'nullable|string',
                'dispatcher_note' => 'nullable|string',
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
                'tags' => 'nullable|string',
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

            //dd($validateData);

            DB::beginTransaction();

            $authUser = Auth::user();
            $branchId = $authUser->branch ? $authUser->branch->id : null;
            //return $authUser;
            Log::info('Authenticated User Branch ID:', ['branch_id' => $branchId]);

            $createUser = User::create([
                'fname' => $validateData['fname'],
                'lname' => $validateData['lname'],
                'mname' => $validateData['mname'],
                'email' => $validateData['email'],
                'password' => Hash::make('0000000000'), //10 zeros is your default password to the app
                'role' => 'driver',
            ]);

            $driver = Driver::create([
                'user_id' => $createUser->id,
                'branch_id' => $branchId,
                'driver_type' => $validateData['driver_type'] ?? null,
                'quick_note' => $validateData['quick_note'] ?? null,
                'dispatcher_note' => $validateData['dispatcher_note'] ?? null,
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
            ]);


            if ($request->hasFile('file_path')) {
                $files = $request->file('file_path');
                //$fileTitles = $request->input('file_titles', []);
        
                foreach ($files as $index => $file) {
                    try {
                        $filePath = $this->uploadFile($file, 'drivers');
                        if ($filePath) {
                            DriverDocs::create([
                                'driver_id' => $driver->id,
                                'file' => $filePath,
                                //'file_title' => $fileTitles[$index] ?? null,
                            ]);
                        } else {
                            \Log::error('File upload failed for file: ' . $file->getClientOriginalName());
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error uploading file: ' . $e->getMessage());
                    }
                }
            } else {
                \Log::error('No files found in the request.');
            }


            DB::commit();
            ///if($createUser->email) wanted to check if email is present in the request but no need since its required in validation
            Mail::to($driver->user->email)->send(new newDriverMail($createUser));

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
        $driver = Driver::with(['branch', 'user', 'driverDocs'])->findOrFail($id);
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
            'print_settlements_under_this_company' => 'sometimes|nullable|boolean',
            'flash_notes_to_dispatch' => 'sometimes|nullable|string|max:500',
            'flash_notes_to_payroll' => 'sometimes|nullable|string|max:500',
            'internal_notes' => 'sometimes|nullable|string|max:1000',
            'driver_status' => 'sometimes|nullable|string|in:active,inactive,suspended',
            'file' => 'sometimes|nullable|string|max:500',
        ]);
        
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        $driver = Driver::findOrFail($id);
    
        $driver->update($request->only([
            'driver_number' => $request->driver_number,
            'driver_phone' => $request->driver_phone,
            'driver_phone_carrier' => $request->driver_phone_carrier,
            'driver_primary_address' => $request->driver_primary_address,
            'driver_secondary_address' => $request->driver_secondary_address,
            'driver_country' => $request->driver_country,
            'driver_state' => $request->driver_state,
            'driver_city' => $request->driver_city,
            'driver_zip' => $request->driver_zip,
            'office' => $request->office,
            'driver_type' => $request->driver_type,
            'isAccessToMobileApp' => $request->isAccessToMobileApp,
            'mobile_settings' => $request->mobile_settings,
            'emergency_contact_info' => $request->emergency_contact_info,
            'hired_on' => $request->hired_on,
            'terminated_on' => $request->terminated_on,
            'years_of_experience' => $request->years_of_experience,
            'tags' => $request->tags,
            'endorsements' => $request->endorsements,
            'rating' => $request->rating,
            'notes_about_the_choices_made' => $request->notes_about_the_choices_made,
            'pay_via' => $request->pay_via,
            'company_name_paid_to' => $request->company_name_paid_to,
            'employer_identification_number' => $request->employer_identification_number,
            'send_settlements_mail' => $request->send_settlements_mail,
            'print_settlements_under_this_company' => $request->print_settlements_under_this_company,
            'flash_notes_to_dispatch' => $request->flash_notes_to_dispatch,
            'flash_notes_to_payroll' => $request->flash_notes_to_payroll,
            'internal_notes' => $request->internal_notes,
            'driver_status' => $request->driver_status
        ]));
    
        // Update or create DriverDocs record
        $driverDoc = DriverDocs::updateOrCreate(
            ['driver_id' => $driver->id],
            ['file' => $request->file_path]
        );
    
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public', $filename);
    
            // Update DriverDocs with the new file path
            $driverDoc->update(['file' => $filename, 'file_title' => $request->file_title]);

            //dd($driverDoc);
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
