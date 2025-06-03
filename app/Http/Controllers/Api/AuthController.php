<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\Customer;
use App\Mail\newBranchMail;
use App\Mail\newDriverMail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\newCustomerMail;
use App\Services\AuthService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Notifications\OtpNotification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use App\Notifications\NewBranchNotification;

class AuthController extends Controller
{
     protected $otpExpiryMinutes = 5; // OTP expires after 5 minutes
    protected $resendCooldown = 1; // 1 minute cooldown for resend

     // User Registration
     //super admin token
     //4|Bo6rLmYLskMnDhSzhaSiXfIA32z7KJCHorDaAPRaef6af829
     public function register(Request $request)
     {
        //dd(env('DB_DATABASE')); 
         $user = auth()->user();
        // 1|CaqoIM26iLaKYJNiBTmepTxmYNiaCmAdPEIKfSJP879c0a61

        if ($user->user_type == 'businessadministrator' && $request->user_type == 'superadministrator') {
            return response()->json(['error' => 'You cannot register a superadmin as a business admin.'], 400);
        }
         $validator = Validator::make($request->all(), [
             'fname' => 'required|string|max:255',
             'mname' => 'nullable|string|max:255',
             'lname' => 'nullable|string|max:255',
             'email' => 'required|email|max:255|unique:users',
             'password' => 'nullable|string|min:8',
             'user_type' => 'required|in:superadministrator,businessadministrator,businessmanager,customer,driver,user',
             //'phone' => 'nullable|string|max:20',
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
                 'password' => Hash::make($request->password ??'123456789'),
                 'user_type' => $request->user_type,
             ]);

             //$user->sendEmailVerificationNotification();
     
             // Check user type and create related records
             if ($user->user_type == 'businessadministrator') {
                 Branch::create([
                     'user_id' => $user->id,
                     'branch_code' => 'SML-' . $user->id,
                     'phone' => $request->phone ?? null,
                     'address' => $request->address ?? 'No address provided',
                     'about_us' => $request->about_us ?? null,
                 ]);
                 Mail::to($user->email)->send(new newBranchMail($user));
                 $user->notify(new NewBranchNotification($user));
     
             } elseif ($user->user_type == 'customer') {
                $authuser = auth()->user();
                //dd($authuser);
                $branchId = auth()->user()->getBranchId();
                 Customer::create([
                     'user_id' => $user->id,
                     'branch_id' => $branchId
                 ]);
                 Mail::to($user->email)->send(new newCustomerMail($user));
     
             } elseif ($user->user_type == 'driver') {
                $authuser = auth()->user();
                $branchId = auth()->user()->getBranchId();
                 Driver::create([
                     'user_id' => $user->id,
                     'branch_id' => $branchId
                 ]);
                 //Mail::to($user->email)->send(new newDriverMail($user));
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


     public function guestRegister(Request $request)
     {
        //dd(env('DB_DATABASE'));
        // $user = auth()->user();
        // 1|CaqoIM26iLaKYJNiBTmepTxmYNiaCmAdPEIKfSJP879c0a61

        // if ($user->user_type == 'businessadministrator' && $request->user_type == 'superadministrator') {
        //     return response()->json(['error' => 'You cannot register a superadmin as a business admin.'], 400);
        // }
         $validator = Validator::make($request->all(), [
             'fname' => 'required|string|max:255',
             'mname' => 'nullable|string|max:255',
             'lname' => 'nullable|string|max:255',
             'email' => 'required|email|max:255|unique:users',
             'password' => 'nullable|string|min:8',
             //'user_type' => 'required|in:superadministrator,businessadministrator,businessmanager,customer,driver,user',
             //'phone' => 'nullable|string|max:20',
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
                 'password' => Hash::make($request->password ??'123456789'),
                 'user_type' => 'businessadministrator',
             ]);

             setDynamicMailConfig();

                 Branch::create([
                     'user_id' => $user->id,
                     'branch_code' => 'SML-' . $user->id,
                     'phone' => $request->phone ?? null,
                     'address' => $request->address ?? 'No address provided',
                     'about_us' => $request->about_us ?? null,
                 ]);
                 Mail::to($user->email)->send(new newBranchMail($user));
                 $user->notify(new NewBranchNotification($user));
     
             $user->addRole('businessadministrator');
           
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
            return response()->json($result, isset($result['errors']) ? 422 : 401);
        }

        $user = $result['user'];
        $requiresOtp = false;

        setDynamicMailConfig($user);

        // Check OTP requirements based on user role
        switch (true) {
            case $user->hasRole('businessadministrator'):
                $requiresOtp = $user->branch && $user->branch->enable_email_otp == 1;
                break;
                
            case $user->hasRole('customer'):
                $requiresOtp = $user->customer && $user->customer->enable_email_otp == 1;
                break;
                
            case $user->hasRole('driver'):
                $requiresOtp = $user->driver && $user->driver->otp == 1;
                break;
        }

        if ($requiresOtp) {
            // Generate and send OTP
            $otp = Str::padLeft(rand(1, 999999), 6, '0');
            $user->update([
                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(5),
                'otp_last_sent_at' => now()
            ]);

            //Mail::to($user->email)->send(new OtpMail($otp, 5));
            $user->notify(new OtpNotification($otp, 5));

            return response()->json([
                'status' => 'otp_required',
                'message' => 'OTP verification required.',
                'token' => $result['token'],
                'user' => $user->only(['id', 'email', 'name']), // Only send necessary user data
                'otp_expires_in' => 300 // 5 minutes in seconds
            ]);
        }
        else
        {

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful!',
                'user' => $user,
                'token' => $result['token'],
                //'user' => $user->only(['id', 'email', 'name']),
            ]);
        }
    }

    //  public function login(Request $request)
    // {
    //     $result = AuthService::attemptLogin($request->all());

    //     if ($result['status'] === 'error') {
    //         // Return error response for API clients
    //         return response()->json($result, isset($result['errors']) ? 422 : 401);
    //     }

    //     return response()->json([
    //         'message' => 'Login successful!',
    //         'user'    => $result['user'],
    //         'token'   => $result['token'],
    //     ]);
    // }

     public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        setDynamicMailConfig($user);
        // Check if we should allow resend
        if ($user->otp_last_sent_at && 
            now()->diffInMinutes($user->otp_last_sent_at) < $this->resendCooldown) {
            return response()->json([
                'message' => 'Please wait before requesting a new OTP',
                'retry_after' => $this->resendCooldown * 60 - now()->diffInSeconds($user->otp_last_sent_at)
            ], 429);
        }

        // Generate 6-digit OTP
        $otp = Str::padLeft(rand(1, 999999), 6, '0');
        $expiresAt = now()->addMinutes($this->otpExpiryMinutes);

        // Update user record
       $update = $user->update([
            'otp' => $otp,
            'otp_expires_at' => $expiresAt,
            'otp_last_sent_at' => now()
        ]);

        if(!$update)
        {
            return response()->json([
                'message' => 'Failed to send OTP',
            ], 500);
        }

        //dd($otp);

        // Send OTP email
        //Mail::to($user->email)->send(new OtpMail($otp, $this->otpExpiryMinutes));
        $user->notify(new OtpNotification($otp, $this->otpExpiryMinutes));

        return response()->json([
            'message' => 'OTP sent successfully',
            'expires_in' => $this->otpExpiryMinutes * 60
        ]);
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6'
        ]);

        $user = User::where('email', $request->email)->first();
        setDynamicMailConfig($user);
        // Check if OTP matches and isn't expired
        if ($user->otp === $request->otp && now()->lt($user->otp_expires_at)) {
            // Clear OTP after successful verification
            $user->update([
                'otp' => null,
                'otp_expires_at' => null
            ]);

            return response()->json([
                'message' => 'OTP verified successfully',
                'user' => $user
            ]);
        }

        return response()->json(['message' => 'Invalid or expired OTP'], 422);
    }

    /**
     * Resend OTP (uses same logic as sendOtp)
     */
    public function resendOtp(Request $request)
    {
        return $this->sendOtp($request);
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
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        setDynamicMailConfig();
        try {
            $status = Password::sendResetLink($request->only('email'));

            switch ($status) {
                case Password::RESET_LINK_SENT:
                    return response()->json(['message' => __($status)]);
                
                case Password::INVALID_USER:
                    return response()->json(['message' => __($status)], 400);
                    
                default:
                    return response()->json(['message' => __($status)], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Password reset error: '.$e->getMessage());
            return response()->json(['message' => 'Server error occurred'], 500);
        }
    }

    // public function sendResetLink(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'email' => 'required|email',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $status = Password::sendResetLink($request->only('email'));

    //     if ($status === Password::RESET_LINK_SENT) {
    //         return response()->json(['message' => 'Reset link sent to your email']);
    //     }

    //     return response()->json(['message' => 'Unable to send reset link'], 500);
    // }

    public function resetPassword(Request $request)
    {
        // Validate input
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
    setDynamicMailConfig();
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
    
                $user->save();
    
                event(new PasswordReset($user));
            }
        );
    
        return $status === Password::PASSWORD_RESET
                    ? response()->json(['status' => __($status)])
                    : response()->json(['email' => [__($status)]], 422);
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
        setDynamicMailConfig($user);
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully!']);
    }


    public function user()
    {
        return response()->json(auth()->user());
    }

}
