<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductPresentationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleNoteController;
use App\Http\Controllers\SalePreparationController;
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
    Route::get('sales/{sale}/ticket', [SaleController::class, 'ticket'])->name('sales.ticket');
    Route::patch('sales/{sale}/cobrar', [SaleController::class, 'cobrar'])->name('sales.cobrar');
    Route::resource('sales', SaleController::class);

    // Compras (cabecera + detalle). El detalle se gestiona dentro de la compra (carrito).
    Route::resource('purchases', PurchaseController::class);

    // Notas de venta (preventa/pedido). Al entregar se genera la venta.
    // El detalle se quita/reactiva de forma individual (baja lógica vía "active").
    Route::post('sale-notes/{sale_note}/entregar', [SaleNoteController::class, 'entregar'])->name('sale-notes.entregar');
    Route::patch('sale-notes/{sale_note}/details/{sale_note_detail}/deactivate', [SaleNoteController::class, 'deactivateDetail'])->name('sale-notes.details.deactivate');
    Route::patch('sale-notes/{sale_note}/details/{sale_note_detail}/restore', [SaleNoteController::class, 'restoreDetail'])->name('sale-notes.details.restore');
    Route::resource('sale-notes', SaleNoteController::class);

    // Preparación de venta (hoja de alistamiento por fecha de entrega; solo lectura).
    Route::get('sale-preparations', [SalePreparationController::class, 'index'])->name('sale-preparations.index');
});

require __DIR__.'/auth.php';
