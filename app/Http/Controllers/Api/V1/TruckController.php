<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Truck;
use App\Models\Driver;
use App\Models\TruckDoc;
use App\Models\TruckDriver;
use Illuminate\Http\Request;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DriverAssignedTruckNotification;


class TruckController extends Controller
{
    use FileUploadTrait;
    public function index()
    {
        $trucks = Truck::with(['truckDocs', 'TruckDriver.driver.user', 'customer', 'branch'])->get();
        return response()->json(['trucks' => $trucks], 200);
    }
    public function show($id)
    {
        try {
            $truck = Truck::with(['truckDocs', 'TruckDriver.driver.user'])->findOrFail($id);
            return response()->json(['truck' => $truck], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Truck not found.'], 404);
        }
    }
    public function store(Request $request)
    {

        $authUser = auth()->user();
        $branchId = $authUser->branch ? $authUser->branch->id : null;
        //dd($branchId);
        try {
            $validateTruck = Validator::make($request->all(), [
                //'branch_id' => 'required|integer|exists:branches,id',
                'customer_id' => 'nullable|integer|exists:customers,id',
                //'user_id' => 'required|integer|exists:users,id',
                'truck_number' => 'nullable|string|max:50',
                'office' => 'nullable|string|max:255',
                'make_model' => 'nullable|string|max:100',
                'make_year' => 'nullable|integer|min:1900|max:' . date('Y'),
                'engine_year' => 'nullable|integer|min:1900|max:' . date('Y'),
                'vehicle_number' => 'nullable|string|max:50',
                'license_plate_number' => 'nullable|string|max:20',
                'license_plate_state' => 'nullable|string|max:2',
                'service_start_date' => 'nullable|date',
                'reffered_by' => 'nullable|string|max:255',
                'tags' => 'nullable|string|max:255',
                'endorsements' => 'nullable|string|max:255',
                'flash_notes_to_dispatchers' => 'nullable|string|max:500',
                'flash_notes_to_payroll' => 'nullable|string|max:500',
                'internal_notes' => 'nullable|string|max:500',
                'createSettlement' => 'boolean',
                'truck_owner_details' => 'nullable|string|max:255',
                'truck_type' => 'nullable|string|max:100',
                'truck_alt_biz_name' => 'nullable|string|max:255',
                'truck_address' => 'nullable|string|max:255',
                'truck_city' => 'nullable|string|max:100',
                'truck_state' => 'nullable|string|max:2',
                'truck_zip' => 'nullable|string|max:10',
                'truck_phone' => 'nullable|string|max:20',
                'truck_email' => 'nullable|email|max:255',
                'isSSNorEIN' => 'nullable',
                'ssn' => 'nullable|string|max:11',
                'ein' => 'nullable|string|max:10',
                'paid_via' => 'nullable|string|max:100',
                'account_number' => 'nullable|string|max:50',
                'routing_number' => 'nullable|string|max:50',
                'note_related_to_owner' => 'nullable|string|max:500',
                'registration_expires' => 'nullable|date',
                'annual_inspection_expires' => 'nullable|date',
                'quarterly_inspection_expires' => 'nullable|date',
                'bobtail_insurance_expires' => 'nullable|date',
                'monthly_maintanance_expires' => 'nullable|date',
                'smoke_inspection_expires' => 'nullable|date',
                'overweight_permit_expires' => 'nullable|date',
                'last_paper_work_received' => 'nullable|date',
                'last_log_received' => 'nullable|date',
                'insurance_expires' => 'nullable|date',
                'insurance_provider' => 'nullable|string|max:255',
                'insurance_coverage' => 'nullable|string|max:255',
                'note_about_insurance' => 'nullable|string|max:500',
                'ifta_note' => 'nullable|string|max:500',
                'plate_program_note' => 'nullable|string|max:500',
                'note_about_other_choices' => 'nullable|string|max:500',
                'other_options' => 'nullable|string|max:500',
                'eld_provider' => 'nullable|string|max:255',
                'eld_serial_number' => 'nullable|string|max:100',
                'tablet_serial_number' => 'nullable|string|max:100',
                'dash_cam_serial_number' => 'nullable|string|max:100',
                'rfid_number' => 'nullable|string|max:100',
                'transponder_number' => 'nullable|string|max:100',
                'tablet_provider' => 'nullable|string|max:255',

                 // Files
                'file' => 'nullable|array',
                'file.*' => 'file|mimes:jpeg,png,jpg,pdf|max:5120', // Only allow jpeg, png, jpg, and pdf files
                'file_titles' => 'nullable|array',
                'file_titles.*' => 'string|max:255',


                // driver attached
                'truck_id' => 'nullable|exists:trucks,id',
                'driver_id' => 'nullable|exists:drivers,id',
                'quick_notes' => 'nullable|string',
                'note_to_dispatcher' => 'nullable|string',
            ]);

            if ($validateTruck->fails()) {
                return response()->json(['errors' => $validateTruck->errors()], 422);
            }

            DB::beginTransaction();
            $validatedTruck = $validateTruck->validated();
            $truck = Truck::create([
                'branch_id' => $branchId,
                'user_id' => $authUser->id,
                ...$validatedTruck
            ]);

            if ($truck) {
                if ($request->hasFile('file_path')) {
                    $files = $request->file('file_path');
                    //$fileTitles = $request->input('file_titles', []);
            
                    foreach ($files as $index => $file) {
                        try {
                            $filePath = $this->uploadFile($file, 'trucks');
                            if ($filePath) {
                                TruckDoc::create([
                                    'truck_id' => $truck->id,
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
               

                if ($request->filled('driver_id')) {
                    TruckDriver::create([
                        'truck_id' => $truck->id,
                        'driver_id' => $request->driver_id,
                        'quick_notes' => $request->quick_notes,
                        'note_to_dispatcher' => $request->note_to_dispatcher
                    ]);
                }

                $driver = Driver::find($request->driver_id);
                //dd($driver->user);
                
                // $userNotification = $driver->user->email;
                // dd($userNotification);
                if ($driver && $driver->user) {
                    $user = $driver->user;
                    $user->notify(new DriverAssignedTruckNotification($truck));
                }

                DB::commit();
                return response()->json(['message' => 'Truck created successfully 🚀'], 201);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $authUser = auth()->user();
        $branchId = $authUser->branch ? $authUser->branch->id : null;

        try {
            $validateTruck = Validator::make($request->all(), [
                'customer_id' => 'nullable|integer|exists:customers,id',
                'truck_number' => 'nullable|string|max:50',
                'office' => 'nullable|string|max:255',
                'make_model' => 'required|string|max:100',
                'make_year' => 'nullable|integer|min:1900|max:' . date('Y'),
                'engine_year' => 'nullable|integer|min:1900|max:' . date('Y'),
                'vehicle_number' => 'nullable|string|max:50',
                'license_plate_number' => 'required|string|max:20',
                'license_plate_state' => 'nullable|string|max:2',
                'service_start_date' => 'nullable|date',
                'reffered_by' => 'nullable|string|max:255',
                'tags' => 'nullable|string|max:255',
                'endorsements' => 'nullable|string|max:255',
                'flash_notes_to_dispatchers' => 'nullable|string|max:500',
                'flash_notes_to_payroll' => 'nullable|string|max:500',
                'internal_notes' => 'nullable|string|max:500',
                'truck_owner_details' => 'nullable|string|max:255',
                'truck_type' => 'nullable|string|max:100',
                'truck_alt_biz_name' => 'nullable|string|max:255',
                'truck_address' => 'nullable|string|max:255',
                'truck_city' => 'nullable|string|max:100',
                'truck_state' => 'nullable|string|max:2',
                'truck_zip' => 'nullable|string|max:10',
                'truck_phone' => 'nullable|string|max:20',
                'truck_email' => 'nullable|email|max:255',
                'isSSNorEIN' => 'nullable',
                'ssn' => 'nullable|string|max:11',
                'ein' => 'nullable|string|max:10',
                'paid_via' => 'nullable|string|max:100',
                'account_number' => 'nullable|string|max:50',
                'routing_number' => 'nullable|string|max:50',
                'note_related_to_owner' => 'nullable|string|max:500',
                'registration_expires' => 'nullable|date',
                'annual_inspection_expires' => 'nullable|date',
                'quarterly_inspection_expires' => 'nullable|date',
                'bobtail_insurance_expires' => 'nullable|date',
                'monthly_maintanance_expires' => 'nullable|date',
                'smoke_inspection_expires' => 'nullable|date',
                'overweight_permit_expires' => 'nullable|date',
                'last_paper_work_received' => 'nullable|date',
                'last_log_received' => 'nullable|date',
                'insurance_expires' => 'nullable|date',
                'insurance_provider' => 'nullable|string|max:255',
                'insurance_coverage' => 'nullable|string|max:255',
                'note_about_insurance' => 'nullable|string|max:500',
                'ifta_note' => 'nullable|string|max:500',
                'plate_program_note' => 'nullable|string|max:500',
                'note_about_other_choices' => 'nullable|string|max:500',
                'other_options' => 'nullable|string|max:500',
                'eld_provider' => 'nullable|string|max:255',
                'eld_serial_number' => 'nullable|string|max:100',
                'tablet_serial_number' => 'nullable|string|max:100',
                'dash_cam_serial_number' => 'nullable|string|max:100',
                'rfid_number' => 'nullable|string|max:100',
                'transponder_number' => 'nullable|string|max:100',
                'tablet_provider' => 'nullable|string|max:255',
                
                // files
                'file' => 'nullable|array',
                'file.*' => 'file|max:5120',
                'file_titles' => 'nullable|array',
                'file_titles.*' => 'string|max:255',

                // driver attached
                'truck_id' => 'nullable|exists:trucks,id',
                'driver_id' => 'nullable|exists:drivers,id',
                'quick_notes' => 'nullable|string',
                'note_to_dispatcher' => 'nullable|string',
            ]);

            if ($validateTruck->fails()) {
                return response()->json(['errors' => $validateTruck->errors()], 422);
            }

            DB::beginTransaction();
            $validatedTruck = $validateTruck->validated();
            $truck = Truck::findOrFail($id);

            $previousDriverId = $truck->truckDriver ? $truck->truckDriver->driver_id : null;
            $newDriverId = $request->driver_id;

            $truck->update([
                'branch_id' => $branchId,
                'user_id' => $authUser->id,
                ...$validatedTruck
            ]);

            if ($request->hasFile('file')) {
                $files = $request->file('file');
                $fileTitles = $request->input('file_titles', []);

                foreach ($files as $index => $file) {
                    $filePath = $this->uploadFile($file, 'trucks');
                    if ($filePath) {
                        TruckDoc::create([
                            'truck_id' => $truck->id,
                            'file_path' => $filePath,
                            'file_title' => $fileTitles[$index] ?? null,
                        ]);
                    }
                }
            }

            if ($request->filled('driver_id')) {
                $existingAssignment = TruckDriver::where('truck_id', $truck->id)->first();

                if ($existingAssignment) {
                    $existingAssignment->update([
                        'driver_id' => $request->driver_id,
                        'quick_notes' => $request->quick_notes,
                        'note_to_dispatcher' => $request->note_to_dispatcher
                    ]);
                } else {
                    TruckDriver::create([
                        'truck_id' => $truck->id,
                        'driver_id' => $request->driver_id,
                        'quick_notes' => $request->quick_notes,
                        'note_to_dispatcher' => $request->note_to_dispatcher
                    ]);
                }
            }

            //we only notify driver if the driver has changed
              if ($previousDriverId !== $newDriverId) {
                $newDriver = Driver::find($newDriverId);
                if ($newDriver && $newDriver->user) {
                    $newDriver->user->notify(new DriverAssignedTruckNotification($truck));
                }
            }

            DB::commit();
            return response()->json(['message' => 'Truck updated successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
