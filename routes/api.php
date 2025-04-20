<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\BikeController;
use App\Http\Controllers\Api\V1\UnivController;
use App\Http\Controllers\Api\V1\PlansController;
use App\Http\Controllers\Api\V1\TruckController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\CarrierController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\ShipmentController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\TransactionsController;
use App\Http\Controllers\Api\V1\ConsolidateShipmentController;
use App\Http\Controllers\Api\V1\ConsolidatedShipmentController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/send-reset-link', [AuthController::class, 'sendResetLink']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::post('/register', [AuthController::class, 'register']);
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);

        Route::prefix('dashboard')->group(function () {
            Route::get('/branches', [DashboardController::class, 'countBranches'])->name('dashboard.countBranches');
            Route::get('/monthly-income', [DashboardController::class, 'monthlyIncome'])->name('dashboard.monthlyIncome');
            Route::get('/dashboardstats', [DashboardController::class, 'dashboardStats'])->name('dashboard.dashboardStats');

        });

        Route::prefix('settings')->group(function () {
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
        Route::post('/shipments/{id}', [ShipmentController::class, 'updateShipment'])->name('shipments.update');
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
        // Route::delete('/shipments/{id}', [ShipmentController::class, 'destroy']);

        Route::prefix('drivers')->group(function () {
            Route::resource('driver', DriverController::class);
        });


        Route::prefix('carriers')->group(function () {
            Route::post('/create', [CarrierController::class, 'store'])->name('carriers.store');
            Route::get('/carriers', [CarrierController::class, 'index'])->name('carriers.index');
            Route::get('/carrier/{id}', [CarrierController::class, 'show'])->name('carriers.show');
            Route::put('/update/{id}', [CarrierController::class, 'update'])->name('carriers.update');
            Route::delete('/delete/{id}', [CarrierController::class, 'destroy'])->name('carriers.destroy');	
        });

        Route::prefix('trucks')->group(function () {
            Route::post('create', [TruckController::class, 'store'])->name('trucks.store');
            Route::put('update/{id}', [TruckController::class, 'update'])->name('trucks.update');
            Route::get('truck/{id}', [TruckController::class, 'show'])->name('trucks.show');
            Route::get('trucks', [TruckController::class, 'index'])->name('trucks.index');
        });

        Route::prefix('invoices')->group(function () {
            Route::post('create', [InvoiceController::class, 'store'])->name('invoices.store');
            Route::put('update/{id}', [InvoiceController::class, 'update'])->name('invoices.update');
            Route::get('invoices', [InvoiceController::class, 'showAll'])->name('invoices.showAll');
            Route::get('invoice/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::get('invoices/search', [InvoiceController::class, 'search'])->name('invoices.search');
            Route::get('customer', [InvoiceController::class, 'getCustomer'])->name('invoices.customer');
            Route::put('updatestatus/{id}', [InvoiceController::class, 'updateStatus'])->name('invoices.updateStatus');
        });

        Route::prefix('bikes')->group(function () {
            Route::post('create', [BikeController::class, 'store'])->name('bikes.store');
            Route::put('update/{id}', [BikeController::class, 'update'])->name('bikes.update');
            Route::get('bikes', [BikeController::class, 'index'])->name('bikes.index');
            Route::get('bike/{id}', [BikeController::class, 'show'])->name('bikes.show');
            Route::post('update-location/{id}', [BikeController::class, 'updateLocation'])->name('bikes.updateLocation');
            Route::delete('delete/{id}', [BikeController::class, 'destroy'])->name('bikes.destroy');
        });

        Route::prefix('roles')->group(function () {
            Route::get('role', [UnivController::class, 'getUserRole'])->name('roles.index');
        });


        Route::prefix('users')->group(function () {
            Route::get('members', [UnivController::class, 'getUsers'])->name('users.index');
            Route::get('user/{id}', [UnivController::class, 'getUser'])->name('users.show');
            Route::put('update/{id}', [UnivController::class, 'updateUser'])->name('users.update');
        });

        
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
            
            Route::get('/driver-shipments/{driver}', [DeliveryController::class, 'getShipments']);
            Route::post('/update-shipment-status/{shipment}', [DeliveryController::class, 'updateStatus']);
        });
        
        Route::prefix('notification')->group(function () {
            Route::get('/notifications', [NotificationController::class, 'index']);
            Route::get('/notifications/{id}', [NotificationController::class, 'show']);
            Route::post('/notifications/read/{id}', [NotificationController::class, 'viewNotification']);
        });

        Route::prefix('payments')->group(function () {
            Route::post('/initialize', [TransactionsController::class, 'initialize']);
            //Route::post('/initialize-paystack', [TransactionsController::class, 'initiatePaystackPayment']);
            Route::get('/verify-paystack/{trxref}/{reference}', [TransactionsController::class, 'verifyPaysatckPayment'])->name('verify.paystack');
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
