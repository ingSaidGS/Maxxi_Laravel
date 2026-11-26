<?php

use App\Services\ProductPresentationService;

/*
 * Redondeo especial del precio de venta recalculado
 * (ProductPresentationService::roundSalePrice):
 *  - decimal < 0.1        -> N
 *  - decimal 0.1 a < 0.7  -> N.5
 *  - decimal >= 0.7       -> N+1
 *
 * Se prueba la función directamente (sin base de datos), porque las
 * migraciones de negocio no corren en SQLite.
 */
it('redondea el precio de venta según la parte decimal', function (float $value, float $expected) {
    $round = (new ReflectionMethod(ProductPresentationService::class, 'roundSalePrice'))
        ->getClosure(new ProductPresentationService);

    expect($round($value))->toBe($expected);
})->with([
    'entero exacto' => [12.00, 12.0],
    '.05 baja' => [12.05, 12.0],
    '.09 baja' => [12.09, 12.0],
    '.10 sube a .5' => [12.10, 12.5],
    '.50 se queda en .5' => [12.50, 12.5],
    '.65 baja a .5' => [12.65, 12.5],
    '.69 baja a .5' => [12.69, 12.5],
    '.70 sube al entero' => [12.70, 13.0],
    '.99 sube al entero' => [12.99, 13.0],
    'cálculo con flotantes (24 × 0.7 + 0.1 = 16.9)' => [24 * 0.7 + 0.1, 17.0],
]);
