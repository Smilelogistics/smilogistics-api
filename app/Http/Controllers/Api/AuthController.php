<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\Customer;
use App\Mail\newBranchMail;
use App\Mail\newDriverMail;
use Illuminate\Http\Request;
use App\Mail\newCustomerMail;
use App\Services\AuthService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
     // User Registration
     //super admin token
     //4|Bo6rLmYLskMnDhSzhaSiXfIA32z7KJCHorDaAPRaef6af829
     public function register(Request $request)
     {
        $user = auth()->user();

        if ($user->user_type == 'businessadmin' && $request->user_type == 'superadmin') {
            return response()->json(['error' => 'You cannot register a superadmin as a business admin.'], 400);
        }
         $validator = Validator::make($request->all(), [
             'fname' => 'required|string|max:255',
             'mname' => 'nullable|string|max:255',
             'lname' => 'required|string|max:255',
             'email' => 'required|email|max:255|unique:users',
             'password' => 'required|string|min:8|confirmed',
             'user_type' => 'required|in:superadministrator,businessadministrator,businessmanager,customer,driver,user',
             'phone' => 'required_if:user_type,businessadmin',
         ]);
     
         if ($validator->fails()) {
             return response()->json(['errors' => $validator->errors()], 422);
         }
     
         DB::beginTransaction();
         try {
             // Create User First
             $user = User::create([
                 'fname' => $request->fname,
                 'mname' => $request->mname,
                 'lname' => $request->lname,
                 'email' => $request->email,
                 'password' => Hash::make('123456789'),
                 'user_type' => $request->user_type,
             ]);

             //$user->sendEmailVerificationNotification();
     
             // Check user type and create related records
             if ($user->user_type == 'businessadministrator') {
                 Branch::create([
                     'user_id' => $user->id, // Ensure user_id is assigned
                     'branch_code' => 'SML-' . $user->id,
                     'phone' => $request->phone ?? null,
                     'address' => $request->address ?? null,
                     'about_us' => $request->about_us ?? null
                 ]);
                 Mail::to($user->email)->send(new newBranchMail($user));
     
             } elseif ($user->user_type == 'customer') {
                $authuser = auth()->user();
                //dd($authuser);
                $branchId = $authuser->branch ? $authuser->branch->id : null; 
                 Customer::create([
                     'user_id' => $user->id,
                     'branch_id' => $branchId
                 ]);
                 Mail::to($user->email)->send(new newCustomerMail($user));
     
             } elseif ($user->user_type == 'driver') {
                $authuser = auth()->user();
                $branchId = $authuser->branch ? $authuser->branch->id : null; 
                 Driver::create([
                     'user_id' => $user->id,
                     'branch_id' => $branchId
                 ]);
                 Mail::to($user->email)->send(new newDriverMail($user));
             }
             
             $user->addRole($user->user_type);
           
             DB::commit();
             $token = $user->createToken('api-token')->plainTextToken;
     
             return response()->json([
                 'message' => 'User registered successfully!',
                 'user' => $user,
                 'token' => $token,
             ]);
         } catch (\Exception $e) {
             DB::rollback(); // Rollback on error
     
             return response()->json(['error' => 'Registration failed!', 'details' => $e->getMessage()], 500);
         }
     }
     
     
     //login
     public function login(Request $request)
    {
        $result = AuthService::attemptLogin($request->all());

        if ($result['status'] === 'error') {
            // Return error response for API clients
            return response()->json($result, isset($result['errors']) ? 422 : 401);
        }

        return response()->json([
            'message' => 'Login successful!',
            'user'    => $result['user'],
            'token'   => $result['token'],
        ]);
    }

    public function login2(Request $request)
    {
        if(Auth::attempt([
            'email' => $request->email,
            'password' => $request->password
        ])){
            return response()->json([
                'message' => 'Login successful!',
            ]);
        }

    }

    //logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logout successful!']);
    }
    
    //reset password

    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Reset link sent to your email']);
        }

        return response()->json(['message' => 'Unable to send reset link'], 500);
    }

    public function resetPassword(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Reset password using Laravel's Password Broker
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        // Check reset status
        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successful'], 200);
        }

        return response()->json(['message' => __($status)], 400);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully!']);
    }


    public function user()
    {
        return response()->json(auth()->user());
    }

}
