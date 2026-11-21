<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Unit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * Importa el catálogo (unidades, productos y presentaciones) desde el Excel
 * exportado del sistema anterior (Google Sheets / AppSheet).
 *
 * Hojas que lee:
 *  - unidad_medida:       id, nombre
 *  - producto:            id, nombre, id_presentacion_minima, codigo_barra,
 *                         id_presentacion_compra, cantidad_contenida_compra,
 *                         precio_presentacion_compra, activo
 *  - detalle_financiero:  id_unidad, id_producto, cantidad, margen_ganancia,
 *                         precio_venta_final, tipo
 *
 * Reglas:
 *  - No modifica nada de lo que ya existe en Maxxi: un producto cuyo nombre
 *    (sin distinguir mayúsculas ni tildes) o código de barras ya existe se salta.
 *  - Las unidades existentes se reutilizan por nombre; solo se crean las que faltan.
 *  - Los precios de venta son los del Excel (no se recalculan con la fórmula de
 *    Maxxi; se recalcularán en la próxima compra del producto).
 *  - La presentación de compra es la de venta con la misma unidad y factor; si no
 *    hay ninguna, se crea una presentación solo de compra.
 *  - Todo ocurre en una transacción: con --dry-run se revierte al final.
 */
class ImportCatalog extends Command
{
    protected $signature = 'maxxi:import-catalog
        {file : Ruta del Excel exportado del sistema anterior}
        {--dry-run : Simula la importación completa y la revierte al final}
        {--exclude=* : ID del Excel (hoja producto) que no se importa; se puede repetir}
        {--unit-map=* : Equivalencia de unidad "Nombre en Excel=Nombre en Maxxi"; se puede repetir}
        {--report= : Ruta del CSV de reporte (por defecto, junto al Excel)}';

    protected $description = 'Importa unidades, productos y presentaciones desde el Excel del sistema anterior';

    /**
     * Productos del Excel que nunca se importan (de prueba o ya cargados).
     */
    private const DEFAULT_EXCLUDED = [
        '27972642', // prueb
        '08e86cfe', // prue 2
        'cf80e260', // producto pprueba
        'ca99950e', // Prueba de produciot
        // Ya cargados en producción con otro nombre.
        '89e16d0c', // Moster (en Maxxi: Monster)
        'b824641f', // dulce de leche 200gr (en Maxxi: Dulce de leche 200g)
    ];

    /**
     * Equivalencias por defecto entre unidades del Excel y unidades habituales de
     * Maxxi. Solo se aplican si la unidad de destino ya existe.
     */
    private const DEFAULT_UNIT_ALIASES = [
        'kilo' => 'Kilogramo',
        // Nombres abreviados con que se crearon estas unidades en producción.
        'pieza x mayor' => 'Pza x mayr',
        'libra x mayor' => 'Lbr x mayr',
        'media docena' => 'Med docena',
    ];

    /**
     * Nombres cortos para unidades del Excel que superan los 10 caracteres.
     */
    private const SHORT_UNIT_NAMES = [
        'media docena' => 'Media doc.',
        'pieza x mayor' => 'Pieza may.',
        'libra x mayor' => 'Libra may.',
    ];

    /**
     * Símbolos sugeridos (máx. 3 caracteres) para las unidades que se creen.
     */
    private const UNIT_SYMBOLS = [
        'pieza' => 'pza', 'docena' => 'doc', 'caja' => 'cja', 'paquete' => 'pqt',
        'bolsa' => 'bls', 'arroba' => 'arr', 'quintal' => 'qq', 'libra' => 'lb',
        'kilo' => 'kg', 'kilogramo' => 'kg', 'unidad' => 'und', 'balde' => 'bal',
        'media doc.' => 'mdc', 'jaba' => 'jab', 'lata' => 'lat', 'tira' => 'tir',
        'pack' => 'pck', 'pieza may.' => 'pzm', 'bote' => 'bot', 'libra may.' => 'lbm',
    ];

