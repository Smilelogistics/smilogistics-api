<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\BikeController;
use App\Http\Controllers\Api\V1\UnivController;
use App\Http\Controllers\Api\V1\PlansController;
use App\Http\Controllers\Api\V1\TruckController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\CarrierController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DeliveryController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\ShipmentController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\SettlementController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\TransactionsController;
use App\Http\Controllers\Api\V1\ConsolidateShipmentController;
use App\Http\Controllers\Api\V1\ConsolidatedShipmentController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/test-db', function (Request $request) {
    try {
        DB::connection()->getPdo();
        return response()->json(['message' => '✅ Database connected successfully!']);
    } catch (\Exception $e) {
        return response()->json(['error' => '❌ Database not connected', 'details' => $e->getMessage()], 500);
    }
});


Route::get('/email/verify', function () {
    return response()->json(['message' => 'Please verify your email address.'], 403);
})->middleware('auth:sanctum')->name('verification.notice');


// ✅ Send verification email after registration
Route::post('/email/resend', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified.'], 200);
    }

    $request->user()->sendEmailVerificationNotification();
    return response()->json(['message' => 'Verification email sent.']);
})->middleware(['auth:sanctum']);

// ✅ Verify email when user clicks the link
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::find($id);

    if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    if (!hash_equals((string) $user->getKey(), (string) $id)) {
        return response()->json(['message' => 'Invalid verification ID.'], 403);
    }

    if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        return response()->json(['message' => 'Invalid verification hash.'], 403);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified.'], 200);
    }

    $user->markEmailAsVerified();
    event(new Verified($user));

    return response()->json(['message' => 'Email verified successfully.']);
})->middleware(['signed'])->name('verification.verify');



