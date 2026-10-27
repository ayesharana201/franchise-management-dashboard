<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoyaltyController;
use App\Http\Controllers\SupplyOrderController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // 1. Multi-location performance dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Locations: everyone can view what they're scoped to; only the franchisor changes them.
    Route::middleware('role:franchisor')->group(function () {
        Route::get('/locations/create', [LocationController::class, 'create'])->name('locations.create');
        Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
        Route::get('/locations/{location}/edit', [LocationController::class, 'edit'])->name('locations.edit');
        Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');
    });
    Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
    Route::get('/locations/{location}', [LocationController::class, 'show'])->name('locations.show');

    // 2. Royalty & fee calculation
    Route::get('/royalties', [RoyaltyController::class, 'index'])->name('royalties.index');
    Route::post('/royalties/sales', [RoyaltyController::class, 'storeSales'])->name('royalties.sales');
    Route::post('/royalties/preview', [RoyaltyController::class, 'preview'])->name('royalties.preview');
    Route::post('/royalties/{statement}/paid', [RoyaltyController::class, 'markPaid'])
        ->middleware('role:franchisor,regional_manager')->name('royalties.paid');

    // 3. Compliance checklist tracking
    Route::get('/compliance', [ComplianceController::class, 'index'])->name('compliance.index');
    Route::put('/compliance/checks/{check}', [ComplianceController::class, 'update'])->name('compliance.update');
    Route::middleware('role:franchisor,regional_manager')->group(function () {
        Route::post('/compliance/items', [ComplianceController::class, 'storeItem'])->name('compliance.items.store');
        Route::post('/compliance/assign', [ComplianceController::class, 'assign'])->name('compliance.assign');
    });

    // 4. Franchisee communication portal
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{message}/reply', [MessageController::class, 'reply'])->name('messages.reply');

    // 5. Inventory & supply ordering
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::middleware('role:franchisor')->group(function () {
        Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
        Route::put('/inventory/{item}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{item}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    });
    Route::get('/orders', [SupplyOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [SupplyOrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [SupplyOrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [SupplyOrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{order}/status', [SupplyOrderController::class, 'updateStatus'])
        ->middleware('role:franchisor,regional_manager')->name('orders.status');

    // 6. Document & contract management
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // 7. Financial reporting per location
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{location}/export', [ReportController::class, 'export'])->name('reports.export');

    // 8. Role-based access: user administration (franchisor only)
    Route::middleware('role:franchisor')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });
});
