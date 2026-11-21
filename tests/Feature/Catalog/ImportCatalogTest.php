<?php

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Unit;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/*
|--------------------------------------------------------------------------
| Importación del catálogo desde el Excel del sistema anterior
|--------------------------------------------------------------------------
*/

/**
 * Genera un Excel con la estructura del sistema anterior y devuelve su ruta.
 *
 * @param  list<list<mixed>>  $products
 * @param  list<list<mixed>>  $financial
 */
function catalogWorkbook(array $products, array $financial): string
{
    $book = new Spreadsheet;
    $sheets = [
        'unidad_medida' => [
            ['id', 'nombre'],
            ['u-pza', 'Pieza'],
            ['u-cja', 'Caja'],
            ['u-kg', 'Kilo'],
            ['u-qq', 'Quintal'],
            ['u-pzm', 'Pieza x mayor'],
        ],
        'producto' => array_merge([[
            'id', 'nombre', 'id_presentacion_minima', 'codigo_barra', 'id_presentacion_compra',
            'cantidad_contenida_compra', 'precio_presentacion_compra', 'precio_unitario_compra',
            'stock', 'foto', 'col_aux', 'stock_aux', 'stock_aux_2', 'activo',
        ]], $products),
        'detalle_financiero' => array_merge([[
            'id', 'id_unidad', 'precio_venta_unidad', 'id_producto', 'cantidad',
            'margen_ganancia', 'precio_venta_final', 'tipo',
        ]], $financial),
    ];

    $book->removeSheetByIndex(0);

    foreach ($sheets as $title => $rows) {
        $sheet = $book->createSheet();
        $sheet->setTitle($title);
        $sheet->fromArray($rows, null, 'A1', true);
    }

    $path = tempnam(sys_get_temp_dir(), 'catalogo').'.xlsx';
    (new Xlsx($book))->save($path);

    return $path;
}

/**
 * Ejecuta el comando con un reporte temporal.
 *
 * @param  array<string, mixed>  $options
 */
function runCatalogImport(string $file, array $options = []): void
{
    test()->artisan('maxxi:import-catalog', ['file' => $file, '--report' => tempnam(sys_get_temp_dir(), 'reporte')] + $options)
        ->assertSuccessful();
}

test('crea unidades, productos y presentaciones respetando precios y compra', function () {
    $file = catalogWorkbook(
        [['p1', 'RAPIGEL', 'u-pza', '7702027416330', 'u-cja', 24, 238, 9.92, 217, null, null, 9, 1, true]],
        [
            ['f1', 'u-cja', 10.42, 'p1', 24, 12, 250, null],
            ['f2', 'u-pza', 14, 'p1', 1, 4, 14, null],
            ['f3', 'u-pzm', 12, 'p1', 1, 2, 12.5, 'GRANDE'],
        ],
    );

    runCatalogImport($file);

    $product = Product::with('presentations.unit')->sole();

    expect($product->name)->toBe('Rapigel')
        ->and($product->stock)->toBe(0)
        ->and((float) $product->base_unit_cost)->toBe(9.92)
        ->and($product->baseUnit->name)->toBe('Pieza')
        ->and($product->presentations)->toHaveCount(3);

    $box = $product->presentations->firstWhere('unit.name', 'Caja');
    expect($box->purchase_enable)->toBeTrue()
        ->and($box->sale_enable)->toBeTrue()
        ->and((float) $box->reference_purchase_cost)->toBe(238.0)
        ->and((float) $box->sale_price)->toBe(250.0)
        ->and($box->presentation_size)->toBe('grande');

    $piece = $product->presentations->firstWhere('unit.name', 'Pieza');
    expect($piece->purchase_enable)->toBeFalse()
        ->and($piece->barcode)->toBe('7702027416330')
        ->and($piece->presentation_size)->toBe('pequeño');

    $wholesale = $product->presentations->firstWhere('unit.name', 'Pieza may.');
    expect((float) $wholesale->sale_price)->toBe(12.5)
        ->and($wholesale->presentation_size)->toBe('grande');

    expect(ProductPresentation::where('purchase_enable', true)->count())->toBe(1);
});

test('crea una presentación solo de compra y calcula el margen faltante', function () {
    $file = catalogWorkbook(
        [['p1', 'Comino', 'u-pza', null, 'u-cja', 90, 32, 0.36, 540, null, null, 6, 0, true]],
        [['f1', 'u-pza', 1, 'p1', 1, null, 1, null]],
    );

    runCatalogImport($file);

    $product = Product::with('presentations')->sole();
    $purchase = $product->presentations->firstWhere('purchase_enable', true);
    $sale = $product->presentations->firstWhere('sale_enable', true);

    expect($product->presentations)->toHaveCount(2)
        ->and($purchase->sale_enable)->toBeFalse()
        ->and($purchase->sale_price)->toBeNull()
        ->and($purchase->conversion_factor)->toBe(90)
        ->and((float) $sale->desired_profit)->toBe(0.6);
});

test('no modifica productos existentes y reutiliza unidades', function () {
    $kg = Unit::create(['name' => 'Kilogramo', 'symbol' => 'kg', 'active' => true]);
    $pieza = Unit::create(['name' => 'Pieza', 'symbol' => 'pza', 'active' => true]);
    Product::create(['name' => 'Arroz machado', 'base_unit_id' => $kg->id, 'stock' => 5, 'base_unit_cost' => 1]);

    $file = catalogWorkbook(
        [
            ['p1', 'arroz MACHADÓ', 'u-kg', null, 'u-qq', 46, 260, 5.65, 400, null, null, 0, 0, true],
            ['p2', 'Azúcar', 'u-kg', null, 'u-qq', 46, 300, 6.52, 92, null, null, 2, 0, true],
            ['p3', 'azucar', 'u-kg', null, 'u-qq', 46, 300, 6.52, 92, null, null, 2, 0, true],
            ['p4', 'Prueba', 'u-pza', null, 'u-pza', 1, 1, 1, 1, null, null, 0, 0, true],
        ],
        [['f1', 'u-kg', 7, 'p2', 1, 1, 7.5, null]],
    );

    runCatalogImport($file, ['--exclude' => ['p4']]);

    expect(Product::count())->toBe(2)
        ->and(Product::where('name', 'Arroz machado')->value('stock'))->toBe(5)
        ->and(Product::where('name', 'Azúcar')->value('base_unit_id'))->toBe($kg->id)
        ->and(Unit::where('name', 'Kilo')->exists())->toBeFalse()
        ->and(Unit::where('name', 'Quintal')->exists())->toBeTrue()
        ->and(Unit::where('name', 'Pieza')->count())->toBe(1)
        ->and($pieza->fresh())->not->toBeNull();
});

test('el dry-run no guarda nada', function () {
    $file = catalogWorkbook(
        [['p1', 'Rapigel', 'u-pza', null, 'u-cja', 24, 238, 9.92, 217, null, null, 9, 1, true]],
        [['f1', 'u-cja', 10.42, 'p1', 24, 12, 250, null]],
    );

    runCatalogImport($file, ['--dry-run' => true]);

    expect(Product::count())->toBe(0)
        ->and(Unit::count())->toBe(0)
        ->and(ProductPresentation::count())->toBe(0);
});
