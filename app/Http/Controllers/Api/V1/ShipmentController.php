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
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ShipmentController extends Controller
{
    use FileUploadTrait;
    public function index()
    {
        $user = auth()->user();
        $branchId = $user->branch ? $user->branch->id : null;
        $shipments = Shipment::with(['branch','customer', 'billTo', 'shipmentContainers', 'shipmentCharges', 'shipmentNotes', 'shipmentExpenses', 'shipmentUploads'])
            ->where('branch_id', $branchId)
            ->latest()
            ->get();
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
        $driverId = $user->driver ? $user->driver->id : null;
        $checkSubscription = false;

       

        // On charges, you multiply Unist * Rate = Amount
        // if there is a discount, you multiply Unist * Rate - discount = Amount
        // for the Total is same Figure as Amount, but discoubt is applied yhu now have Net Total
        
        //$validator = Validator::make($request->all(), []); moved request to validator facade
        

        $validatedData = $request->validated();

        //dd($validatedData);

        $total_miles = $validatedData['total_miles'];
        $fuel_rate_per_gallon = $validatedData['fuel_rate_per_gallon'];
        $mpg = $user->branch->mpg ?? 1;

        $total_fuelL = ($total_miles * 2)*  $fuel_rate_per_gallon / $mpg;

        //dd($validatedData);
        if (isset($validatedData['tags'])) {
            if (is_string($validatedData['tags'])) {
                $tagsArray = explode(',', $validatedData['tags']);
            } 
            elseif (is_string($validatedData['tags']) && json_decode($validatedData['tags'])) {
                $tagsArray = json_decode($validatedData['tags'], true);
            }
            else {
                $tagsArray = $validatedData['tags'];
            }
            
            $tagsArray = array_values(array_filter(array_map('trim', $tagsArray)));
            $validatedData['tags'] = !empty($tagsArray) ? $tagsArray : null;
        }

        $arrayFields = [
            'overweight_hazmat' => $request->input('overweight_hazmat', []),
        ];

        // Convert single values to arrays if needed
        foreach ($arrayFields as $field => $value) {
            if (!is_array($value)) {
                $arrayFields[$field] = [$value];
            }
        }

       
        DB::beginTransaction();

        try {
            $shipment = Shipment::create([
            'branch_id' => $branchId ?? null,
            'driver_id' => $validateData['driver_id'] ?? null,
            'user_id' => $user->id ?? null,
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
            
            'overweight_hazmat' => !empty($validatedData['overweight_hazmat']) 
                ? json_encode(array_filter($validatedData['overweight_hazmat'])) 
                : null,
            'tags' => $validatedData['tags'] ?? null,
            'genset_number' => $validatedData['genset_number'] ?? null,
            'reefer_temp' => $validatedData['reefer_temp'] ?? null,
            'seal_number' => $validatedData['seal_number'] ?? null,
            'total_miles' => $validatedData['total_miles'] ?? 0.00,
            'loaded_miles' => $validatedData['loaded_miles'] ?? 0.00,
            'empty_miles' => $validatedData['empty_miles'] ?? 0.00,
            'dh_miles' => $validatedData['dh_miles'] ?? 0.00,
            'fuel_rate_per_gallon' => $validatedData['fuel_rate_per_gallon'] ?? 0.00,
            'mpg' => $validatedData['mpg'] ?? 0.00,
            'total_fuel_cost' => $total_fuelL,
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
            'signature' => null,
            'delivery_type' => $validatedData['delivery_type'] ?? null,
            //'comment' => $validatedData['comment'] ?? null,
            ]);
            
            if ($request->hasFile('signature')) {
                $uploadedFile = Cloudinary::upload($request->file('signature')->getRealPath(), [
                'folder' => 'Smile_logistics/signatures',
                ]);
              
                $shipment->update([
                    'signature' => $uploadedFile->getSecurePath()
                ]);
                
            }
            
            if (!empty($validatedData['charge_type']) && is_array($validatedData['charge_type'])) {
                $total = 0;
                $totalDiscount = 0;

                // Process each charge
                foreach ($validatedData['charge_type'] as $i => $chargeType) {
                    $amount = (float)($validatedData['amount'][$i] ?? 0);
                    $discount = (float)($validatedData['discount'][$i] ?? 0);
                    
                    // Calculate totals
                    $total += $amount;
                    $totalDiscount += $discount;
            
                    // Create charge record
                    ShipmentCharge::create([
                        'shipment_id' => $shipment->id,
                        'branch_id' => $branchId ?? null,
                        'charge_type' => $chargeType,
                        'comment' => $validatedData['comment'][$i] ?? null,
                        'units' => $validatedData['units'][$i] ?? null,
                        'rate' => $validatedData['rate'][$i] ?? null,
                        'amount' => $amount,
                        'discount' => $discount,
                        'internal_notes' => $validatedData['internal_notes'][$i] ?? null,
                        'total' => $total,
                        'total_discount' => $totalDiscount,
                        'net_total' => $total - $totalDiscount
                    ]);
                }
            
                // Update shipment with calculated totals
                // $shipment->update([
                //     'total' => $total,
                //     'total_discount' => $totalDiscount,
                //     'net_total' => $total - $totalDiscount
                // ]);
            }

            
            // if (isset($validatedData['charges'])) {
            //     //dd($request->charges);
            //     foreach ($validateData['charges'] as $charge) {
            //         ShipmentCharge::create([
            //             'shipment_id' => $shipment->id,
            //             'branch_id' => $branchId ?? null,
            //             'charge_type' => $charge['charge_type'] ?? null,
            //             'comment' => $charge['comment'] ?? null,
            //             'units' => $charge['units'] ?? null,
            //             'rate' => $charge['rate'] ?? null,
            //             'amount' => $charge['amount'] ?? null,
            //             'discount' => $charge['discount'] ?? null,
            //             'internal_notes' => $charge['internal_notes'] ?? null,
            //             'billed' => $charge['billed'] ?? null,
            //             'invoice_number' => $charge['invoice_number'] . $branch_prfx ?? null,
            //             'invoice_date' => $charge['invoice_date'] ?? null,
            //             'total' => $total ?? 0,
            //             'net_total' => $net_total ?? 0,
            //         ]);
            //     }
            // }

            // if ($request->has('notes')) {
            //     foreach ($request->notes as $note) {
            //         ShipmentNote::create([
            //             'shipment_id' => $shipment->id,
            //             'branch_id' => $branchId ?? null,
            //             'note' => $note['note'],
            //         ]);
            //     }
            // }

            if (!empty($validatedData['container_type']) && is_array($validatedData['container_type'])) {
                //dd($request->containers);
                $containers = [];
                for ($i = 0; $i < count($validatedData['container_type']); $i++) {
                    $containers[] = [
                        'container_type' => $validatedData['container_type'][$i],
                        'container_number' => $validatedData['container_number'][$i] ?? 0,
                        'container_size' => $validatedData['container_size'][$i] ?? 0,
                        'container' => $validatedData['container'][$i] ?? null,  // Changed from 'rate'
                        'isLoaded' => $validatedData['isLoaded'][$i] ?? null,
                        'chasis_size' => $validatedData['chasis_size'][$i] ?? null,
                        'chasis_type' => $validatedData['chasis_type'][$i] ?? null,
                        'chasis_vendor' => $validatedData['chasis_vendor'][$i] ?? null,
                        'chasis' => isset($validatedData['expense_disputechasisd'][$i]) ? 1 : 0,
                    ];
                }

                foreach($containers as $container) {
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


            
            if (!empty($validatedData['bill_to']) && is_array($validatedData['bill_to'])) {
                $biltos = [];
                for ($i = 0; $i < count($validatedData['bill_to']); $i++) {
                    $biltos[] = [
                        'bill_to' => $validatedData['bill_to'][$i],
                        'carrier_id' => $validatedData['carrier_id'][$i] ?? null,
                        'driver_id' => $validatedData['driver_id'][$i] ?? null,
                        'customer_id' => $validatedData['customer_id'][$i] ?? null,
                    ];
                }

                foreach($biltos as $billto){
                    BillTo::create([
                        'shipment_id' => $shipment->id,
                        'branch_id' => $branchId ?? null,
                        'bill_to' => $billto['bill_to'],
                        'carrier_id' => $billto['carrier_id'],
                        'driver_id' => $billto['driver_id'],
                        'customer_id' => $billto['customer_id'],
                    ]);
                }
            }

            if (!empty($validatedData['expense_type']) && is_array($validatedData['expense_type'])) {
                $credit_total = 0;
                $expense_total = 0;
                
                $expenses = [];
                for ($i = 0; $i < count($validatedData['expense_type']); $i++) {
                    $expenses[] = [
                        'expense_type' => $validatedData['expense_type'][$i],
                        'credit_reimbursement_amount' => $validatedData['credit_reimbursement_amount'][$i] ?? 0,
                        'units' => $validatedData['expense_unit'][$i] ?? 0,
                        'rate' => $validatedData['expense_rate'][$i] ?? 0,
                        'amount' => $validatedData['expense_amount'][$i] ?? 0,
                        'vendor_invoice_number' => $validatedData['vendor_invoice_number'][$i] ?? null,
                        'payment_reference_note' => $validatedData['payment_reference_note'][$i] ?? null,
                        'disputed_note' => $validatedData['disputed_note'][$i] ?? null,
                        'expense_disputed' => !empty($validatedData['expense_disputed'][$i]) ? true : false,
                        'paid' => !empty($validatedData['paid'][$i]) ? true : false
                    ];
                }
            
                foreach ($expenses as $expense) {
                    $expense_total += (float)$expense['amount'];
                    $credit_total += (float)$expense['credit_reimbursement_amount'];
                }
                $net_total = $expense_total - $credit_total;
            
                // Then create all records with the same totals
                foreach ($expenses as $expense) {
                    ShipmentExpense::create([
                        'shipment_id' => $shipment->id,
                        'branch_id' => $branchId ?? null,
                        'expense_type' => $expense['expense_type'],
                        'credit_reimbursement_amount' => (float)$expense['credit_reimbursement_amount'],
                        'units' => (float)$expense['units'],
                        'rate' => (float)$expense['rate'],
                        'amount' => (float)$expense['amount'],
                        'vendor_invoice_number' => $expense['vendor_invoice_number'],
                        'payment_reference_note' => $expense['payment_reference_note'],
                        'disputed_note' => $expense['disputed_note'],
                        'expense_disputed' => $expense['expense_disputed'],
                        'paid' => $expense['paid'],
                        'credit_total' => $credit_total,
                        'expense_total' => $expense_total,
                        'net_expense' => $net_total,
                    ]);
                }
            
                // Update the shipment with these totals
                // $shipment->update([
                //     'expense_total' => $expense_total,
                //     'credit_total' => $credit_total,
                //     'net_expense' => $net_total
                // ]);
            }


            // if (!empty($validatedData['expense_type']) && is_array($validatedData['expense_type'])) {
            //     $credit_total = 0;
            //     $expense_total = 0;
                
            //     $expenses = [];
            //     for ($i = 0; $i < count($validatedData['expense_type']); $i++) {
            //         $expenses[] = [
            //             'expense_type' => $validatedData['expense_type'][$i],
            //             'credit_reimbursement_amount' => $validatedData['credit_reimbursement_amount'][$i] ?? 0,
            //             'units' => $validatedData['expense_unit'][$i] ?? 0, // Changed from 'units'
            //             'rate' => $validatedData['expense_rate'][$i] ?? 0,  // Changed from 'rate'
            //             'amount' => $validatedData['expense_amount'][$i] ?? 0,
            //             'vendor_invoice_number' => $validatedData['vendor_invoice_number'][$i] ?? null,
            //             'payment_reference_note' => $validatedData['payment_reference_note'][$i] ?? null,
            //             'disputed_note' => $validatedData['disputed_note'][$i] ?? null,
            //             'expense_disputed' => isset($validatedData['expense_disputed'][$i]) ? 1 : 0,
            //             'paid' => isset($validatedData['paid'][$i]) ? 1 : 0
            //         ];
            //     }
            
            //         foreach ($expenses as $expense) {
            //             $units = (float)$expense['units'];
            //             $rate = (float)$expense['rate'];
            //             $amount = (float)$expense['amount'];
            //             $credit = (float)$expense['credit_reimbursement_amount'];
            
            //             // Calculate totals
            //             $expense_total += $amount;
            //             $credit_total += $credit;
            //             $net_total = $expense_total - $credit_total;
            
            //             ShipmentExpense::create([
            //                 'shipment_id' => $shipment->id,
            //                 'branch_id' => $branchId ?? null,
            //                 'expense_type' => $expense['expense_type'],
            //                 'credit_reimbursement_amount' => $credit,
            //                 'units' => $units,
            //                 'rate' => $rate,
            //                 'amount' => $amount,
            //                 'vendor_invoice_number' => $expense['vendor_invoice_number'],
            //                 'payment_reference_note' => $expense['payment_reference_note'],
            //                 'disputed_note' => $expense['disputed_note'],
            //                 'expense_disputed' => $expense['expense_disputed'],
            //                 'paid' => $expense['paid'],
            //                 'credit_total' => $credit_total,
            //                 'expense_total' => $expense_total,
            //                 'net_expense' => $net_total,
            //             ]);
            //         }
                    
                
            // }
            if ($request->hasFile('file_path')) {
                // Get the files - always convert to array for consistent handling
                $files = $request->file('file_path');
                $files = is_array($files) ? $files : [$files]; // Ensure we always have an array
                
                // $uploadedFiles = [];
                // $successCount = 0;
                // $errorCount = 0;
            
                foreach ($files as $index => $file) {
                    try {
                        if (!$file->isValid()) {
                            throw new \Exception("Invalid file: " . $file->getClientOriginalName());
                        }
            
                        // Upload to Cloudinary
                        $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                            'folder' => 'Smile_logistics/shipment',
                            'timeout' => 30
                        ]);
            
                        if (!$uploadedFile->getSecurePath()) {
                            throw new \Exception("Cloudinary upload failed for: " . $file->getClientOriginalName());
                        }
            
                        // Create shipment upload record
                        $upload = $shipment->shipmentUploads()->create([
                            'file_path' => $uploadedFile->getSecurePath(),
                            'public_id' => $uploadedFile->getPublicId(),
                            'original_name' => $file->getClientOriginalName()
                        ]);
            
                        // $successCount++;
                        // $uploadedFiles[] = $upload;
            
                    } catch (\Exception $e) {
                        $errorCount++;
                        \Log::error("File upload error: " . $e->getMessage());
                    }
                }
           ;
            
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
            //Mail::to($request->email)->send(new ShipmentCreated($shipment));
        } else {
            // Retrieve customer ID from the customer_name field
            $customer = Customer::where('id', $request->customer_name)->first();
        
            if ($customer && $customer->email) {
              //  Mail::to($customer->email)->send(new ShipmentCreated($shipment));
            }
        }
        // notify party email
        if($request->consignee_email) {
            $consignee_email = $request->consignee_email;
          //  Mail::to($consignee_email)->send(new ShipmentConsigneeMail($shipment));
        }
        if($request->first_notify_party_email) {
            $first_notify_party_email = $request->first_notify_party_email;
            //Mail::to($first_notify_party_email)->send(new ShipmentNotifyPartyMail($shipment));
        } 

        if($request->second_notify_party_email) {
            $second_notify_party_email = $request->second_notify_party_email;
            //Mail::to($second_notify_party_email)->send(new ShipmentAdditionalNotifyPartyMail($shipment));
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
            //'branch_id' => 'required|exists:branches,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'user_id' => 'nullable|exists:users,id',
            'carrier_id' => 'nullable|exists:carriers,id',
            'truck_id' => 'nullable|exists:trucks,id',
            'bike_id' => 'nullable|exists:bikes,id',
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
            'overweight_hazmat' => 'nullable',
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

           'goods_name.*' => 'nullable|string|max:255',
            'ocean_vin.*' => 'nullable|string|max:255',
            'ocean_weight.*' => 'nullable|string|max:255',

              //container details
              'container.*' => 'nullable|string|max:255',
              'container_size.*' => 'nullable|string|max:255',
              'container_type.*' => 'nullable|string|max:255',
              'container_number.*' => 'nullable|string|max:255',
              'chasis.*' => 'nullable|string|max:255',
              'chasis_size.*' => 'nullable|string|max:255',
              'chasis_type.*' => 'nullable|string|max:255',
              'chasis_vendor.*' => 'nullable|string|max:255',
              'isLoaded.*' => 'nullable|string|max:255',


            // Add validation for related tables
            'shipment_uploads' => 'nullable|array',
            'shipment_uploads.*.id' => 'nullable|exists:shipmentuploads,id',
            'shipment_uploads.*.file_path' => 'nullable|string',
            
           'charge_type.*' => 'nullable|string',
            'comment.*' => 'nullable|string',
            'units.*' => 'nullable|numeric',
            'rate.*' => 'nullable|numeric',
            'amount.*' => 'nullable|numeric',
            'discount.*' => 'nullable|numeric',
            'internal_notes.*' => 'nullable|string',
            //notes starts here
    
              
            'expense_type.*' => 'nullable|string|max:255',
            'expense_unit.*' => 'nullable|integer|min:1',
            'expense_rate.*' => 'nullable|numeric|min:0',
            'expense_amount.*' => 'nullable|numeric|min:0',
            'credit_reimbursement_amount.*' => 'nullable|numeric|min:0',
            'vendor_invoice_name.*' => 'nullable|string|max:255',
            'vendor_invoice_number.*' => 'nullable|string|max:100',
            'payment_reference_note.*' => 'nullable|string|max:255',
            'disputed_note.*' => 'nullable|string',
            'billed.*' => 'nullable|boolean',
            'paid.*' => 'nullable|boolean',
            'expense_disputed.*' => 'nullable|boolean',
            'disputed_amount.*' => 'nullable|numeric|min:0',
            
            'shipment_docs' => 'nullable|array',
            'shipment_docs.*.id' => 'nullable|exists:shipmentdocs,id',
            'shipment_docs.*.document_path' => 'reqnullableuired|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $validatedData = $validator->validated();
        $arrayFields = [
            'overweight_hazmat' => $request->input('overweight_hazmat', []),
        ];

        // Convert single values to arrays if needed
        foreach ($arrayFields as $field => $value) {
            if (!is_array($value)) {
                $arrayFields[$field] = [$value];
            }
        }

        //dd($validatedData);
        if (isset($validatedData['tags'])) {
            if (is_string($validatedData['tags'])) {
                $tagsArray = explode(',', $validatedData['tags']);
            } 
            elseif (is_string($validatedData['tags']) && json_decode($validatedData['tags'])) {
                $tagsArray = json_decode($validatedData['tags'], true);
            }
            else {
                $tagsArray = $validatedData['tags'];
            }
            
            $tagsArray = array_values(array_filter(array_map('trim', $tagsArray)));
            $validatedData['tags'] = !empty($tagsArray) ? $tagsArray : null;
        }
    
        DB::beginTransaction();
        try {
            // Check if there are changes before updating
            //if ($shipment->isDirty($validatedData)) {
                $shipment->update([
                    'branch_id' => $branchId,
                    'driver_id' => $validatedData['driver_id'],
                    'carrier_id' => $validatedData['carrier_id'],
            // 'truck_id' => $validatedData['truck_id'],
            // 'bike_id' => $validatedData['bike_id'],
            //'shipment_tracking_number' => $shipment_prefix . Shipment::generateTrackingNumber() ?? null,
            'shipment_status' => $validatedData['shipment_status'],
            'signature' => $validatedData['signature'],
            'office' => $validatedData['office'],
            'load_type' => $validatedData['load_type'],
            'load_type_note' => $validatedData['load_type_note'],
            'brokered' => $validatedData['brokered'],
            'shipment_image' => $validatedData['shipment_image'],
            'reference_number' => $validatedData['reference_number'],
            'bill_of_laden_number' => $validatedData['bill_of_laden_number'],
            'booking_number' => $validatedData['booking_number'],
            'po_number' => $validatedData['po_number'],
            'shipment_weight' => $validatedData['shipment_weight'],
            'commodity' => $validatedData['commodity'],
            'pieces' => $validatedData['pieces'],
            'pickup_number' => $validatedData['pickup_number'],
            'overweight_hazmat' => $validatedData['overweight_hazmat'],
            
            'overweight_hazmat' => !empty($validatedData['overweight_hazmat']) 
                ? json_encode(array_filter($validatedData['overweight_hazmat'])) 
                : null,
            'tags' => $validatedData['tags'],
            'genset_number' => $validatedData['genset_number'],
            'reefer_temp' => $validatedData['reefer_temp'],
            'seal_number' => $validatedData['seal_number'],
            'total_miles' => $validatedData['total_miles'],
            'loaded_miles' => $validatedData['loaded_miles'],
            'empty_miles' => $validatedData['empty_miles'],
            'dh_miles' => $validatedData['dh_miles'],
            'fuel_rate_per_gallon' => $validatedData['fuel_rate_per_gallon'],
            'mpg' => $validatedData['mpg'],
            'total_fuel_cost' => $total_fuelL,
            'broker_name' => $validatedData['broker_name'],
            'broker_email' => $validatedData['broker_email'],
            'broker_phone' => $validatedData['broker_phone'],
            'broker_reference_number' => $validatedData['broker_reference_number'],
            'broker_batch_number' => $validatedData['broker_batch_number'],
            'broker_seq_number' => $validatedData['broker_seq_number'],
            'broker_sales_rep' => $validatedData['broker_sales_rep'],
            'broker_edi_api_shipment_number' => $validatedData['broker_edi_api_shipment_number'],
            'broker_notes' => $validatedData['broker_notes'],
            //ocean shipment
            'shipment_type' => $validatedData['shipment_type'],
            'shipper_name' => $validatedData['shipper_name'],
            'ocean_shipper_reference_number' => $validatedData['ocean_shipper_reference_number'],
            'carrier_name' => $validatedData['carrier_name'],
            'carrier_reference_number' => $validatedData['carrier_reference_number'],
            'ocean_bill_of_ladening_number' => $validatedData['ocean_bill_of_ladening_number'],
            'consignee' => $validatedData['consignee'],
            'consignee_phone' => $validatedData['consignee_phone'],
            'consignee_email' => $validatedData['consignee_email'],
            'first_notify_party_name' => $validatedData['first_notify_party_name'],
            'first_notify_party_phone' => $validatedData['first_notify_party_phone'],
            'first_notify_party_email' => $validatedData['first_notify_party_email'],
            'second_notify_party_name' => $validatedData['second_notify_party_name'],
            'second_notify_party_phone' => $validatedData['second_notify_party_phone'],
            'second_notify_party_email' => $validatedData['second_notify_party_email'],
            'pre_carrier' => $validatedData['pre_carrier'],
            'vessel_aircraft_name' => $validatedData['vessel_aircraft_name'],
            'voyage_number' => $validatedData['voyage_number'],
            'port_of_discharge' => $validatedData['port_of_discharge'],
            'place_of_delivery' => $validatedData['place_of_delivery'],
            'final_destination' => $validatedData['final_destination'],
            'port_of_landing' => $validatedData['port_of_landing'],
            'ocean_note' => $validatedData['ocean_note'],
            'ocean_freight_charges' => $validatedData['ocean_freight_charges'],
            'ocean_total_containers_in_words' => $validatedData['ocean_total_containers_in_words'],
            'no_original_bill_of_landing' => $validatedData['no_original_bill_of_landing'],
            'original_bill_of_landing_payable_at' => $validatedData['original_bill_of_landing_payable_at'],
            'shipped_on_board_date' => $validatedData['shipped_on_board_date'],
            'signature' => null,
            'delivery_type' => $validatedData['delivery_type']
                    // 'tags' => $validatedData['tags'],
                    // 'overweight_hazmat' => !empty($validatedData['overweight_hazmat']) 
                    //     ? json_encode(array_filter($validatedData['overweight_hazmat'])) 
                    //     : null,
                    // ...$validatedData
                ]);
    

            if (isset($validatedData['shipment_uploads'])) {
                foreach ($validatedData['shipment_uploads'] as $upload) {
                    if (isset($upload['file'])) {
                        // Upload to Cloudinary
                        $uploadedFileUrl = Cloudinary::upload($upload['file']->getRealPath())->getSecurePath();
            
                        if (isset($upload['id'])) {
                            $shipment->shipmentUploads()
                                ->where('id', $upload['id'])
                                ->update(['file_path' => $uploadedFileUrl]);
                        } else {
                            $shipment->shipmentUploads()->create(['file_path' => $uploadedFileUrl]);
                        }
                    }
                }
            }

            // if (isset($validatedData['shipment_charges'])) {
            //     $shipment->load('shipmentCharges'); // Ensure the relation is loaded
                
            //     // Track processed IDs to identify charges that need to be deleted
            //     $processedIds = [];
                
            //     foreach ($validatedData['shipment_charges'] as $charge) {
            //         $chargeData = [
            //             'amount' => $charge['amount'],
            //             'units' => $charge['units'] ?? null,
            //             'rate' => $charge['rate'] ?? null,
            //             'comment' => $charge['comment'] ?? null,
            //             'total' => $charge['total'] ?? null,
            //             'net_total' => $charge['net_total'] ?? null, 
            //             'discount' => $charge['discount'] ?? null,
            //             'total_discount' => $charge['total_discount'] ?? null,
            //         ];
                    
            //         if (isset($charge['id']) && $shipment->shipmentCharges()->where('id', $charge['id'])->exists()) {
            //             // Update existing charge
            //             $shipment->shipmentCharges()->where('id', $charge['id'])->update($chargeData);
            //             $processedIds[] = $charge['id'];
            //         } else {
            //             // Create new charge
            //             $newCharge = $shipment->shipmentCharges()->create($chargeData);
            //             $processedIds[] = $newCharge->id;
            //         }
            //     }
                
               
            // }
    
            // if (isset($validatedData['shipment_charges'])) {
            //     $shipment->load('shipmentCharges'); // Ensure the relation is loaded
            
            //     foreach ($validatedData['shipment_charges'] as $charge) {
            //         if (isset($charge['id']) && $shipment->shipmentCharges()->where('id', $charge['id'])->exists()) {
            //             $shipment->shipmentCharges()->where('id', $charge['id'])->update([
            //                 'amount' => $charge['amount'],
            //                 'units' => $charge['units'],
            //                 'rate'  => $charge['rate'],
            //                 'comment' => $charge['comment'],
            //                 'total' => $charge[['total']],
            //                 'net_total' => $charge['net_total'],
            //                 'discount' => $charge['discount'],
            //                 'total_discount' => $charge['total_discount'],
            //             ]);
            //         } else {
            //             $shipment->shipmentCharges()->create(['amount' => $charge['amount']]);
            //         }
            //     }
            // }

            if (!empty($validatedData['charge_type']) && is_array($validatedData['charge_type'])) {
                $total = 0;
                $totalDiscount = 0;
                
                ShipmentCharge::where('shipment_id', $shipment->id)->delete();

                foreach ($validatedData['charge_type'] as $i => $chargeType) {
                    $amount = (float)($validatedData['amount'][$i] ?? 0);
                    $discount = (float)($validatedData['discount'][$i] ?? 0);
                    
                    $total += $amount;
                    $totalDiscount += $discount;

                    ShipmentCharge::create([
                        'shipment_id' => $shipment->id,
                        'branch_id' => $branchId ?? null,
                        'charge_type' => $chargeType,
                        'comment' => $validatedData['comment'][$i] ?? null,
                        'units' => $validatedData['units'][$i] ?? null,
                        'rate' => $validatedData['rate'][$i] ?? null,
                        'amount' => $amount,
                        'discount' => $discount,
                        'internal_notes' => $validatedData['internal_notes'][$i] ?? null,
                        'total' => $total,
                        'total_discount' => $totalDiscount,
                        'net_total' => $total - $totalDiscount
                    ]);
                }

                // Update shipment with calculated totals
                // $shipment->update([
                //     'total' => $total,
                //     'total_discount' => $totalDiscount,
                //     'net_total' => $total - $totalDiscount
                // ]);
            }

            
            
    
            if (!empty($validatedData['expense_type']) && is_array($validatedData['expense_type'])) {
                $credit_total = 0;
                $expense_total = 0;
                
                ShipmentExpense::where('shipment_id', $shipment->id)->delete();
            
                // Process each expense
                foreach ($validatedData['expense_type'] as $i => $expenseType) {
                    $expense = [
                        'expense_type' => $expenseType,
                        'credit_reimbursement_amount' => $validatedData['credit_reimbursement_amount'][$i] ?? 0,
                        'units' => $validatedData['expense_unit'][$i] ?? 0,
                        'rate' => $validatedData['expense_rate'][$i] ?? 0,
                        'amount' => $validatedData['expense_amount'][$i] ?? 0,
                        'vendor_invoice_number' => $validatedData['vendor_invoice_number'][$i] ?? null,
                        'payment_reference_note' => $validatedData['payment_reference_note'][$i] ?? null,
                        'disputed_note' => $validatedData['disputed_note'][$i] ?? null,
                        'expense_disputed' => !empty($validatedData['expense_disputed'][$i]),
                        'paid' => !empty($validatedData['paid'][$i]),
                    ];
            
                    // Calculate totals
                    $expense_total += (float)$expense['amount'];
                    $credit_total += (float)$expense['credit_reimbursement_amount'];
            
                    // Create new expense record
                    ShipmentExpense::create([
                        'shipment_id' => $shipment->id,
                        'branch_id' => $branchId ?? null,
                        'expense_type' => $expense['expense_type'],
                        'credit_reimbursement_amount' => $expense['credit_reimbursement_amount'],
                        'units' => $expense['units'],
                        'rate' => $expense['rate'],
                        'amount' => $expense['amount'],
                        'vendor_invoice_number' => $expense['vendor_invoice_number'],
                        'payment_reference_note' => $expense['payment_reference_note'],
                        'disputed_note' => $expense['disputed_note'],
                        'expense_disputed' => $expense['expense_disputed'],
                        'paid' => $expense['paid'],
                    ]);
                }
            
                // If you need to store totals on the shipment (only if columns exist)
                // if (Schema::hasColumn('shipments', 'expense_total')) {
                //     $shipment->update([
                //         'expense_total' => $expense_total,
                //         'credit_total' => $credit_total,
                //         'net_expense' => $expense_total - $credit_total
                //     ]);
                // }
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

    public function destroy($id) {
        $shipment = Shipment::findOrFail($id);
        $shipment->delete();
        return response()->json(['message' => 'Shipment deleted successfully'], 200);
    }

       
}
