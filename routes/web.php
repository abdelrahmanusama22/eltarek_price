<?php

use Illuminate\Support\Facades\Route;

// Root redirects to sales portal login or dashboard
Route::redirect('/', '/sales');

// The redirect logic for guests is now handled in bootstrap/app.php

Route::prefix('sales')->name('sales.')->group(function () {
    // Auth Routes
    Route::get('/login', [\App\Http\Controllers\Sales\AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Sales\AuthController::class, 'login'])->name('login.post');
    Route::get('/register', \App\Livewire\Sales\Register::class)->name('register');
    Route::post('/logout', [\App\Http\Controllers\Sales\AuthController::class, 'logout'])->name('logout');

    // Authenticated Portal Routes (Protected by auth:sales)
    Route::middleware(['auth:sales'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Sales\PortalController::class, 'index'])->name('dashboard');
        Route::get('/offers', [\App\Http\Controllers\Sales\PortalController::class, 'offers'])->name('offers');
        
        // Old step-by-step routes (kept for direct links/fallback)
        Route::get('/brand/{brand}', [\App\Http\Controllers\Sales\PortalController::class, 'showBrand'])->name('brand');
        Route::get('/brand/{brand}/model/{model_name}', [\App\Http\Controllers\Sales\PortalController::class, 'showModels'])->name('models');
        Route::get('/price/{car}', \App\Livewire\Sales\CarPriceDetail::class)->name('price');
    });
});
