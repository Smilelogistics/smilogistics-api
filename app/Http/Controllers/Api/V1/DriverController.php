<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Driver;
use App\Models\DriverDocs;
use App\Mail\newDriverMail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class DriverController extends Controller
{
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
        $authUser = auth()->user();
        $branchId = $authUser->branch ? $authUser->branch->id : null;
        return response()->json($branchId);
        try {
            if (!$branchId) {
            
                return response()->json(['error' => $authUser .'User does not have an associated branch.'], 400);
            }
            $validator = Validator::make(request()->all(), [
                'fname' => 'required|string|max:255',
                'lname' => 'required|string|max:255',
                'mname' => 'nullable|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'driver_type' => 'required|string|max:255',
                'truck_id' => 'nullable|integer|exists:trucks,id',	
                'quick_note' => 'nullable|string',
                'dispatcher_note' => 'nullable|string',
                'driver_phone' => 'nullable|string|max:20|regex:/^\+?[0-9\-]+$/',
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
                'isAccessToMobileApp' => 'nullable|boolean',
                'mobile_settings' => 'nullable|string',
                'pay_via' => 'nullable|string|max:255',
                'company_name_paid_to' => 'nullable|string|max:255',
                'employer_identification_number' => 'nullable|string|max:255',
                'send_settlements_mail' => 'nullable|email',
                'print_settlements_under_this_company' => 'nullable|string',
                'flash_notes_to_dispatch' => 'nullable|string',
                'flash_notes_to_payroll' => 'nullable|string',
                'internal_notes' => 'nullable|string',
                'file_path' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5048',
            ]);

            if($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $validateData = $validator->validate();

            DB::beginTransaction();

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
                'driver_type' => $validateData['driver_type'],
                'truck_id' => $validateData['truck_id'],
                'quick_note' => $validateData['quick_note'],
                'dispatcher_note' => $validateData['dispatcher_note'],
                'driver_phone' => $validateData['driver_phone'],
                'driver_phone_carrier' => $validateData['driver_phone_carrier'],
                'driver_primary_address' => $validateData['driver_primary_address'],
                'driver_secondary_address' => $validateData['driver_secondary_address'],
                'driver_city' => $validateData['driver_city'],
                'driver_state' => $validateData['driver_state'],
                'driver_zip' => $validateData['driver_zip'],
                'emergency_contact_info' => $validateData['emergency_contact_info'],
                'hired_on' => $validateData['hired_on'],
                'years_of_experience' => $validateData['years_of_experience'],
                'endorsements' => $validateData['endorsements'],
                'rating' => $validateData['rating'],
                'tags' => $validateData['tags'],
                'notes_about_the_choices_made' => $validateData['notes_about_the_choices_made'],
                'isAccessToMobileApp' => $validateData['isAccessToMobileApp'],
                'mobile_settings' => $validateData['mobile_settings'],
                'pay_via' => $validateData['pay_via'],
                'company_name_paid_to' => $validateData['company_name_paid_to'],
                'employer_identification_number' => $validateData['employer_identification_number'],
                'send_settlements_mail' => $validateData['send_settlements_mail'],
                'print_settlements_under_this_company' => $validateData['print_settlements_under_this_company'],
                'flash_notes_to_dispatch' => $validateData['flash_notes_to_dispatch'],
                'flash_notes_to_payroll' => $validateData['flash_notes_to_payroll'],
                'internal_notes' => $validateData['internal_notes'],
            ]);

            if ($request->hasFile('file_path')) {
                $file = $request->file('file_path');
                $fileName = time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('drivers'), $fileName);
        
                DriverDocs::create([
                    'driver_id' => $driver->id,
                    'file_path' => $fileName
                ]);
            }

            DB::commit();
            ///if($createUser->email) wanted to check if email is present in the request but no need since its required in validation
            Mail::to($driver->user->email)->send(new newDriverMail($createUser));

            return response()->json([
                'message' => 'Driver created successfully.',
                'driver' => $driver
            ], 200);
            
        }
        catch (\Exception $e) {
            return response()->json(['message' => 'Driver not found.'], 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'driver_number' => 'nullable|string|max:255|unique:drivers,driver_number,' . $id,
            'driver_phone' => 'required|string|max:15|regex:/^\+?[0-9]{7,15}$/',
            'driver_phone_carrier' => 'nullable|string|max:255',
            'driver_primary_address' => 'required|string|max:500',
            'driver_secondary_address' => 'nullable|string|max:500',
            'driver_country' => 'required|string|max:255',
            'driver_state' => 'required|string|max:255',
            'driver_city' => 'required|string|max:255',
            'driver_zip' => 'required|string|max:20',
            'office' => 'nullable|string|max:255',
            'driver_type' => 'required|integer',
            'isAccessToMobileApp' => 'required|boolean',
            'mobile_settings' => 'nullable|integer',
            'emergency_contact_info' => 'nullable|json',
            'hired_on' => 'nullable|date',
            'terminated_on' => 'nullable|date|after_or_equal:hired_on',
            'years_of_experience' => 'nullable|integer|min:0|max:50',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'endorsements' => 'nullable|array',
            'endorsements.*' => 'string|max:255',
            'rating' => 'nullable|numeric|min:1|max:5',
            'notes_about_the_choices_made' => 'nullable|string|max:1000',
            'pay_via' => 'nullable|string|in:bank_transfer,paypal,cash',
            'company_name_paid_to' => 'nullable|string|max:255',
            'employer_identification_number' => 'nullable|string|max:20',
            'send_settlements_mail' => 'required|boolean',
            'print_settlements_under_this_company' => 'required|boolean',
            'flash_notes_to_dispatch' => 'nullable|string|max:500',
            'flash_notes_to_payroll' => 'nullable|string|max:500',
            'internal_notes' => 'nullable|string|max:1000',
            'driver_status' => 'required|string|in:active,inactive,suspended',
            // 'file' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
            // 'file_path' => 'nullable|string|max:500',
            'file_path' => 'nullable|string|max:500',
            'file' => 'nullable|string|max:500',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        $driver = Driver::findOrFail($id);
    
        $driver->update([
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
        ]);
    
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
    public function destroy(string $id)
    {
        
    }
}
