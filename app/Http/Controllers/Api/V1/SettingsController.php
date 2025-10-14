<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use App\Models\Driver;
use App\Models\Customer;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use App\Models\OfficeLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class SettingsController extends Controller
{

    public function index() {
        
        $user = auth()->user();
        $branchId = auth()->user()->getBranchId();
        if ($user->hasRole('customer')) {
            $data = Customer::with('branch', 'user')->where('user_id', $user->id)
            ->where('branch_id', $branchId)->get();
            return response()->json($data);
        }
        elseif ($user->hasRole('driver')) {
            $data = Driver::with('branch', 'user')->where('user_id', $user->id)
            ->where('branch_id', $branchId)->get();
            return response()->json($data);
        }
        elseif ($user->hasRole('businessadministrator')) {
            $data = Branch::with('user','offices')->where('user_id', $user->id)->get();
            return response()->json($data);
        }
        elseif($user->hasRole('superadministrator')) {
            $data = SuperAdmin::with('user')->where('user_id', $user->id)->get();
            return response()->json($data);
        }
        
    }

    public function getRates()
    {
        try {
            $user = auth()->user();
            
            if (!$user || !$user->branch) {
                return response()->json([
                    'error' => 'User branch not found'
                ], 404);
            }
            
            $data = DB::table('branches')
                ->where('id', $user->branch->id)
                ->select('base_rate', 'base_fee')
                ->first();
                
            if (!$data) {
                return response()->json([
                    'error' => 'Branch rates not found'
                ], 404);
            }
            
            return response()->json([
                'base_rate' => (float)$data->base_rate,
                'base_fee' => (float)$data->base_fee
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch rates'
            ], 500);
        }
    }

public function updateGeneral(Request $request)
{
    //dd($request->all());
    try {
        $user = auth()->user();

        // Validate input
        $validated = $request->validate([
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|min:10|max:255',
            'parcel_prefix' => 'nullable|string|max:10',
            'invoice_prefix' => 'nullable|string|max:10',
            'currency' => 'nullable|string|size:3',
            'copyright' => 'nullable|string|min:5|max:100',
            'logo1' => 'sometimes|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo2' => 'sometimes|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo3' => 'sometimes|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            // 'favicon' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'mpg' => 'sometimes|integer',
            'base_rate' => 'nullable|numeric',
            'base_fee' => 'nullable|numeric',
            'handling_fee' => 'nullable|numeric',
            'price_per_mile' => 'nullable|numeric',
            'price_per_gallon' => 'nullable|numeric',
            'max_height' => 'nullable|numeric',
            'max_length' => 'nullable|numeric',
            'invoice_logo' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
        ]);

        // Upload logos to Cloudinary
        $logoPaths = [];
        foreach (['logo1', 'logo2', 'logo3', 'invoice_logo'] as $logoField) {
            if ($request->hasFile($logoField)) {
                $file = $request->file($logoField);
                if ($file->isValid()) {
                     $filename = time() . '_' . $file->getClientOriginalName();
                        $path = $file->storeAs(
                            'branch',    // folder inside Wasabi bucket
                            $filename,  // unique filename
                            'wasabi'    // disk name from config/filesystems.php
                        );

                        $url = Storage::disk('wasabi')->url($path);

                    // $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                    //     'folder' => 'Smile_logistics/Branch_Logos',
                    // ]);
                    $logoPaths[$logoField] = $url;
                }
            }
        }

        // Merge and filter update data
        $updateData = array_merge($validated, $logoPaths);
        $updateData = array_filter($updateData, fn ($v) => $v !== null);

        // Handle update based on user role
        //dd($user);
        if ($user->hasRole('businessadministrator')) {
            if (!$user->branch) {
                return response()->json([
                    'message' => 'Branch not found',
                    'hint' => 'Contact administrator to assign you to a branch'
                ], 404);
            }

            $user->branch->update($updateData);
        } elseif ($user->hasRole('superadministrator')) {
            //dd($updateData);
            $user->superadmin->update($updateData);
        } else {
            return response()->json([
                'message' => 'Unauthorized action',
                'hint' => 'Your role cannot update these settings'
            ], 403);
        }

        return response()->json([
            'message' => 'General Settings updated successfully',
            'data' => $updateData,
            'logo_urls' => $logoPaths
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        Log::error("Settings update failed: " . $e->getMessage());
        return response()->json([
            'message' => 'Update failed',
            'error' => $e->getMessage(),
            'hint' => 'Please try again or contact support'
        ], 500);
    }
} 


    /**
     * Update payment settings for the authenticated user.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePayment(Request $request)
    {
        $user = auth()->user();
        // Validate incoming request
        $validated = $request->validate([
            'paystack_publicKey' => 'sometimes|nullable|string',
            'paystack_secretKey' => 'sometimes|nullable|string|min:10',
            'FLW_pubKey' => 'sometimes|nullable|string|max:10',
            'FLW_secKey' => 'sometimes|nullable|string|max:10',
            'Razor_pubKey' => 'sometimes|nullable|string|max:8',
            'Razor_secKey' => 'sometimes|nullable|string|min:5',
            'stripe_pubKey' => 'sometimes|nullable|string|min:5',
            'stripe_secKey' => 'sometimes|nullable|string|min:5',
        ]);
    
        try {
            if ($user->hasRole('customer')) {
                // Update or create customer payment settings
                $customer = Customer::firstOrNew(['user_id' => $user->id]);
                $customer->fill($validated);
    
                if (!$customer->save()) {
                    return response()->json(['message' => 'Failed to update customer payment settings'], 500);
                }
            } 
            elseif ($user->hasRole('businessadministrator')) {
                // Ensure the user has an associated branch
                if (!$user->branch) {
                    return response()->json(['message' => 'Branch not found'], 404);
                }
    
                $branch = Branch::where('user_id', $user->id)->first();
    
                if (!$branch) {
                    return response()->json(['message' => 'Branch not found'], 404);
                }
    
                $branch->fill($validated);
    
                if (!$branch->save()) {
                    return response()->json(['message' => 'Failed to update branch payment settings'], 500);
                }
            }
             
            elseif ($user->hasRole('superadministrator')) {
                // Ensure the user has an associated branch
                // if (!$user->branch) {
                //     return response()->json(['message' => 'Branch not found'], 404);
                // }
    
                $superadmin = SuperAdmin::where('user_id', $user->id)->first();
    
                if (!$superadmin) {
                    return response()->json(['message' => 'Branch not found'], 404);
                }
    
                $superadmin->fill($validated);
    
                if (!$superadmin->save()) {
                    return response()->json(['message' => 'Failed to update branch payment settings'], 500);
                }
            }
    
            return response()->json([
                'message' => 'Payment settings updated successfully',
                'data' => $validated
            ]);
    
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateAccount(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'trucking_bank_name' => 'sometimes|nullable|string',
            'trucking_account_name' => 'sometimes|nullable|string',
            'trucking_account_number' => 'sometimes|nullable|integer',
            'trucking_routing' => 'sometimes|nullable|string',
            'trucking_zelle' => 'sometimes|nullable|string', // Increased from max:8
            'trucking_pay_cargo' => 'sometimes|nullable|string',
            'ocean_bank_name' => 'sometimes|nullable|string',
            'ocean_account_name' => 'sometimes|nullable|string',
            'ocean_account_number' => 'sometimes|nullable|string',
            'ocean_routing' => 'sometimes|nullable|string',
            'ocean_zelle' => 'sometimes|nullable|string',
        ]);

        //dd($validated);

        DB::beginTransaction();

        try {
            
        if ($user->hasRole('customer')) {
                $customer = Customer::where('user_id', $user->id)->firstOrFail();
                $customer->fill($validated);
                
                if (!$customer->save()) {
                    throw new \Exception('Failed to update customer account settings');
                }
            } 
            elseif ($user->hasRole('businessadministrator')) {
                $branch = Branch::where('user_id', $user->id)->firstOrFail();
                $branch->fill($validated);
                
                if (!$branch->save()) {
                    throw new \Exception('Failed to update branch account settings');
                }
            } 
            
            elseif ($user->hasRole('superadministrator')) {
                $superadmin = SuperAdmin::where('user_id', $user->id)->firstOrFail();
                $superadmin->fill($validated);
                
                if (!$superadmin->save()) {
                    throw new \Exception('Failed to update branch account settings');
                }
            } 
            
            else {
                throw new \Exception('Unauthorized role');
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Account settings updated successfully',
                'data' => $validated
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Failed to update mail settings'
            ], 500);
        }
    }

   public function createOffices(Request $request)
    {
        $user = auth()->user();
        $branchId = $user->getBranchId();

        $validator = Validator::make($request->all(), [
            'short_name.*' => 'sometimes|string|max:255',
            'long_name.*' => 'sometimes|string|max:255',
            'id.*' => 'sometimes|integer|exists:office_locations,id,branch_id,'.$branchId,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();

        try {
            // First get all existing offices for this branch
            $existingOfficeIds = OfficeLocation::where('branch_id', $branchId)
                ->pluck('id')
                ->toArray();

            $submittedIds = $validated['id'] ?? [];

            // Delete offices that were removed from the form
            $idsToDelete = array_diff($existingOfficeIds, $submittedIds);
            if (!empty($idsToDelete)) {
                OfficeLocation::whereIn('id', $idsToDelete)->delete();
            }

            // Process submitted offices
            foreach ($validated['short_name'] as $i => $shortName) {
                $officeData = [
                    'branch_id' => $branchId,
                    'short_name' => $shortName ?? null,
                    'long_name' => $validated['long_name'][$i] ?? null,
                ];

                if (!empty($validated['id'][$i])) {
                    // Update existing office
                    OfficeLocation::where('id', $validated['id'][$i])
                        ->update($officeData);
                } else {
                    // Create new office
                    OfficeLocation::create($officeData);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Offices updated successfully',
                'data' => OfficeLocation::where('branch_id', $branchId)->get()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Failed to update offices settings'
            ], 500);
        }
    }


    public function updateMailer(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'mail_driver' => 'sometimes|nullable|string',
            'mail_host' => 'sometimes|nullable|string',
            'mail_port' => 'sometimes|nullable|integer',
            'mail_encryption' => 'sometimes|nullable|string',
            'mail_username' => 'sometimes|nullable|string', // Increased from max:8
            'mail_password' => 'sometimes|nullable|string',
            'mail_from_address' => 'sometimes|nullable|string',
            'mail_from_name' => 'sometimes|nullable|string',
        ]);
    
        DB::beginTransaction();
    
        try {
            if ($user->hasRole('customer')) {
                $customer = Customer::where('user_id', $user->id)->firstOrFail();
                $customer->fill($validated);
                
                if (!$customer->save()) {
                    throw new \Exception('Failed to update customer mail settings');
                }
            } 
            elseif ($user->hasRole('businessadministrator')) {
                $branch = Branch::where('user_id', $user->id)->firstOrFail();
                $branch->fill($validated);
                
                if (!$branch->save()) {
                    throw new \Exception('Failed to update branch mail settings');
                }
            } 
            
            elseif ($user->hasRole('superadministrator')) {
                $superadmin = SuperAdmin::where('user_id', $user->id)->firstOrFail();
                $superadmin->fill($validated);
                
                if (!$superadmin->save()) {
                    throw new \Exception('Failed to update branch mail settings');
                }
            } 
            
            else {
                throw new \Exception('Unauthorized role');
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Mail settings updated successfully',
                'data' => $validated
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Failed to update mail settings'
            ], 500);
        }
    }

    
    public function updateSecurity(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'enable_email_otp' => 'sometimes|nullable|boolean',
            'enable_2fa' => 'sometimes|nullable|boolean',
        ]);
    
        if ($user->hasRole('customer')) {
            // Update customer record with user_id constraint
            $customer = Customer::where('user_id', $user->id)->first();
            
            if (!$customer) {
                $customer = new Customer(['user_id' => $user->id]);
            }
            
            $customer->fill($validated)->save();
        } 
        elseif ($user->hasRole('businessadministrator')) {
            //dd($user);
            if (!$user->branch) {
                return response()->json(['message' => 'Branch not found'], 404);
            }
            
            $branch = Branch::where('user_id', $user->id)->first();
            
            if (!$branch) {
                return response()->json(['message' => 'Branch not found'], 404);
            }
            
            $branch->fill($validated)->save();
        }
        
        elseif ($user->hasRole('superadministrator')) {
            //dd($user);
            // if (!$user->branch) {
            //     return response()->json(['message' => 'Branch not found'], 404);
            // }
            
            $superadmin = SuperAdmin::where('user_id', $user->id)->first();
            
            if (!$superadmin) {
                return response()->json(['message' => 'Branch not found'], 404);
            }
            
            $superadmin->fill($validated)->save();
        }
    
        return response()->json([
            'message' => 'Security settings updated successfully',
            'data' => $validated
        ]);
    }

    //Customer methods
    public function updateBasic(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'invoice_footer_note' => 'sometimes|nullable|string|min:5',
            'customer_sales_rep' => 'sometimes|nullable|string|min:3',
            'fax_no' => 'sometimes|nullable|string|max:20',
            'toll_free' => 'sometimes|nullable|string|max:10',
            'note' => 'sometimes|nullable|string|max:255',
            'internal_note' => 'sometimes|nullable|string|min:5',
            //'tag' => 'sometimes|nullable',
            'flash_note_for_drivers' => 'sometimes|nullable|string|min:5',
            'flash_note_for_accounting' => 'sometimes|nullable|string|min:5',
            'other_notes' => 'sometimes|nullable|string|min:5',
            'start_date' => 'sometimes|nullable|string|min:5',
            'credit_limit' => 'sometimes|nullable|string|min:1',
            'alert_percentage' => 'sometimes|nullable|string|min:1|max:100',
            
        ]);
    
        DB::beginTransaction();
        //  if (isset($validated['tags'])) {
        //     if (is_string($validated['tags'])) {
        //         $tagsArray = explode(',', $validated['tags']);
        //     } 
        //     elseif (is_string($validated['tags']) && json_decode($validated['tags'])) {
        //         $tagsArray = json_decode($validated['tags'], true);
        //     }
        //     else {
        //         $tagsArray = $validated['tags'];
        //     }
            
        //     $tagsArray = array_values(array_filter(array_map('trim', $tagsArray)));
        //     $validated['tags'] = !empty($tagsArray) ? $tagsArray : null;
        // }
    
        try {
            if ($user->hasRole('customer')) {
                $customer = Customer::where('user_id', $user->id)->firstOrFail();
                $customer->fill($validated);
                
                if (!$customer->save()) {
                    throw new \Exception('Failed to update customer mail settings');
                }
            } 
            elseif ($user->hasRole('driver')) {
                $driver = Driver::where('user_id', $user->id)->firstOrFail();
                $driver->fill($validated);
                
                if (!$branch->save()) {
                    throw new \Exception('Failed to update branch mail settings');
                }
            } else {
                throw new \Exception('Unauthorized role');
            }

    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Basic settings updated successfully',
                'data' => $validated
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Failed to update basic settings'
            ], 500);
        }
    }

    public function updateAddress(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'customer_phone' => 'sometimes|nullable|string|min:5',
            'customer_primary_address' => 'sometimes|nullable|string|min:8',
            'customer_secondary_address' => 'sometimes|nullable|string|min:8',
            'customer_country' => 'sometimes|nullable|string|max:10',
            'customer_state' => 'sometimes|nullable|string|max:255',
            'customer_zip' => 'sometimes|nullable|string|min:5',
            'customer_office' => 'sometimes|nullable|string|min:5',
        ]);
    
        DB::beginTransaction();
    
        try {
            if ($user->hasRole('customer')) {
                $customer = Customer::where('user_id', $user->id)->firstOrFail();
                $customer->fill($validated);
                
                if (!$customer->save()) {
                    throw new \Exception('Failed to update customer Address settings');
                }

            } else {
                throw new \Exception('Unauthorized role');
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Address settings updated successfully',
                'data' => $validated
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Failed to update address settings'
            ], 500);
        }
    }

    public function updateOther(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'isSubAccount' => 'sometimes|nullable|in:0,1',
            'subAccount_of' => 'sometimes|nullable|string|min:10',
            'isFactoredInvoice' => 'sometimes|nullable|in:0,1',
            'factoringCompany' => 'sometimes|nullable|string|max:100',
            'isPrepaid' => 'sometimes|nullable|in:0,1',
            'isNonBillable' => 'sometimes|nullable|in:0,1',
        ]);
    
        DB::beginTransaction();
    
        try {
            if ($user->hasRole('customer')) {
                $customer = Customer::where('user_id', $user->id)->firstOrFail();
                $customer->fill($validated);
                
                if (!$customer->save()) {
                    throw new \Exception('Failed to update customer settings');
                }

            } else {
                throw new \Exception('Unauthorized role');
            }
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => $validated
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Failed to update settings'
            ], 500);
        }
    }

    //Driver methods
    public function updateDriverBasic(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'driver_type' => 'sometimes|nullable|string',
            'emergency_contact_info' => 'sometimes|nullable|string',
            'hired_on' => 'sometimes|nullable|date',
            'terminated_on' => 'sometimes|nullable|date',
            'years_of_experience' => 'sometimes|nullable|integer',
            'endorsements' => 'sometimes|nullable|string',
            'tags' => 'sometimes|nullable|string|min:5',
            'rating' => 'sometimes|nullable|string',
            'notes_about_the_choices_made' => 'sometimes|nullable|string|min:5',
            'pay_via' => 'sometimes|nullable|string|min:5',
            'company_name_paid_to' => 'sometimes|nullable|string|min:3',
            'employer_identification_number' => 'sometimes|nullable|string|min:1',
            'send_settlements_mail' => 'sometimes|nullable|string|min:6',
            'print_settlements_under_this_company' => 'sometimes|nullable|string|min:3',
            'flash_notes_to_dispatch' => 'sometimes|nullable|string|min:10',
            'flash_notes_to_payroll' => 'sometimes|nullable|string|min:10',
            'internal_notes' => 'sometimes|nullable|string|min:10',
            //'driver_status' => 'sometimes|nullable|string|min:10',
        ]);
    
        DB::beginTransaction();
    
        try {
            if ($user->hasRole('driver')) {
                $customer = Driver::where('user_id', $user->id)->firstOrFail();
                $customer->fill($validated);
                
                if (!$customer->save()) {
                    throw new \Exception('Failed to update driver Basic settings');
                }
            } else {
                throw new \Exception('Unauthorized role');
            }

    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Basic settings updated successfully',
                'data' => $validated
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Failed to update basic settings'
            ], 500);
        }
    }

    public function updateDriverAddress(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'driver_phone' => 'sometimes|nullable|string',
            'driver_phone_carrier' => 'sometimes|nullable|string',
            'driver_primary_address' => 'sometimes|nullable|string|min:8',
            'driver_secondary_address' => 'sometimes|nullable|string|min:8',
            'driver_country' => 'sometimes|nullable|string',
            'driver_state' => 'sometimes|nullable|string',
            'driver_city' => 'sometimes|nullable|string|min:3',
            'driver_zip' => 'sometimes|nullable|integer',
            'office' => 'sometimes|nullable|string|min:1',
        ]);
    
        DB::beginTransaction();
    
        try {
            if ($user->hasRole('driver')) {
                $customer = Driver::where('user_id', $user->id)->firstOrFail();
                $customer->fill($validated);
                
                if (!$customer->save()) {
                    throw new \Exception('Failed to update driver Basic settings');
                }
            } else {
                throw new \Exception('Unauthorized role');
            }

    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Address settings updated successfully',
                'data' => $validated
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Failed to update address settings'
            ], 500);
        }
    }

    public function updateDriverOther(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'isAccessToMobileApp' => 'sometimes|nullable|integer',
            'mobile_settings' => 'sometimes|nullable|integer',
        ]);
    
        DB::beginTransaction();
    
        try {
            if ($user->hasRole('driver')) {
                $customer = Driver::where('user_id', $user->id)->firstOrFail();
                $customer->fill($validated);
                
                if (!$customer->save()) {
                    throw new \Exception('Failed to update driver Basic settings');
                }
            } else {
                throw new \Exception('Unauthorized role');
            }

    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Address settings updated successfully',
                'data' => $validated
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => 'Failed to update address settings'
            ], 500);
        }
    }
}
