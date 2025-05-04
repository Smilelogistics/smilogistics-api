<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use App\Models\Driver;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class SettingsController extends Controller
{
    public function index() {
        
        $user = auth()->user();
        if ($user->hasRole('customer')) {
            $data = Customer::with('branch')->where('user_id', $user->id)->get();
            return response()->json($data);
        }
        elseif ($user->hasRole('businessadministrator')) {
            $data = Branch::where('user_id', $user->id)->get();
            return response()->json($data);
        }
        
    }

//     public function updateGeneral(Request $request)
// {
//     try {
//         $user = auth()->user();
        
//         // Validate input
//         $validated = $request->validate([
//             'phone' => 'nullable|string|max:20',
//             'address' => 'nullable|string|min:10|max:255',
//             'parcel_prefix' => 'nullable|string|max:10',
//             'invoice_prefix' => 'nullable|string|max:10',
//             'currency' => 'nullable|string|size:3',
//             'copyright' => 'nullable|string|min:5|max:100',
//             'logo1' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
//             'logo2' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
//             'logo3' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
//             'favicon' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
//             'mpg' => 'sometimes|integer',
//         ]);

//         // Define upload directory
//         $uploadPath = public_path('uploads/logos');

//         // Create folder if not exists
//         if (!file_exists($uploadPath)) {
//             mkdir($uploadPath, 0777, true);
//         }

//         foreach (['logo1', 'logo2', 'logo3'] as $logoField) {
//             if ($request->hasFile($logoField)) {
//                 // Generate unique file name
//                 // $fileName = $logoField . '_' . time() . '.' . $request->file($logoField)->extension();
//                 // $request->file($logoField)->move($uploadPath, $fileName);
//                 // $logoPaths[$logoField] = 'uploads/logos/' . $fileName;

//                 if ($file->isValid()) {
//                     $uploadedFile = Cloudinary::upload($file->getRealPath(), [
//                         'folder' => 'Smile_logistics/Branch_Logos',
//                     ]);
        
//                     Branch::create([
//                         'truck_id' => $truck->id,
//                         'logo1' => $uploadedFile->getSecurePath(),
//                         'logo2' => $uploadedFile->getSecurePath(),
//                         'logo3' => $uploadedFile->getSecurePath(),
//                         //'public_id' => $uploadedFile->getPublicId()
//                     ]);
//                 }
//             }
//         }
        

//         $updateData = $validated; //array_merge($validated, $logoPaths);

//         $updateData = array_filter($updateData);

//         // if ($user->hasRole('customer')) {
//         //     $customer = Customer::updateOrCreate(
//         //         ['user_id' => $user->id],
//         //         $updateData
//         //     );

//         //     foreach ($logoPaths as $field => $path) {
//         //         if (!empty($customer->{$field})) {
//         //             Storage::delete(str_replace('storage/', 'public/', $customer->{$field}));
//         //         }
//         //     }
//         // } 
//         // else
//         if ($user->hasRole('businessadministrator')) {
//             if (!$user->branch) {
//                 return response()->json([
//                     'message' => 'Branch not found',
//                     'hint' => 'Contact administrator to assign you to a branch'
//                 ], 404);
//             }

//             $branch = $user->branch;
            
//             // Delete old logos before updating
//             foreach ($logoPaths as $field => $path) {
//                 if (!empty($branch->{$field})) {
//                     Storage::delete(str_replace('storage/', 'public/', $branch->{$field}));
//                 }
//             }

//             $branch->update($updateData);
//         }
//         else {
//             return response()->json([
//                 'message' => 'Unauthorized action',
//                 'hint' => 'Your role cannot update these settings'
//             ], 403);
//         }

//         return response()->json([
//             'message' => 'General Settings updated successfully',
//             'data' => $updateData,
//             'logo_urls' => $logoPaths
//         ]);

//     } catch (ValidationException $e) {
//         return response()->json([
//             'message' => 'Validation failed',
//             'errors' => $e->errors()
//         ], 422);
        
//     } catch (\Exception $e) {
//         Log::error("Settings update failed: " . $e->getMessage());
//         return response()->json([
//             'message' => 'Update failed',
//             'hint' => 'Please try again or contact support'
//         ], 500);
//     }
// }

public function updateGeneral(Request $request)
{
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
            'logo1' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo2' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo3' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'favicon' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'mpg' => 'sometimes|integer',
        ]);

        // Initialize logo paths array
        $logoPaths = [];

        // Handle file uploads to Cloudinary
        foreach (['logo1', 'logo2', 'logo3', 'favicon'] as $logoField) {
            if ($request->hasFile($logoField)) {
                $file = $request->file($logoField);
                
                if ($file->isValid()) {
                    $uploadedFile = Cloudinary::upload($file->getRealPath(), [
                        'folder' => 'Smile_logistics/Branch_Logos',
                    ]);
                    
                    $logoPaths[$logoField] = $uploadedFile->getSecurePath();
                }
            }
        }

        // Prepare update data
        $updateData = array_merge($validated, $logoPaths);
        $updateData = array_filter($updateData);

        if ($user->hasRole('businessadministrator')) {
            if (!$user->branch) {
                return response()->json([
                    'message' => 'Branch not found',
                    'hint' => 'Contact administrator to assign you to a branch'
                ], 404);
            }

            $branch = $user->branch;
            
            // Update branch with new data
            $branch->update($updateData);

            return response()->json([
                'message' => 'General Settings updated successfully',
                'data' => $updateData,
                'logo_urls' => $logoPaths
            ]);
        }

        return response()->json([
            'message' => 'Unauthorized action',
            'hint' => 'Your role cannot update these settings'
        ], 403);

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
    
            return response()->json([
                'message' => 'Payment settings updated successfully',
                'data' => $validated
            ]);
    
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateMailer(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'mail_driver' => 'sometimes|nullable|string',
            'mail_host' => 'sometimes|nullable|string|min:10',
            'mail_port' => 'sometimes|nullable|integer|max:10',
            'mail_encryption' => 'sometimes|nullable|string|max:10',
            'mail_username' => 'sometimes|nullable|string|max:80', // Increased from max:8
            'mail_password' => 'sometimes|nullable|string|min:5',
            'mail_from' => 'sometimes|nullable|string|min:5',
            'mail_from_name' => 'sometimes|nullable|string|min:5',
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
            } else {
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
            'tag' => 'sometimes|nullable|string|min:5',
            'flash_note_for_drivers' => 'sometimes|nullable|string|min:5',
            'flash_note_for_accounting' => 'sometimes|nullable|string|min:5',
            'other_notes' => 'sometimes|nullable|string|min:5',
            'start_date' => 'sometimes|nullable|string|min:5',
            'credit_limit' => 'sometimes|nullable|string|min:1',
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
            'driver_status' => 'sometimes|nullable|string|min:10',
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
