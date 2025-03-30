<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\Agency;
use App\Models\BillTo;
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
use App\Traits\FileUploadTrait;
use App\Models\GoodsDescription;
use App\Helpers\FileUploadHelper;
use App\Models\ShipmentContainer;
use Illuminate\Support\Facades\DB;
use App\Mail\ShipmentConsigneeMail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\ConsolidatedShipment;
use App\Notifications\AssigneDriver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\ShipmentNotifyPartyMail;
use App\Models\ShipmentConsolidation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Notifications\Notification;
use App\Http\Requests\StoreShipmentRequest;
use App\Mail\ShipmentAdditionalNotifyPartyMail;

class ShipmentController extends Controller
{
    use FileUploadTrait;
    public function index()
    {
        $shipments = Shipment::with(['branch','customer'])->get();
        return response()->json(['shipments' => $shipments]);
    }

    public function show($id)
    {
        $shipment = Shipment::with(['shipmentCharges', 'shipmentNotes', 'shipmentExpenses', 'shipmentUploads', 'billTo', 'shipmentContainers'])->findOrFail($id);
        return response()->json($shipment);
    }


    
    public function store(StoreShipmentRequest $request)
    {
        //return response()->json($request->all());

        //dd($request->all()); // Debug input dat
        //you should be able to create customer account from hete
        $checkCreateAccount = false;
        $user = auth()->user();
        $branch = Branch::where('user_id', $user->id)->first();
        $branch_prfx = $user->branch ? $user->branch->parcel_tracking_prefix : null;
        $shipment_prefix = $branch_prfx ? $branch_prfx : '';
        $branchId = $user->branch ? $user->branch->id : null;

        // On charges, you multiply Unist * Rate = Amount
        // if there is a discount, you multiply Unist * Rate - discount = Amount
        // for the Total is same Figure as Amount, but discoubt is applied yhu now have Net Total
        
        //$validator = Validator::make($request->all(), []); moved request to validator facade
        

        $validatedData = $request->validated();
        //dd($validatedData);
       
        DB::beginTransaction();

        try {
            $shipment = Shipment::create([
            'branch_id' => $branchId ?? null,
            'driver_id' => $validatedData['driver_id'] ?? null,
            'user_id' => $validatedData['user_id'] ?? null,
            'carrier_id' => $validatedData['carrier_id'] ?? null,
            'truck_id' => $validatedData['truck_id'] ?? null,
            'bike_id' => $validatedData['bike_id'] ?? null,
            'shipment_tracking_number' => $shipment_prefix . Shipment::generateTrackingNumber() ?? null,
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
            //ocean shipment
            'shipment_type' => $validatedData['shipment_type'] ?? null,
            'shipper_name' => $validatedData['shipper_name'] ?? null,
            'ocean_shipper_reference_number' => $validatedData['ocean_shipper_reference_number'] ?? null,
            'carrier_name' => $validatedData['carrier_name'] ?? null,
            'carrier_reference_number' => $validatedData['carrier_reference_number'] ?? null,
            'ocean_bill_of_ladening_number' => $validatedData['ocean_bill_of_ladening_number'] ?? null,
            'consignee' => $validatedData['consignee'] ?? null,
            'consignee_phone' => $validatedData['consignee_phone'] ?? null,
            'consignee_email' => $validatedData['consignee_email'] ?? null,
            'first_notify_party_name' => $validatedData['first_notify_party_name'] ?? null,
            'first_notify_party_phone' => $validatedData['first_notify_party_phone'] ?? null,
            'first_notify_party_email' => $validatedData['first_notify_party_email'] ?? null,
            'second_notify_party_name' => $validatedData['second_notify_party_name'] ?? null,
            'second_notify_party_phone' => $validatedData['second_notify_party_phone'] ?? null,
            'second_notify_party_email' => $validatedData['second_notify_party_email'] ?? null,
            'pre_carrier' => $validatedData['pre_carrier'] ?? null,
            'vessel_aircraft_name' => $validatedData['vessel_aircraft_name'] ?? null,
            'voyage_number' => $validatedData['voyage_number'] ?? null,
            'port_of_discharge' => $validatedData['port_of_discharge'] ?? null,
            'place_of_delivery' => $validatedData['place_of_delivery'] ?? null,
            'final_destination' => $validatedData['final_destination'] ?? null,
            'port_of_landing' => $validatedData['port_of_landing'] ?? null,
            'ocean_note' => $validatedData['ocean_note'] ?? null,
            'ocean_freight_charges' => $validatedData['ocean_freight_charges'] ?? null,
            'ocean_total_containers_in_words' => $validatedData['ocean_total_containers_in_words'] ?? null,
            'no_original_bill_of_landing' => $validatedData['no_original_bill_of_landing'] ?? null,
            'original_bill_of_landing_payable_at' => $validatedData['original_bill_of_landing_payable_at'] ?? null,
            'shipped_on_board_date' => $validatedData['shipped_on_board_date'] ?? null,
            'signature' => $validatedData['signature'] ?? null,
            'delivery_type' => $validatedData['delivery_type'] ?? null,
            //'comment' => $validatedData['comment'] ?? null,
            ]);
            
            if ($request->hasFile('signature')) {
                $file = $request->file('signature');
                $filename = $user->email . '_' . time() . '.' . $file->getClientOriginalExtension();
                $destinationPath = public_path("uploads/{$branch->branch_code}/signatures");
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                $validatedData['signature'] = "uploads/{$branch->branch_code}/signatures/{$filename}";
            }

            
            
            if ($request->has('charges')) {
                foreach ($request->charges as $charge) {
                    ShipmentCharge::create([
                        'shipment_id' => $shipment->id,
                        'branch_id' => $branchId ?? null,
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

            // if ($request->has('notes')) {
            //     foreach ($request->notes as $note) {
            //         ShipmentNote::create([
            //             'shipment_id' => $shipment->id,
            //             'branch_id' => $branchId ?? null,
            //             'note' => $note['note'],
            //         ]);
            //     }
            // }

            if($request->has('containers')) {
                //dd($request->containers);
                foreach($request->containers as $container) {
                    ShipmentContainer::create([
                        'shipment_id' => $shipment->id,
                        'container_number' => $container['container_number'],
                        'container_type' => $container['container_type'],
                        'container_size' => $container['container_size'],
                        'container' => $container['container'],
                        'isLoaded' => $container['isLoaded'],
                        'chasis_size' => $container['chasis_size'],
                        'chasis_type' => $container['chasis_type'],
                        'chasis_vendor' => $container['chasis_vendor'],
                        'chasis' => $container['chasis'],
                    ]);
                }
            }


            if($request->has('billtos')){
                foreach($request->billtos as $billto){
                    BillTo::create([
                        'shipment_id' => $shipment->id,
                        'branch_id' => $billto['branch_id'] ?? null,
                        'bill_to' => $billto['bill_to'],
                        'carrier_id' => $billto['carrier_id'],
                        'driver_id' => $billto['driver_id'],
                        'customer_id' => $billto['customer_id'],
                    ]);
                }
            }

            
            // if($request->has('goods')) {
            //     foreach($request->goods as $good) {
            //         GoodsDescription::create([
            //             'shipment_id' => $shipment->id,
            //             'branch_id' => $validatedData['branch_id'] ?? null,
            //             'goods_name' => $good['goods_name'],
            //             'ocean_vin' => $good['ocean_vin'],
            //             'ocean_weight' => $good['ocean_weight'],
            //         ]);
            //     }
            // }


            if ($request->has('expenses')) {
                $total = 0;
                $net_total = 0;
            
                foreach ($request->expenses as $expense) {
                    // Validate required fields
                    // if (!isset($expense['expense_unit'], $expense['expense_rate'], $expense['disputed_amount'])) {
                    //     dd("Missing keys in expense entry:", $expense);
                    // }
                    
            
                    $unit = $expense['expense_unit'];
                    $rate = $expense['expense_rate'];
                    $discount = $expense['disputed_amount'];
            
                    // Calculate totals
                    $itemTotal = $unit * $rate;
                    $total += $itemTotal;
                    $net_total += ($itemTotal - $discount);
            
                    // Store expense
                    ShipmentExpense::create([
                        'shipment_id' => $shipment->id,
                        'branch_id' => $branchId,
                        'expense_type' => $expense['expense_type'],
                        'units' => $unit,
                        'rate' => $rate,
                        'amount' => $expense['amount'] ?? 0,
                        'credit_reimbursement_amount' => $expense['credit_reimbursement_amount'] ?? 0,
                        'vendor_invoice_name' => $expense['vendor_invoice_name'] ?? '',
                        'vendor_invoice_number' => $expense['vendor_invoice_number'] ?? '',
                        'payment_reference_note' => $expense['payment_reference_note'] ?? '',
                        'disputed_note' => $expense['disputed_note'] ?? '',
                        'billed' => $expense['billed'] ?? false,
                        'paid' => $expense['paid'] ?? false,
                    ]);
                }
            }

            if ($request->hasFile('file_path')) {
                $files = $request->file('file_path');
                //$fileTitles = $request->input('file_titles', []);
                foreach ($files as $index => $file) {
                    try {
                        $filePath = $this->uploadFile($file, 'shipments');
                        if ($filePath) {
                            ShipmentUploads::create([
                                'shipment_id' => $truck->id,
                                'file_path' => $filePath,
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
            

           // $uploadedPaths = FileUploadHelper::upload($request->file('files'), 'ShipmentUploads');

            // Check if files are multiple or single[used first before implementing traits]
            // if (is_array($uploadedPaths)) {
            //     foreach ($uploadedPaths as $index => $filePath) {
            //         ShipmentUploads::create([
            //             'shipment_id' => $shipment->id,
            //             'file_name' => $request->titles[$index] ?? 'Untitled',
            //             'file_path' => $filePath
            //         ]);
            //     }
            // } else {
            //     ShipmentUploads::create([
            //         'shipment_id' => $shipment->id,
            //         'file_name' => $request->titles[0] ?? 'Untitled',
            //         'file_path' => $uploadedPaths
            //     ]);
            // }

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
        // notify party email
        if($request->consignee_email) {
            $consignee_email = $request->consignee_email;
            Mail::to($consignee_email)->send(new ShipmentConsigneeMail($shipment));
        }
        if($request->first_notify_party_email) {
            $first_notify_party_email = $request->first_notify_party_email;
            Mail::to($first_notify_party_email)->send(new ShipmentNotifyPartyMail($shipment));
        } 

        if($request->second_notify_party_email) {
            $second_notify_party_email = $request->second_notify_party_email;
            Mail::to($second_notify_party_email)->send(new ShipmentAdditionalNotifyPartyMail($shipment));
        }            
        //Log::info(request()->headers->all());
        //Log::info('Authorization Header:', [request()->header('Authorization')]);


        return response()->json([
            'success' => true,  
            'message' => 'Shipment created successfully 🚚',
            'shipment' => $shipment
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        $shipment = Shipment::findOrFail($id);
    
        $validator = Validator::make($request->all(), [
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
    
            // Charge Table
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
    
            // Notes
            'notes' => 'nullable|array',
            'notes.*.note' => 'nullable|string',
    
            // Expenses
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
    
            // Container Details
            'containers' => 'nullable|array',
            'containers.*.container' => 'nullable|string|max:255',
            'containers.*.container_size' => 'nullable|string|max:255',
            'containers.*.container_type' => 'nullable|string|max:255',
            'containers.*.container_number' => 'nullable|string|max:255',
    
            // Bill To
            'bill_tos' => 'nullable|array',
            'bill_tos.*.bill_to' => 'nullable|string|max:255',
    
            // Ocean Shipment
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
    
            'file_path' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:150',
    
            'delivery_type' => 'nullable|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $validatedData = $validator->validated();
    
        DB::beginTransaction();
        try {
            $shipment->fill($request->only([
                'driver_id', 'user_id', 'carrier_id', 'truck_id', 'bike_id',
                'shipment_tracking_number', 'shipment_status', 'signature', 
                'office', 'load_type', 'load_type_note', 'brokered', 'shipment_image', 
                'reference_number', 'bill_of_laden_number', 'booking_number', 'po_number',
                'shipment_weight', 'commodity', 'pieces', 'pickup_number', 'overweight_hazmat',
                'tags', 'genset_number', 'reefer_temp', 'seal_number', 'total_miles',
                'loaded_miles', 'empty_miles', 'dh_miles', 'fuel_rate_per_gallon', 'mpg',
                'total_fuel_cost', 'broker_name', 'broker_email', 'broker_phone',
                'broker_reference_number', 'broker_batch_number', 'broker_seq_number',
                'broker_sales_rep', 'broker_edi_api_shipment_number', 'broker_notes',
                'shipment_type', 'shipper_name', 'ocean_shipper_address', 'ocean_shipper_reference_number',
                'carrier_name', 'carrier_reference_number', 'ocean_bill_of_ladening_number', 'consignee',
                'consignee_phone', 'consignee_email', 'first_notify_party_name', 'first_notify_party_phone',
                'first_notify_party_email', 'second_notify_party_name', 'second_notify_party_phone',
                'second_notify_party_email', 'pre_carrier', 'vessel_aircraft_name', 'voyage_number',
                'port_of_discharge', 'place_of_delivery', 'final_destination', 'port_of_landing',
                'ocean_note', 'ocean_freight_charges', 'ocean_total_containers_in_words',
                'no_original_bill_of_landing', 'original_bill_of_landing_payable_at',
                'shipped_on_board_date', 'signature', 'delivery_type'
            ]));
    
            // ✅ Update related tables only if they exist in request
            if (isset($validatedData['shipment_uploads'])) {
                foreach ($validatedData['shipment_uploads'] as $upload) {
                    $shipment->shipmentUploads()->updateOrCreate(
                        ['id' => $upload['id'] ?? null],
                        ['file_path' => $upload['file_path']]
                    );
                }
            }
    
            if (isset($validatedData['shipment_charges'])) {
                foreach ($validatedData['shipment_charges'] as $charge) {
                    $shipment->shipmentCharges()->updateOrCreate(
                        ['id' => $charge['id'] ?? null],
                        ['amount' => $charge['amount']]
                    );
                }
            }
    
            if (isset($validatedData['shipment_expenses'])) {
                foreach ($validatedData['shipment_expenses'] as $expense) {
                    $shipment->shipmentExpenses()->updateOrCreate(
                        ['id' => $expense['id'] ?? null],
                        ['cost' => $expense['cost']]
                    );
                }
            }
    
            if (isset($validatedData['shipment_docs'])) {
                foreach ($validatedData['shipment_docs'] as $doc) {
                    $shipment->shipmentUploads()->updateOrCreate(
                        ['id' => $doc['id'] ?? null],
                        ['document_path' => $doc['document_path']]
                    );
                }
            }
    
            DB::commit();
            return response()->json([
                'message' => 'Shipment updated successfully',
                'shipment' => $shipment->load(['shipmentUploads', 'shipmentCharges', 'shipmentExpenses'])
            ], 200);
    
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