    /** @var array<string, string> id de unidad del Excel => nombre */
    private array $excelUnits = [];

    /** @var array<string, Unit> clave normalizada => unidad de Maxxi */
    private array $unitsByKey = [];

    /** @var array<string, Unit> id de unidad del Excel => unidad de Maxxi resuelta */
    private array $resolvedUnits = [];

    /** @var array<string, string> clave normalizada del nombre en Excel => nombre en Maxxi */
    private array $unitMap = [];

    /** @var array<string, true> */
    private array $usedSymbols = [];

    /** @var array<string, string> clave normalizada => nombre de producto existente */
    private array $productsByKey = [];

    /** @var array<string, string> clave normalizada => nombre de producto que existía antes de importar */
    private array $preexistingProducts = [];

    /** @var array<string, string> código de barras => nombre de producto existente */
    private array $productsByBarcode = [];

    /** @var array<string, string> código de barras => id del Excel que lo usó en esta importación */
    private array $importedBarcodes = [];

    /** @var list<array{id: string, excel: string, maxxi: string, resultado: string, detalle: string}> */
    private array $report = [];

    /** @var list<string> */
    private array $createdUnits = [];

    public function handle(): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->error("No existe el archivo: {$file}");

            return self::FAILURE;
        }

        if (! $this->parseUnitMap()) {
            return self::FAILURE;
        }

        try {
            [$products, $financialRows] = $this->readWorkbook($file);
        } catch (Throwable $e) {
            $this->error('No se pudo leer el Excel: '.$e->getMessage());

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        DB::beginTransaction();

        try {
            $this->loadExistingCatalog();
            $this->importProducts($products, $financialRows);
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('La importación se revirtió por un error: '.$e->getMessage());

            return self::FAILURE;
        }

        $dryRun ? DB::rollBack() : DB::commit();

        $this->printSummary($dryRun);
        $this->writeReport($file);

        return self::SUCCESS;
    }

    /**
     * Interpreta las opciones --unit-map ("Excel=Maxxi").
     */
    private function parseUnitMap(): bool
    {
        foreach ((array) $this->option('unit-map') as $pair) {
            if (! str_contains($pair, '=')) {
                $this->error("Formato inválido en --unit-map: \"{$pair}\" (usa \"Excel=Maxxi\").");

                return false;
            }

            [$from, $to] = array_map('trim', explode('=', $pair, 2));
            $to = $this->normalizeName($to);

            if ($from === '' || $to === '' || mb_strlen($to) > 10) {
                $this->error("Equivalencia inválida en --unit-map: \"{$pair}\" (el nombre en Maxxi debe tener 1 a 10 caracteres).");

                return false;
            }

            $this->unitMap[$this->key($from)] = $to;
        }

        return true;
    }

    /**
     * Lee las hojas del Excel.
     *
     * @return array{0: list<array<string, mixed>>, 1: array<string, list<array<string, mixed>>>}
     */
    private function readWorkbook(string $file): array
    {
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(['unidad_medida', 'producto', 'detalle_financiero']);
        $book = $reader->load($file);

        foreach (['unidad_medida', 'producto', 'detalle_financiero'] as $sheet) {
            if ($book->getSheetByName($sheet) === null) {
                throw new \RuntimeException("falta la hoja \"{$sheet}\"");
            }
        }

        $rows = function (string $sheet) use ($book): array {
            $data = $book->getSheetByName($sheet)->toArray(null, false, false, false);
            $headers = array_map(fn ($h) => trim((string) $h), array_shift($data) ?? []);
            $out = [];

            foreach ($data as $row) {
                if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                    continue;
                }

                $out[] = array_combine($headers, array_pad($row, count($headers), null));
            }

            return $out;
        };

        foreach ($rows('unidad_medida') as $unit) {
            $this->excelUnits[$this->text($unit['id'])] = trim((string) $unit['nombre']);
        }

        $financialRows = [];

        foreach ($rows('detalle_financiero') as $row) {
            $financialRows[$this->text($row['id_producto'])][] = $row;
        }

        return [$rows('producto'), $financialRows];
    }

    /**
     * Carga unidades, productos y códigos de barras que ya existen en Maxxi.
     */
    private function loadExistingCatalog(): void
    {
        foreach (Unit::all() as $unit) {
            $this->unitsByKey[$this->key($unit->name)] = $unit;
            $this->usedSymbols[Str::lower($unit->symbol)] = true;
        }

        foreach (Product::query()->pluck('name') as $name) {
            $this->productsByKey[$this->key($name)] = $name;
        }

        $this->preexistingProducts = $this->productsByKey;

        ProductPresentation::query()
            ->whereNotNull('barcode')
            ->with('product:id,name')
            ->get(['id', 'product_id', 'barcode'])
            ->each(function (ProductPresentation $p) {
                $this->productsByBarcode[trim($p->barcode)] = $p->product?->name ?? '';
            });
    }

    /**
     * @param  list<array<string, mixed>>  $products
     * @param  array<string, list<array<string, mixed>>>  $financialRows
     */
    private function importProducts(array $products, array $financialRows): void
    {
        $excluded = array_map('strval', array_merge(self::DEFAULT_EXCLUDED, (array) $this->option('exclude')));

        foreach ($products as $row) {
            $id = $this->text($row['id']);
            $excelName = trim((string) $row['nombre']);

            if (in_array($id, $excluded, true)) {
                $this->record($id, $excelName, '', 'excluido', 'Excluido de la importación');

                continue;
            }

            $this->importProduct($id, $excelName, $row, $financialRows[$id] ?? []);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<array<string, mixed>>  $saleRows
     */
    private function importProduct(string $id, string $excelName, array $row, array $saleRows): void
    {
        $notes = [];
        $name = $this->normalizeName($excelName);

        if ($name === '') {
            $this->record($id, $excelName, '', 'error', 'Producto sin nombre');

            return;
        }

        if (mb_strlen($name) > 30) {
            $name = rtrim(mb_substr($name, 0, 30));
            $notes[] = 'Nombre recortado a 30 caracteres';
        }

        $key = $this->key($name);
        $barcode = $this->barcode($row['codigo_barra']);

        if (isset($this->productsByKey[$key])) {
            isset($this->preexistingProducts[$key])
                ? $this->record($id, $excelName, $this->productsByKey[$key], 'existe', 'Ya existe en Maxxi un producto con ese nombre')
                : $this->record($id, $excelName, $this->productsByKey[$key], 'repetido', 'Nombre repetido en el Excel: solo se importa el primero');

            return;
        }

        if ($barcode !== null && isset($this->productsByBarcode[$barcode])) {
            $this->record($id, $excelName, $this->productsByBarcode[$barcode], 'existe', "Ya existe en Maxxi un producto con el código de barras {$barcode}");

            return;
        }

        // --- Datos de compra -------------------------------------------------
        $purchaseFactor = $this->positiveInt($row['cantidad_contenida_compra']);
        $purchaseCost = is_numeric($row['precio_presentacion_compra']) ? round((float) $row['precio_presentacion_compra'], 2) : null;

        if ($purchaseFactor === null || $purchaseCost === null || $purchaseCost < 0) {
            $this->record($id, $excelName, $name, 'error', 'Factor o costo de compra inválido');

            return;
        }

        $baseUnit = $this->unitFor($row['id_presentacion_minima']);
        $purchaseUnit = $this->unitFor($row['id_presentacion_compra']);

        if ($baseUnit === null || $purchaseUnit === null) {
            $this->record($id, $excelName, $name, 'error', 'Unidad mínima o de compra desconocida');

            return;
        }

        $costPerBaseUnit = $purchaseCost / $purchaseFactor;

        // --- Presentaciones de venta ----------------------------------------
        $presentations = [];

        foreach ($saleRows as $sale) {
            $factor = $this->positiveInt($sale['cantidad']);
            $unit = $this->unitFor($sale['id_unidad']);
            $price = is_numeric($sale['precio_venta_final']) ? round((float) $sale['precio_venta_final'], 1) : null;

            if ($factor === null || $unit === null || $price === null || $price < 0) {
                $notes[] = 'Presentación de venta omitida (unidad, cantidad o precio inválido)';

                continue;
            }

            if (is_numeric($sale['margen_ganancia']) && (float) $sale['margen_ganancia'] >= 0) {
                $profit = round((float) $sale['margen_ganancia'], 1);
            } else {
                $profit = max(0, round($price - $costPerBaseUnit * $factor, 1));
                $notes[] = "Margen calculado ({$profit}) en {$unit->name} x{$factor}";
            }

            // Un precio 0 permitiría vender gratis: se importa sin habilitar la venta.
            $sellable = $price > 0;

            if (! $sellable) {
                $notes[] = "Precio 0 en {$unit->name} x{$factor}: se importa con la venta deshabilitada";
            }

            $presentations[] = [
                'unit_id' => $unit->id,
                'conversion_factor' => $factor,
                'sale_enable' => $sellable,
                'sale_price' => $sellable ? $price : null,
                'desired_profit' => $profit,
                'purchase_enable' => false,
                'reference_purchase_cost' => null,
                'presentation_size' => $this->size($sale['tipo'] ?? null, $factor),
            ];
        }

        // --- Presentación de compra (única) ---------------------------------
        $purchaseIndex = null;

        foreach ($presentations as $i => $p) {
            if ($p['unit_id'] === $purchaseUnit->id && $p['conversion_factor'] === $purchaseFactor) {
                $purchaseIndex = $i;

                break;
            }
        }

        if ($purchaseIndex === null) {
            $presentations[] = [
                'unit_id' => $purchaseUnit->id,
                'conversion_factor' => $purchaseFactor,
                'sale_enable' => false,
                'sale_price' => null,
                'desired_profit' => 0,
                'purchase_enable' => false,
                'reference_purchase_cost' => null,
                'presentation_size' => $this->size(null, $purchaseFactor),
            ];
            $purchaseIndex = array_key_last($presentations);

            if ($saleRows === []) {
                $notes[] = 'Sin presentaciones de venta: solo se crea la de compra';
            }
        }

        $presentations[$purchaseIndex]['purchase_enable'] = true;
        $presentations[$purchaseIndex]['reference_purchase_cost'] = $purchaseCost;

        // --- Código de barras -----------------------------------------------
        if ($barcode !== null) {
            $barcodeIndex = $this->barcodeIndex($presentations, $baseUnit->id);
            $presentations[$barcodeIndex]['barcode'] = $barcode;

            if ($presentations[$barcodeIndex]['conversion_factor'] !== 1) {
                $notes[] = 'Código de barras asignado a una presentación de factor '.$presentations[$barcodeIndex]['conversion_factor'];
            }

            if (isset($this->importedBarcodes[$barcode])) {
                $notes[] = "Código de barras repetido en el Excel (también en {$this->importedBarcodes[$barcode]})";
            }

            $this->importedBarcodes[$barcode] = $id;
        }

        if (($similar = $this->similarProduct($key)) !== null) {
            $notes[] = "Parecido a \"{$similar}\" que ya existe en Maxxi: revisar si es el mismo";
        }

        // --- Persistencia ---------------------------------------------------
        $product = Product::create([
            'name' => $name,
            'base_unit_id' => $baseUnit->id,
            'stock' => 0,
            'base_unit_cost' => round($costPerBaseUnit, 2),
            'single_batch' => false,
            'active' => $this->bool($row['activo']),
        ]);

        foreach ($presentations as $p) {
            $product->presentations()->create($p + ['barcode' => null, 'active' => true]);
        }

        $this->productsByKey[$key] = $name;

        $sales = count(array_filter($presentations, fn ($p) => $p['sale_enable']));
        $detail = "{$sales} de venta, compra {$purchaseUnit->name} x{$purchaseFactor}";

        $this->record($id, $excelName, $name, $notes === [] ? 'creado' : 'creado con avisos', $detail.($notes === [] ? '' : ' | '.implode('; ', array_unique($notes))));
    }

    /**
     * Índice de la presentación que recibe el código de barras: la de factor 1 en
     * la unidad mínima; si no hay, cualquiera de factor 1; si no, la de menor factor.
     *
     * @param  list<array<string, mixed>>  $presentations
     */
    private function barcodeIndex(array $presentations, int $baseUnitId): int
    {
        foreach ($presentations as $i => $p) {
            if ($p['conversion_factor'] === 1 && $p['unit_id'] === $baseUnitId) {
                return $i;
            }
        }

        foreach ($presentations as $i => $p) {
            if ($p['conversion_factor'] === 1) {
                return $i;
            }
        }

        $factors = array_column($presentations, 'conversion_factor');

        return array_search(min($factors), $factors, true);
    }

    /**
     * Resuelve (o crea) la unidad de Maxxi para una unidad del Excel.
     */
    private function unitFor(mixed $excelUnitId): ?Unit
    {
        $excelUnitId = $this->text($excelUnitId);

        if (isset($this->resolvedUnits[$excelUnitId])) {
            return $this->resolvedUnits[$excelUnitId];
        }

        $excelName = $this->excelUnits[$excelUnitId] ?? null;

        if ($excelName === null || trim($excelName) === '') {
            return null;
        }

        $excelKey = $this->key($excelName);
        $candidates = array_filter([
            $this->unitMap[$excelKey] ?? null,
            self::DEFAULT_UNIT_ALIASES[$excelKey] ?? null,
            self::SHORT_UNIT_NAMES[$excelKey] ?? null,
            $this->normalizeName($excelName),
        ]);

        foreach ($candidates as $candidate) {
            if (isset($this->unitsByKey[$this->key($candidate)])) {
                return $this->resolvedUnits[$excelUnitId] = $this->unitsByKey[$this->key($candidate)];
            }
        }

        // No existe: se crea con el nombre de la equivalencia, el corto o el propio.
        $name = $this->normalizeName($this->unitMap[$excelKey] ?? self::SHORT_UNIT_NAMES[$excelKey] ?? mb_substr($this->normalizeName($excelName), 0, 10));

        $unit = Unit::create([
            'name' => $name,
            'symbol' => $this->symbolFor($name),
            'active' => true,
        ]);

        $this->unitsByKey[$this->key($name)] = $unit;
        $this->createdUnits[] = "{$excelName} → {$unit->name} ({$unit->symbol})";

        return $this->resolvedUnits[$excelUnitId] = $unit;
    }

    /**
     * Genera un símbolo único de hasta 3 caracteres.
     */
    private function symbolFor(string $name): string
    {
        $base = self::UNIT_SYMBOLS[Str::lower($name)] ?? Str::substr(preg_replace('/[^a-z]/', '', Str::lower(Str::ascii($name))) ?: 'u', 0, 3);
        $symbol = $base;
        $n = 1;

        while (isset($this->usedSymbols[$symbol])) {
            $symbol = Str::substr($base, 0, 2).$n++;
        }

        $this->usedSymbols[$symbol] = true;

        return $symbol;
    }

    /**
     * Producto existente (previo a la importación) con un nombre casi igual.
     */
    private function similarProduct(string $key): ?string
    {
        if (mb_strlen($key) < 5) {
            return null;
        }

        foreach ($this->preexistingProducts as $otherKey => $otherName) {
            if (abs(strlen($otherKey) - strlen($key)) <= 2 && levenshtein($key, $otherKey) <= 2) {
                return $otherName;
            }
        }

        return null;
    }

    private function size(mixed $type, int $factor): string
    {
        $type = $this->key((string) $type);

        if ($type === 'pequeno') {
            return 'pequeño';
        }

        if ($type === 'grande') {
            return 'grande';
        }

        return $factor === 1 ? 'pequeño' : 'grande';
    }

    /**
     * Nombre con la misma normalización que aplican los controllers de Maxxi.
     */
    private function normalizeName(string $name): string
    {
        return Str::ucfirst(Str::lower(trim((string) preg_replace('/\s+/u', ' ', $name))));
    }

    /**
     * Clave de comparación: sin tildes, en minúsculas y con espacios simples.
     */
    private function key(string $value): string
    {
        return Str::lower(trim((string) preg_replace('/\s+/', ' ', Str::ascii($value))));
    }

    private function text(mixed $value): string
    {
        if (is_float($value) && floor($value) === $value) {
            return sprintf('%.0f', $value);
        }

        return trim((string) $value);
    }

    private function barcode(mixed $value): ?string
    {
        $barcode = $this->text($value);

        return $barcode === '' || mb_strlen($barcode) > 20 ? null : $barcode;
    }

    private function positiveInt(mixed $value): ?int
    {
        $int = $this->nonNegativeInt($value);

        return $int !== null && $int > 0 ? $int : null;
    }

    private function nonNegativeInt(mixed $value): ?int
    {
        if (! is_numeric($value) || (float) $value < 0 || floor((float) $value) != (float) $value) {
            return null;
        }

        return (int) $value;
    }

    private function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(Str::lower(trim((string) $value)), ['true', '1', 'si', 'sí', 'verdadero'], true);
    }

    private function record(string $id, string $excel, string $maxxi, string $result, string $detail): void
    {
        $this->report[] = ['id' => $id, 'excel' => $excel, 'maxxi' => $maxxi, 'resultado' => $result, 'detalle' => $detail];
    }

    private function printSummary(bool $dryRun): void
    {
        $this->newLine();
        $this->line($dryRun
            ? '<fg=yellow>SIMULACIÓN (--dry-run): no se guardó nada.</>'
            : '<fg=green>Importación aplicada.</>');

        $counts = array_count_values(array_column($this->report, 'resultado'));
        $this->table(['Resultado', 'Productos'], collect($counts)->map(fn ($n, $r) => [$r, $n])->values()->all());

        if ($this->createdUnits !== []) {
            $this->line('<options=bold>Unidades nuevas:</>');

            foreach ($this->createdUnits as $unit) {
                $this->line("  • {$unit}");
            }
        }

        $attention = array_filter($this->report, fn ($r) => in_array($r['resultado'], ['creado con avisos', 'error', 'existe', 'repetido'], true));

        if ($attention !== [] && ($this->output->isVerbose() || count($attention) <= 60)) {
            $this->table(['ID', 'Excel', 'Maxxi', 'Resultado', 'Detalle'], array_map(fn ($r) => array_values($r), $attention));
        } elseif ($attention !== []) {
            $this->line(count($attention).' productos con avisos, errores o ya existentes: ver el CSV o usar -v.');
        }

        if ($this->output->isVeryVerbose()) {
            $created = array_filter($this->report, fn ($r) => $r['resultado'] === 'creado');
            $this->table(['ID', 'Excel', 'Maxxi', 'Resultado', 'Detalle'], array_map(fn ($r) => array_values($r), $created));
        }
    }

    private function writeReport(string $file): void
    {
        $path = $this->option('report') ?: dirname($file).'/reporte-importacion-'.now()->format('Ymd-His').'.csv';
        $handle = @fopen($path, 'w');

        if ($handle === false) {
            $this->warn("No se pudo escribir el reporte en {$path}");

            return;
        }

        fwrite($handle, "\xEF\xBB\xBF"); // BOM para que Excel lea bien las tildes.
        fputcsv($handle, ['id_excel', 'nombre_excel', 'nombre_maxxi', 'resultado', 'detalle'], escape: '');

        foreach ($this->report as $row) {
            fputcsv($handle, array_values($row), escape: '');
        }

        fclose($handle);
        $this->line("Reporte: {$path}");
    }
}
