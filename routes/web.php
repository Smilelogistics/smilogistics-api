<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\UnivController;
use App\Http\Controllers\Web\ShipmentController;
use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\Auth\DashboardController;

Route::get('/', [ShipmentController::class, 'test'])->name('login');

// Route::get('/login', [AuthController::class, 'getLogin'])->name('login');
// Route::post('/login', [AuthController::class, 'postLogin'])->name('PostLogin');
// Route::get('/register', [AuthController::class, 'getRegister'])->name('register');

// Route::middleware('auth')->group(function() {
//     Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
//     Route::get('/home', [DashboardController::class, 'home'])->name('home');
// });

// Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified',
// ])->group(function () {
//     Route::get('/dashboard', function () {
//         return view('dashboard');
//     })->name('dashboard');
// });
