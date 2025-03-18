<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Driver;
use App\Models\DriverDocs;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return response ()->json([
            'message' => 'we got here created successfully'
        ]);
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
