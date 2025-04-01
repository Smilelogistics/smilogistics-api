<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

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
        ]);

        // Define upload directory
        $uploadPath = public_path('uploads/logos');

        // Create folder if not exists
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $logoPaths = [];

        // Handle file uploads
        foreach (['logo1', 'logo2', 'logo3'] as $logoField) {
            if ($request->hasFile($logoField)) {
                // Generate unique file name
                $fileName = $logoField . '_' . time() . '.' . $request->file($logoField)->extension();

                // Move file to the folder
                $request->file($logoField)->move($uploadPath, $fileName);

                // Save path in validated data
                $logoPaths[$logoField] = 'uploads/logos/' . $fileName;
            }
        }

        // Merge logos with other data
        $updateData = array_merge($validated, $logoPaths);

        // Remove null logo paths
        $updateData = array_filter($updateData);

        if ($user->hasRole('customer')) {
            $customer = Customer::updateOrCreate(
                ['user_id' => $user->id],
                $updateData
            );

            // Delete old logos if new ones were uploaded
            foreach ($logoPaths as $field => $path) {
                if (!empty($customer->{$field})) {
                    Storage::delete(str_replace('storage/', 'public/', $customer->{$field}));
                }
            }
        } 
        elseif ($user->hasRole('businessadministrator')) {
            if (!$user->branch) {
                return response()->json([
                    'message' => 'Branch not found',
                    'hint' => 'Contact administrator to assign you to a branch'
                ], 404);
            }

            $branch = $user->branch;
            
            // Delete old logos before updating
            foreach ($logoPaths as $field => $path) {
                if (!empty($branch->{$field})) {
                    Storage::delete(str_replace('storage/', 'public/', $branch->{$field}));
                }
            }

            $branch->update($updateData);
        }
        else {
            return response()->json([
                'message' => 'Unauthorized action',
                'hint' => 'Your role cannot update these settings'
            ], 403);
        }

        return response()->json([
            'message' => 'Settings updated successfully',
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
}
