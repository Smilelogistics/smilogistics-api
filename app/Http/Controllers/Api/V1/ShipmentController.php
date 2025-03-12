<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\Agency;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\ShipmentNote;
use Illuminate\Http\Request;
use App\Models\ShipmentTrack;
use App\Models\ShipmentCharge;
use App\Mail\AssigneDriverMail;
use App\Models\ShipmentExpense;
use App\Models\ShipmentUploads;
use App\Helpers\FileUploadHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\ConsolidatedShipment;
use App\Notifications\AssigneDriver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\ShipmentConsolidation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Notifications\Notification;

class ShipmentController extends Controller
{
    public function index()
    {
        $shipments = Shipment::with(['shipmentCharges', 'shipmentNotes', 'shipmentExpenses', 'shipmentUploads'])->get();
        return response()->json($shipments);
    }

    public function show($id)
    {
        $shipment = Shipment::with(['shipmentCharges', 'shipmentNotes', 'shipmentExpenses', 'shipmentUploads'])->findOrFail($id);
        return response()->json($shipment);
    }


    
    public function store(Request $request)
    {
        //you should be able to create customer account from hete
        $checkCreateAccount = false;
        $user = auth()->user();
        $branch = Branch::where('user_id', $user->id)->first();
        $branch_prfx = $user->branch ? $user->branch->parcel_tracking_prefix : null;
        $shipment_prefix = $branch_prfx ? $branch_prfx : '';

        // On charges, you multiply Unist * Rate = Amount
        // if there is a discount, you multiply Unist * Rate - discount = Amount
        // for the Total is same Figure as Amount, but discoubt is applied yhu now have Net Total
        
        $validator = Validator::make($request->all(), [
            //'shipment_prefix' => 'nullable|string|max:255',
            //'agency_id' => 'nullable|exists:agencies,id',
            'branch_id' => 'required|exists:branches,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'user_id' => 'nullable|exists:users,id',
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
            'tags' => 'nullable|string|max:255',
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
            'charges.*.units' => 'required|integer|min:1',
            'charges.*.rate' => 'required|numeric|min:0',
            'charges.*.amount' => 'required|numeric|min:0',
            'charges.*.discount' => 'nullable|numeric|min:0|max:100',
            'charges.*.internal_notes' => 'nullable|string|max:500',
            'charges.*.billed' => 'required|boolean',
            'charges.*.invoice_number' => 'required|string|unique:invoices,invoice_number|max:50',
            'charges.*.invoice_date' => 'required|date',
            'charges.*.total' => 'required|numeric|min:0',
            'charges.*.net_total' => 'required|numeric|min:0',
            //notes starts here
            'notes' => 'nullable|array',
            'notes.*.note' => 'nullable|string',
            //expense starts here
            'expenses' => 'nullable|array',
            'expenses.*.expense_type' => 'required|string|max:255',
            'expenses.*.expense_units' => 'required|integer|min:1',
            'expenses.*.expense_rate' => 'required|numeric|min:0',
            'expenses.*.expense_amount' => 'required|numeric|min:0',
            'expenses.*.credit_reimbursement_amount' => 'nullable|numeric|min:0',
            'expenses.*.vendor_invoice_name' => 'required|string|max:255',
            'expenses.*.vendor_invoice_number' => 'required|string|max:100',
            'expenses.*.payment_reference_note' => 'nullable|string|max:255',
            'expenses.*.disputed_note' => 'nullable|string',
            'expenses.*.billed' => 'nullable|boolean',
            'expenses.*.paid' => 'nullable|boolean',
            'expenses.*.expense_disputed' => 'required|boolean',
            'expenses.*.disputed_amount' => 'nullable|numeric|min:0',
            //'disputed_date' => 'nullable|date',
            //file upload starts here
            //'file_path' => 'nullable|file|mimes:jpg,jpeg,png,doc,docx,pdf|max:2048',
            'file_path' => 'nullable|string|max:255',
            'file_name' => 'nullable|string|max:255',
            'file_type' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


    $units = $validator['units'];
    $rates = $validator['rate'];
    $discounts = $validator['discount'];
    
    $total = 0;
    $net_total = 0;
    
    // Process each set of values
    foreach ($units as $key => $unit) {
        // Make sure all arrays have this key
        if (isset($rates[$key]) && isset($discounts[$key])) {
            // Calculate item total
            $itemTotal = $unit * $rates[$key];
            
            // Add to the grand total
            $total += $itemTotal;
            
            // Calculate net total after discount
            $net_total += ($itemTotal - $discounts[$key]);
        }
    }

        $validatedData = $validator->validated(); 
        DB::beginTransaction();

        try {
            $shipment = Shipment::create([
                'branch_id' => $validatedData['branch_id'] ?? null,
            'driver_id' => $validatedData['driver_id'] ?? null,
            'user_id' => $validatedData['user_id'] ?? null,
            'shipment_tracking_number' => $shipment_prefix . $validatedData['shipment_tracking_number'] ?? null,
            'shipment_status' => $validatedData['shipment_status'] ?? null,
            'signature' => $validatedData['signature'] ?? null,
            'office' => $validatedData['office'] ?? null,
            'load_type' => $validatedData['load_type'] ?? null,
            'load_type_note' => $validatedData['load_type_note'] ?? null,
            'brokered' => $validatedData['brokered'] ?? null,
            'shipment_image' => $validatedData['shipment_image'] ?? null,
            'reference_number' => $validatedData['reference_number'] ?? null,
            'bill_of_laden_number' => $validatedData['bill_of_laden_number'] ?? null,
            'booking_number' => $validatedData['booking_number'] ?? null,
            'po_number' => $validatedData['po_number'] ?? null,
            'shipment_weight' => $validatedData['shipment_weight'] ?? null,
            'commodity' => $validatedData['commodity'] ?? null,
            'pieces' => $validatedData['pieces'] ?? null,
            'pickup_number' => $validatedData['pickup_number'] ?? null,
            'overweight_hazmat' => $validatedData['overweight_hazmat'] ?? null,
            'tags' => $validatedData['tags'] ?? null,
            'genset_number' => $validatedData['genset_number'] ?? null,
            'reefer_temp' => $validatedData['reefer_temp'] ?? null,
            'seal_number' => $validatedData['seal_number'] ?? null,
            'total_miles' => $validatedData['total_miles'] ?? null,
            'loaded_miles' => $validatedData['loaded_miles'] ?? null,
            'empty_miles' => $validatedData['empty_miles'] ?? null,
            'dh_miles' => $validatedData['dh_miles'] ?? null,
            'fuel_rate_per_gallon' => $validatedData['fuel_rate_per_gallon'] ?? null,
            'mpg' => $validatedData['mpg'] ?? null,
            'total_fuel_cost' => $validatedData['total_fuel_cost'] ?? null,
            'broker_name' => $validatedData['broker_name'] ?? null,
            'broker_email' => $validatedData['broker_email'] ?? null,
            'broker_phone' => $validatedData['broker_phone'] ?? null,
            'broker_reference_number' => $validatedData['broker_reference_number'] ?? null,
            'broker_batch_number' => $validatedData['broker_batch_number'] ?? null,
            'broker_seq_number' => $validatedData['broker_seq_number'] ?? null,
            'broker_sales_rep' => $validatedData['broker_sales_rep'] ?? null,
            'broker_edi_api_shipment_number' => $validatedData['broker_edi_api_shipment_number'] ?? null,
            'broker_notes' => $validatedData['broker_notes'] ?? null,
            //'comment' => $validatedData['comment'] ?? null,
            ]);
       
           

            if ($request->has('charges')) {
                foreach ($request->charges as $charge) {
                    ShipmentCharge::create([
                        'shipment_id' => $shipment->id,
                        'branch_id' => $validatedData['branch_id'] ?? null,
                        'charge_type' => $charge['charge_type'] ?? null,
                        'comment' => $charge['comment'] ?? null,
                        'units' => $charge['units'] ?? null,
                        'rate' => $charge['rate'] ?? null,
                        'amount' => $charge['amount'] ?? null,
                        'discount' => $charge['discount'] ?? null,
                        'internal_notes' => $charge['internal_notes'] ?? null,
                        'billed' => $charge['billed'] ?? null,
                        'invoice_number' => $charge['invoice_number'] . $branch_prfx ?? null,
                        'invoice_date' => $charge['invoice_date'] ?? null,
                        'total' => $total ?? null,
                        'net_total' => $net_total ?? null,
                    ]);
                }
            }

            if ($request->has('notes')) {
                foreach ($request->notes as $note) {
                    ShipmentNote::create([
                        'shipment_id' => $shipment->id,
                        'branch_id' => $validatedData['branch_id'] ?? null,
                        'note' => $note['note'],
                    ]);
                }
            }


            if ($request->has('expenses')) {
                //dd($request->expenses);
                foreach ($request->expenses as $expense) {
                    ShipmentExpense::create([
                        'shipment_id' => $shipment->id,
                        'branch_id' => $validatedData['branch_id'],
                        'expense_type' => $expense['expense_type'],
                        'units' => $expense['units'],
                        'rate' => $expense['rate'],
                        'amount' => $expense['amount'],
                        'credit_reimbursement_amount' => $expense['credit_reimbursement_amount'],
                        'vendor_invoice_name' => $expense['vendor_invoice_name'],
                        'vendor_invoice_number' => $expense['vendor_invoice_number'],
                        'payment_reference_note' => $expense['payment_reference_note'],
                        'disputed_note' => $expense['disputed_note'],
                        'billed' => $expense['billed'],
                        'paid' => $expense['paid'],
                    ]);
                }
            }

            $uploadedPaths = FileUploadHelper::upload($request->file('files'), 'ShipmentUploads');

            // Check if files are multiple or single
            if (is_array($uploadedPaths)) {
                foreach ($uploadedPaths as $index => $filePath) {
                    ShipmentUploads::create([
                        'shipment_id' => $shipment->id,
                        'file_name' => $request->titles[$index] ?? 'Untitled',
                        'file_path' => $filePath
                    ]);
                }
            } else {
                ShipmentUploads::create([
                    'shipment_id' => $shipment->id,
                    'file_name' => $request->titles[0] ?? 'Untitled',
                    'file_path' => $uploadedPaths
                ]);
            }

            ShipmentTrack::create([
                'shipment_id' => $shipment->id,
                'user_id' => Auth::id(),
                'status' => 'Shipment Created',
                'tracking_number' => $shipment->shipment_tracking_number,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
       
       
        if ($request->email) {
            Mail::to($request->email)->send(new ShipmentCreated($shipment));
        } else {
            // Retrieve customer ID from the customer_name field
            $customer = Customer::where('id', $request->customer_name)->first();
        
            if ($customer && $customer->email) {
                Mail::to($customer->email)->send(new ShipmentCreated($shipment));
            }
        }               
        
        return response()->json($shipment, 201);
    }

    public function update(Request $request, $id)
{
    $shipment = Shipment::findOrFail($id);

    $validator = Validator::make($request->all(), [
        'branch_id' => 'required|exists:branches,id',
        'driver_id' => 'nullable|exists:drivers,id',
        'user_id' => 'nullable|exists:users,id',
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
        // Add validation for related tables
        'shipment_uploads' => 'nullable|array',
        'shipment_uploads.*.id' => 'nullable|exists:shipmentuploads,id',
        'shipment_uploads.*.file_path' => 'required|string',
        
        'shipment_charges' => 'nullable|array',
        'shipment_charges.*.id' => 'nullable|exists:shipmentcharges,id',
        'shipment_charges.*.amount' => 'required|numeric',

        'shipment_expenses' => 'nullable|array',
        'shipment_expenses.*.id' => 'nullable|exists:shipmentexpenses,id',
        'shipment_expenses.*.cost' => 'required|numeric',
        
        'shipment_docs' => 'nullable|array',
        'shipment_docs.*.id' => 'nullable|exists:shipmentdocs,id',
        'shipment_docs.*.document_path' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $validatedData = $validator->validated();

    DB::beginTransaction();
    try {
        // Check if there are changes before updating
        //if ($shipment->isDirty($validatedData)) {
            $shipment->update($validatedData);
        //}

        // Update related tables only if there are changes
        if (isset($validatedData['shipment_uploads'])) {
            foreach ($validatedData['shipment_uploads'] as $upload) {
                if (isset($upload['id'])) {
                    $shipment->shipmentUploads()->where('id', $upload['id'])->update(['file_path' => $upload['file_path']]);
                } else {
                    $shipment->shipmentUploads()->create(['file_path' => $upload['file_path']]);
                }
            }
        }

        if (isset($validatedData['shipment_charges'])) {
            foreach ($validatedData['shipment_charges'] as $charge) {
                if (isset($charge['id'])) {
                    $shipment->shipmentCharges()->where('id', $charge['id'])->update(['amount' => $charge['amount']]);
                } else {
                    $shipment->shipmentCharges()->create(['amount' => $charge['amount']]);
                }
            }
        }

        if (isset($validatedData['shipment_expenses'])) {
            foreach ($validatedData['shipment_expenses'] as $expense) {
                if (isset($expense['id'])) {
                    $shipment->shipmentExpenses()->where('id', $expense['id'])->update(['cost' => $expense['cost']]);
                } else {
                    $shipment->shipmentExpenses()->create(['cost' => $expense['cost']]);
                }
            }
        }

        if (isset($validatedData['shipment_docs'])) {
            foreach ($validatedData['shipment_docs'] as $doc) {
                if (isset($doc['id'])) {
                    $shipment->shipmentUploads()->where('id', $doc['id'])->update(['file_path' => $doc['document_path']]);
                } else {
                    $shipment->shipmentUploads()->create(['file_path' => $doc['document_path']]);
                }
            }
        }

        DB::commit();
        return response()->json(['message' => 'Shipment updated successfully', 'shipment' => $shipment->load(['shipmentUploads', 'shipmentCharges', 'shipmentExpenses', 'shipmentUploads'])], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Failed to update shipment', 'error' => $e->getMessage()], 500);
    }
}




    public function updateShipment(Request $request, $id) 
    {
        $validator = Validator::make($request->all(), [
            'shipment_status' => 'required|string|max:255',
            'shipment_tracking_number' => 'required|string|max:255',
            'driver' => 'nullable|integer|exists:drivers,id'
        ]);

        if ($validator->fails()) {    
            return response()->json($validator->errors(), 422);
        }

        $shipment = Shipment::findOrFail($id);

        $shipment->update([
            'shipment_status' => $request->shipment_status,
            'shipment_tracking_number' => $request->shipment_tracking_number,
            'driver_id' => $request->driver
        ]);

        ShipmentTrack::create([
            'shipment_id' => $shipment->id,
            'status' => $request->shipment_status,
            'tracking_number' => $request->shipment_tracking_number,
            'driver_id' => $request->driver
        ]);

        // Simplified driver notification logic
        if ($request->filled('driver')) {
            $driver = Driver::with('user')->find($request->driver);
            if ($driver && $driver->user && $driver->user->email) {
                $driver->notify(new AssigneDriver($shipment, $driver));
                Mail::to($driver->user->email)->send(new AssigneDriverMail($shipment, $driver));
            }
        }

        return response()->json([
            'message' => 'Shipment updated successfully',
            'shipment' => $shipment->fresh()  // Get fresh data from database
        ]);
    }

    public function trackShipment(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'tracking_number' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {    
            return response()->json($validator->errors(), 422);
        }

        $shipment = ShipmentTrack::with('shipment')->where('tracking_number', $request->tracking_number)->get();

        if (!$shipment) {
            return response()->json(['message' => 'Shipment not found'], 404);
        }

        return response()->json($shipment);
    }



    public function storeAgency(Request $request) {
        //dd($request->all());
        $user = auth()->user();
    
        // Check if the user has a related branch
        $branchId = $user->branch ? $user->branch->id : null;
        
        if (!$branchId) {
            return response()->json(['error' => 'User does not have an associated branch.'], 400);
        }
    
        $validator = Validator::make($request->all(), [
            'agency_name' => 'required|string|max:255',
            'agency_address' => 'required|string|max:255',
            'agency_city' => 'required|string|max:255',
            'agency_state' => 'nullable|string|max:255',
            'agency_zip' => 'nullable|string|max:255',
            'agency_country' => 'nullable|string|max:255',
            'agency_phone' => 'required|string|max:255',
            'agency_email' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        try {

            $agency = Agency::create([
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'agency_name' => $request->agency_name,
                'agency_address' => $request->agency_address,
                'agency_city' => $request->agency_city,
                'agency_state' => $request->agency_state,
                'agency_zip' => $request->agency_zip,
                'agency_country' => $request->agency_country,
                'agency_phone' => $request->agency_phone,
                'agency_email' => $request->agency_email,
            ]);
    
            return response()->json($agency, 201);
    
        } catch (\Exception $e) {
            // Log error if something goes wrong
            \Log::error('Error creating agency: ' . $e->getMessage());
    
            // Return error response
            return response()->json(['error' => 'Registration failed!'], 500);
        }
    }

    public function getAgency(Request $request) {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        if (!$branchId) {
            return response()->json(['error' => 'User does not have an associated branch.'], 400);
        }
        // Get the agency associated with the user's branch
        $agency = Agency::where('branch_id', $branchId)->first();

        return response()->json($agency);
    }

       
}
