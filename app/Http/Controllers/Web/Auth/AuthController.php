<?php

namespace App\Http\Controllers\Web\Auth;

use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function getRegister()
    {
        return view('auth.register');
    }
    public function getLogin()
    {
        return view('auth.login');
    }

    public function postRegister(Request $request)
    {

    }

    public function postLogin(Request $request)
    {
        $result = AuthService::attemptLogin($request->all());

        if ($result['status'] !== 'success') {
            return redirect()->back()->with('error', $result['message'] ?? 'Login failed');
        }
        Auth::login($result['user']);
        session(['api_token' => $result['token']]);
        return redirect('/dashboard')->with('success', 'Login successful!');
    }

}