// ✅ Check if user has verified their email
Route::get('/email/check', function (Request $request) {
    return response()->json(['verified' => $request->user()->hasVerifiedEmail()]);
})->middleware(['auth:sanctum']);



Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::post('/guest-register', [AuthController::class, 'guestRegister']);
    Route::post('/send-reset-link', function (Request $request) {
        $request->validate(['email' => 'required|email']);
    
        $status = Password::sendResetLink(
            $request->only('email')
        );
    
        return $status === Password::RESET_LINK_SENT
                    ? response()->json(['status' => __($status)])
                    : response()->json(['email' => __($status)], 422);
    })->middleware('guest')->name('password.email');
    
    // Handle reset submission
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('guest')->name('password.update');

    Route::get('/payments/verify-paystack', [TransactionsController::class, 'verifyPaysatckPayment']);
    // Protected routes

    Route::middleware('auth:sanctum')->group(function () {
        
        Route::get('/user', [AuthController::class, 'user']);
    });
     
    
    Route::middleware('auth:sanctum', 'verified', 'check.subscription')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::prefix('dashboard')->group(function () {
            Route::get('/branches', [DashboardController::class, 'countBranches'])->name('dashboard.countBranches');
            Route::get('/monthly-income', [DashboardController::class, 'monthlyIncome'])->name('dashboard.monthlyIncome');
            Route::get('/dashboardstats', [DashboardController::class, 'dashboardStats'])->name('dashboard.dashboardStats');

        });

        Route::prefix('settings')->group(function () {
            Route::get('/rates', [SettingsController::class, 'getRates'])->name('settings.rates');
            Route::get('/data', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('/general', [SettingsController::class, 'updateGeneral'])->name('settings.general.update');
            Route::post('/payment', [SettingsController::class, 'updatePayment'])->name('settings.payment.update');
            Route::post('/mailer', [SettingsController::class, 'updateMailer'])->name('settings.mailer.update');
            Route::post('/security', [SettingsController::class, 'updateSecurity'])->name('settings.security.update');

            Route::post('/basic', [SettingsController::class, 'updateBasic'])->name('settings.basic.update');
            Route::post('/address', [SettingsController::class, 'updateAddress'])->name('settings.address.update');
            Route::post('/other', [SettingsController::class, 'updateOther'])->name('settings.other.update');

            Route::post('/Dbasic', [SettingsController::class, 'updateDriverBasic'])->name('settings.driver.basic.update');
            Route::post('/Daddress', [SettingsController::class, 'updateDriverAddress'])->name('settings.driver.address.update');
            Route::post('/Dother', [SettingsController::class, 'updateDriverOther'])->name('settings.driver.other.update');
        });

        // Shipment routes
        Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
        Route::get('/shipments/show/{id}', [ShipmentController::class, 'show'])->name('shipments.show');
        Route::post('/shipments/create', [ShipmentController::class, 'store'])->name('shipments.store');
        Route::put('/updateShipmentStatus/{id}', [ShipmentController::class, 'updateShipment'])->name('shipments.update');
        Route::get('/shipments/track', [ShipmentController::class, 'trackShipment'])->name('shipments.track');
        Route::post('/agency', [ShipmentController::class, 'storeAgency'])->name('shipments.agency.store');
        //this route is for updating shipments, in the case of typo or something during uploads
        Route::put('/shipments/update/{id}', [ShipmentController::class, 'update'])->name('shipments.updateAll');
        //Route::post('/shipments/consolidate', [ConsolidatedShipmentController::class, 'consolidateShipment'])->name('shipments.consolidate');
        Route::get('/get-consolidated-shipments', [ConsolidatedShipmentController::class, 'getConsolidatedShipment'])->name('get.consolidated.shipments');
        Route::get('/get-pending-consolidated-shipments', [ConsolidatedShipmentController::class, 'pendingConsolidatedShipment'])->name('get.pending.consolidated.shipments');
        Route::get('/get-consolidated-shipments-by-customer-email', [ConsolidatedShipmentController::class, 'getConsolidatedShipmentByCustomrEmail'])->name('email.consolidate');
        Route::get('/get-agency', [ShipmentController::class, 'getAgency'])->name('get.agency');
        // Route::get('/shipments', [ShipmentController::class, 'index']);
        // Route::get('/shipments/{id}', [ShipmentController::class, 'show']);
        // Route::put('/shipments/{id}', [ShipmentController::class, 'update']);
        Route::delete('/shipments/delete/{id}', [ShipmentController::class, 'destroy']);

        Route::prefix('drivers')->group(function () {
            Route::resource('driver', DriverController::class);
            Route::get('truckdrivers', [DriverController::class, 'getTruckDrivers']);
            Route::get('bikedrivers', [DriverController::class, 'getBikeDrivers']);
        });


        Route::prefix('carriers')->group(function () {
            Route::post('/create', [CarrierController::class, 'store'])->name('carriers.store');
            Route::get('/carriers', [CarrierController::class, 'index'])->name('carriers.index');
            Route::get('/carrier/{id}', [CarrierController::class, 'show'])->name('carriers.show');
            Route::put('/update/{id}', [CarrierController::class, 'update'])->name('carriers.update');
            Route::delete('/delete/{id}', [CarrierController::class, 'destroy'])->name('carriers.destroy');	
        })->middleware('role:businessadministrator');

        Route::prefix('trucks')->group(function () {
            Route::post('create', [TruckController::class, 'store'])->name('trucks.store');
            Route::put('update/{id}', [TruckController::class, 'update'])->name('trucks.update');
            Route::get('truck/{id}', [TruckController::class, 'show'])->name('trucks.show');
            Route::get('trucks', [TruckController::class, 'index'])->name('trucks.index');
            Route::delete('delete/{id}', [TruckController::class, 'destroy'])->name('trucks.destroy');
        })->middleware('role:businessadministrator');
        Route::prefix('invoices')->group(function () {
            Route::post('create', [InvoiceController::class, 'store'])->name('invoices.store');
            Route::put('update/{id}', [InvoiceController::class, 'update'])->name('invoices.update');
            Route::get('invoices', [InvoiceController::class, 'showAll'])->name('invoices.showAll');
            Route::get('invoice/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::get('invoices/search', [InvoiceController::class, 'search'])->name('invoices.search');
            Route::get('customer', [InvoiceController::class, 'getCustomer'])->name('invoices.customer');
            Route::put('updatestatus/{id}', [InvoiceController::class, 'updateStatus'])->name('invoices.updateStatus');
            Route::delete('delete/{id}', [InvoiceController::class, 'destroy'])->name('invoices.destroy')->middleware('role:businessadministrator');
        });

        Route::prefix('customers')->group(function () {
            Route::post('create', [CustomerController::class, 'store'])->name('customers.store');
            Route::put('update/{id}', [CustomerController::class, 'update'])->name('customers.update');
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('customer/{id}', [CustomerController::class, 'show'])->name('customers.show');
            Route::delete('delete/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy')->middleware('role:businessadministrator');
        });

        Route::prefix('bikes')->group(function () {
            Route::post('create', [BikeController::class, 'store'])->name('bikes.store');
            Route::put('update/{id}', [BikeController::class, 'update'])->name('bikes.update');
            Route::get('bikes', [BikeController::class, 'index'])->name('bikes.index');
            Route::get('bike/{id}', [BikeController::class, 'show'])->name('bikes.show');
            Route::post('update-location/{id}', [BikeController::class, 'updateLocation'])->name('bikes.updateLocation');
            Route::delete('delete/{id}', [BikeController::class, 'destroy'])->name('bikes.destroy');
        })->middleware('role:businessadministrator');

        Route::prefix('roles')->group(function () {
            Route::get('role', [UnivController::class, 'getUserRole'])->name('roles.index');
        });


        Route::prefix('users')->group(function () {
            Route::get('members', [UnivController::class, 'getUsers'])->name('users.index');
            Route::get('user/{id}', [UnivController::class, 'getUser'])->name('users.show');
            Route::put('update/{id}', [UnivController::class, 'updateUser'])->name('users.update');
            Route::delete('delete/{id}', [UnivController::class, 'destroyUser'])->name('users.destroy');
        })->middleware('role:businessadministrator');

        
        Route::prefix('consolidate')->group(function () {
            Route::post('/create', [ConsolidateShipmentController::class, 'store'])->name('console.shipments');
           Route::put('/update/{id}', [ConsolidateShipmentController::class, 'update'])->name('console.shipments.update');
            Route::get('/shipments', [ConsolidateShipmentController::class, 'index'])->name('console.shipments.index');
            Route::get('/shipment/{id}', [ConsolidateShipmentController::class, 'show'])->name('console.shipments.show');
            Route::get('payments', [ConsolidateShipmentController::class, 'getPayments'])->name('console.shipments.payments');
            Route::get('show-payment/{id}', [ConsolidateShipmentController::class, 'showPayment'])->name('console.shipments.show.payment');
            Route::put('accept/{id}', [ConsolidateShipmentController::class,'acceptConsolidatedDelivery'])->name('console.accept');
            Route::get('get-accepted-consolidated', [ConsolidateShipmentController::class, 'getAcceptedConslidatedDelivery'])->name('console.my.consolidate');
            Route::get('get-pending', [ConsolidateShipmentController::class, 'getPendingConslidatedDelivery']);

            Route::delete('/delete/{id}', [ConsolidateShipmentController::class, 'destroy'])->name('console.shipments.destroy');
            

        });

        Route::prefix('delivery')->group(function () {
            Route::post('/create', [DeliveryController::class, 'makeRequest'])->name('delivery.makeRequest');
            Route::get('/my-deliveries', [DeliveryController::class, 'getMyDeliveries']);
            Route::get('/driver-shipments/{driver}', [DeliveryController::class, 'getShipments']);
            Route::post('/update-shipment-status/{shipment}', [DeliveryController::class, 'updateStatus']);
        });

        Route::prefix('settlements')->group(function () {
            Route::get('/settlements', [SettlementController::class, 'index']);
            Route::get('/settlement/{id}', [SettlementController::class, 'show']);
            Route::post('/settlements', [SettlementController::class, 'store']);
            Route::put('/settlements/{id}', [SettlementController::class, 'update']);
            Route::delete('/settlements/{id}', [SettlementController::class, 'destroy']);
        });
        
        Route::prefix('notification')->group(function () {
            Route::get('/notifications', [NotificationController::class, 'index']);
            Route::get('/notifications/{id}', [NotificationController::class, 'show']);
            Route::post('/notifications/read/{id}', [NotificationController::class, 'viewNotification']);
        });

        Route::prefix('payments')->group(function () {
            Route::post('/initialize', [TransactionsController::class, 'initialize']);
            //Route::post('/initialize-paystack', [TransactionsController::class, 'initiatePaystackPayment']);
            // Route::get('/verify-paystack', [TransactionsController::class, 'verifyPaysatckPayment'])->withoutMiddleware(['auth:api']);
            Route::post('/initialize-flutterwave', [TransactionsController::class, 'initializePaymentFlutterwave']);
            Route::get('/callback-flutterwave', [TransactionsController::class, 'callbackFlutterwave']);
        });

        Route::prefix('plans')->group(function () {
            Route::get('/plans', [PlansController::class, 'index']);
            Route::get('/plan/{id}', [PlansController::class, 'show']);
            Route::post('/create-plan', [PlansController::class, 'store']);
        });
    });
});
