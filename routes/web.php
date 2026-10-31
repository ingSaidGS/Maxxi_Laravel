<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductPresentationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleNoteController;
use App\Http\Controllers\SalePreparationController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Rutas autenticadas (matriz de permisos por rol)
|--------------------------------------------------------------------------
|
| Encargado (tipo = encargado): acceso total.
| Empleado  (tipo = empleado):
|   - Catálogo (unidades, productos, presentaciones): SOLO lectura.
|     No crea, no edita, no ajusta stock, no gestiona presentaciones ni da
|     de baja nada (gate "manage-catalog").
|   - Ventas: crea y ve (index/show/ticket), pero no edita ni cobra fiadas
|     (gate "manage-sales"). Cobrar en el momento sí, vía store.
|   - Notas de venta: SOLO ve la ficha (show), accesible desde la hoja de
|     alistamiento; no crea, edita, entrega ni toca detalles
|     (gate "manage-sale-notes").
|   - Compras: crea y ve (index/create/store/show), no edita
|     (gate "manage-purchases").
|   - Reportes y Usuarios: sin acceso (view-reports / manage-users).
|   - Preparación (picking): acceso libre, sin gate.
|
| Nota de orden: los recursos con acciones restringidas se registran ANTES
| que los de solo lectura del mismo módulo, para que las rutas literales
| ("create") no queden sombreadas por el route-model binding ("{unit}").
|
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Unidades de medida (baja lógica vía "active" + restore). Gestión solo
    // encargado; el empleado puede listar y ver.
    Route::middleware('can:manage-catalog')->group(function () {
        Route::patch('units/{unit}/restore', [UnitController::class, 'restore'])->name('units.restore');
        Route::resource('units', UnitController::class)->except(['index', 'show']);
    });
    Route::resource('units', UnitController::class)->only(['index', 'show']);

    // Productos (baja lógica vía "active" + restore; stock se fija desde
    // products.show). Gestión solo encargado; el empleado puede listar y ver.
    Route::middleware('can:manage-catalog')->group(function () {
        Route::patch('products/{product}/stock', [ProductController::class, 'updateStock'])->name('products.stock.update');
        Route::patch('products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');
        Route::resource('products', ProductController::class)->except(['index', 'show']);
    });
    Route::resource('products', ProductController::class)->only(['index', 'show']);

    // Presentaciones de producto (se listan desde products.show; sin índice
    // propio). El empleado solo ve la ficha (show); crear, editar y dar de
    // baja es del encargado.
    Route::middleware('can:manage-catalog')->group(function () {
        Route::patch('product-presentations/{product_presentation}/restore', [ProductPresentationController::class, 'restore'])->name('product-presentations.restore');
        Route::resource('product-presentations', ProductPresentationController::class)->except(['index', 'show']);
    });
    Route::resource('product-presentations', ProductPresentationController::class)->only(['show']);

    // Ventas (sin borrado físico ni lógico). El detalle se gestiona dentro de
    // la venta (carrito). El empleado crea y ve; editar y cobrar fiadas es
    // del encargado. Cobrar en el momento se resuelve en store.
    Route::middleware('can:manage-sales')->group(function () {
        Route::patch('sales/{sale}/cobrar', [SaleController::class, 'cobrar'])->name('sales.cobrar');
        Route::resource('sales', SaleController::class)->except(['index', 'create', 'store', 'show']);
    });
    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('sales/{sale}/ticket', [SaleController::class, 'ticket'])->name('sales.ticket');

    // Compras (cabecera + detalle). El detalle se gestiona dentro de la
    // compra (carrito). El empleado crea y ve; editar es del encargado.
    Route::middleware('can:manage-purchases')->group(function () {
        Route::resource('purchases', PurchaseController::class)->except(['index', 'create', 'store', 'show']);
    });
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);

    // Notas de venta (preventa/pedido). Al entregar se genera la venta. El
    // empleado solo ve la ficha (show); crear, editar, entregar y gestionar
    // detalles es del encargado. El detalle se agrega, se quita/reactiva y se
    // edita su cantidad de forma individual (baja lógica vía "active"; solo
    // en notas pendientes).
    Route::middleware('can:manage-sale-notes')->group(function () {
        Route::post('sale-notes/{sale_note}/entregar', [SaleNoteController::class, 'entregar'])->name('sale-notes.entregar');
        Route::post('sale-notes/{sale_note}/details', [SaleNoteController::class, 'storeDetail'])->name('sale-notes.details.store');
        Route::patch('sale-notes/{sale_note}/details/{sale_note_detail}/deactivate', [SaleNoteController::class, 'deactivateDetail'])->name('sale-notes.details.deactivate');
        Route::patch('sale-notes/{sale_note}/details/{sale_note_detail}/restore', [SaleNoteController::class, 'restoreDetail'])->name('sale-notes.details.restore');
        Route::patch('sale-notes/{sale_note}/details/{sale_note_detail}/quantity', [SaleNoteController::class, 'updateDetailQuantity'])->name('sale-notes.details.quantity');
        Route::resource('sale-notes', SaleNoteController::class)->except(['show']);
    });
    Route::resource('sale-notes', SaleNoteController::class)->only(['show']);

    // Preparación de venta (hoja de alistamiento por fecha de entrega; solo
    // lectura). Acceso libre para ambos roles.
    Route::get('sale-preparations', [SalePreparationController::class, 'index'])->name('sale-preparations.index');

    // Alistamiento (picking) de empaques pequeños: el lector de códigos envía el
    // "barcode" y el contador de la línea se ajusta (+1 / -1). El "deshacer" y el
    // botón "completar" (empaques sin código) operan sobre la línea por su id.
    Route::post('sale-preparations/{sale_note}/scan', [SalePreparationController::class, 'scan'])->name('sale-preparations.scan');
    Route::post('sale-preparations/{sale_note}/details/{sale_note_detail}/unscan', [SalePreparationController::class, 'unscan'])->name('sale-preparations.details.unscan');
    Route::post('sale-preparations/{sale_note}/details/{sale_note_detail}/complete', [SalePreparationController::class, 'complete'])->name('sale-preparations.details.complete');
    Route::post('sale-preparations/{sale_note}/details/{sale_note_detail}/reset', [SalePreparationController::class, 'reset'])->name('sale-preparations.details.reset');

    // Reportes (solo lectura, calculados en vivo desde ventas y detalles).
    // Exclusivo del encargado.
    Route::middleware('can:view-reports')->group(function () {
        Route::get('reports/export', [ReportController::class, 'exportExcel'])->name('reports.export');
        Route::get('reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('reports/low-stock/pdf', [ReportController::class, 'exportLowStockPdf'])->name('reports.low-stock.pdf');
        Route::get('reports/low-stock', [ReportController::class, 'lowStock'])->name('reports.low-stock');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    });

    // Usuarios (gestión de credenciales de acceso). La baja es lógica mediante
    // "active": no hay baja física ni "destroy". Exclusivo del encargado.
    Route::middleware('can:manage-users')->group(function () {
        Route::patch('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::resource('users', UserController::class)->except('destroy');
    });
});

require __DIR__.'/auth.php';
