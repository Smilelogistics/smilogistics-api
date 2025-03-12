<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthService
{
    /**
     * Attempt to log in a user.
     *
     * @param array $credentials
     * @return array
     */
    public static function attemptLogin(array $credentials)
    {
        // Validate the credentials array
        $validator = Validator::make($credentials, [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return [
                'status' => 'error',
                'errors' => $validator->errors(),
            ];
        }
    
        // Retrieve the user by email from the credentials array
        $user = User::where('email', $credentials['email'])->first();
    
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return [
                'status'  => 'error',
                'message' => 'Invalid credentials',
            ];
        }
    
        // Create an API token for the user and store it in session if needed
        $token = $user->createToken('api-token')->plainTextToken;
        session(['api_token' => $token]);
    
        return [
            'status'  => 'success',
            'message' => 'Login successful!',
            'user'    => $user,
            'token'   => $token,
        ];
    }
    
}
