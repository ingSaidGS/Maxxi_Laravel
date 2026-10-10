<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductPresentationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleController;
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

    // Productos (baja lógica vía "active" + restore; stock se fija desde products.show)
    Route::patch('products/{product}/stock', [ProductController::class, 'updateStock'])->name('products.stock.update');
    Route::patch('products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
    Route::resource('products', ProductController::class);

    // Presentaciones de producto (se listan desde products.show; sin índice propio)
    Route::patch('product-presentations/{product_presentation}/restore', [ProductPresentationController::class, 'restore'])->name('product-presentations.restore');
    Route::resource('product-presentations', ProductPresentationController::class)->except('index');

    // Ventas (sin borrado físico ni lógico). El detalle se gestiona dentro de la venta (carrito).
    Route::resource('sales', SaleController::class);
});

require __DIR__.'/auth.php';
