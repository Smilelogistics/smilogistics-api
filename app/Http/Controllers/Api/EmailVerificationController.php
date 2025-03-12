<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Mail\newBranchMail;
use App\Mail\newDriverMail;
use Illuminate\Http\Request;
use App\Mail\newCustomerMail;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    public function verify(Request $request)
    {
        // Find user by ID
        $user = User::find($request->route('id'));

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Check if user is already verified
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $token = $user->createToken('api-token')->plainTextToken;

        // Send welcome email after verification
        if ($user->user_type == 'businessadmin') {
            Mail::to($user->email)->send(new newBranchMail($user));
        } elseif ($user->user_type == 'customer') {
            Mail::to($user->email)->send(new newCustomerMail($user));
        } elseif ($user->user_type == 'driver') {
            Mail::to($user->email)->send(new newDriverMail($user));
        }

        return response()->json(['message' => 'Email verified successfully.']);
    }

    /**
     * Resend the email verification notification.
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent.']);
    }
}
