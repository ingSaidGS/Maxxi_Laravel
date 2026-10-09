<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductPresentationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleDetailController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Unidades de medida (baja lógica vía "active" + restore)
    Route::patch('units/{unit}/restore', [UnitController::class, 'restore'])->name('units.restore');
    Route::resource('units', UnitController::class);

    // Productos (baja lógica vía "active" + restore)
    Route::patch('products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
    Route::resource('products', ProductController::class);

    // Presentaciones de producto (baja lógica vía "active" + restore)
    Route::patch('product-presentations/{product_presentation}/restore', [ProductPresentationController::class, 'restore'])->name('product-presentations.restore');
    Route::resource('product-presentations', ProductPresentationController::class);

    // Ventas (sin borrado físico ni lógico)
    Route::resource('sales', SaleController::class);

    // Detalle de ventas (sin borrado físico ni lógico)
    Route::resource('sale-details', SaleDetailController::class);
});

require __DIR__.'/auth.php';
