<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\TruckController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\CarrierController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\ShipmentController;
use App\Http\Controllers\Api\V1\ConsolidatedShipmentController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/send-reset-link', [AuthController::class, 'sendResetLink']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);

        // Shipment routes
        Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
        Route::get('/shipments/show/{id}', [ShipmentController::class, 'show'])->name('shipments.show');
        Route::post('/shipments/store', [ShipmentController::class, 'store'])->name('shipments.store');
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
            Route::post('create', [CarrierController::class, 'store'])->name('carriers.store');
            Route::put('update/{id}', [CarrierController::class, 'update'])->name('carriers.update');
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
        });
        
    });
});
