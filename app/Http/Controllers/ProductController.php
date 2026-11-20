<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * El listado se filtra en el servidor (sobre todos los productos, no solo
     * la página actual) por:
     * - "search": compara sin distinguir mayúsculas ("ilike" de PostgreSQL)
     *   contra el nombre del producto y el nombre/símbolo de su unidad base.
     * - "estado": "active" (por defecto) o "inactive".
     *
     * Los contadores de activos/inactivos respetan la búsqueda.
     */
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 15);

        if (! in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $search = $request->query('search');
        $search = is_string($search) ? trim($search) : '';

        $estado = $request->query('estado') === 'inactive' ? 'inactive' : 'active';

        $activeCount = $this->searchedProducts($search)->where('active', true)->count();
        $inactiveCount = $this->searchedProducts($search)->where('active', false)->count();

        // Distingue "no hay productos registrados" de "ninguno coincide con los filtros".
        $hasProducts = Product::query()->exists();

        $products = $this->searchedProducts($search)
            ->where('active', $estado === 'active')
            ->with(['baseUnit', 'presentations.unit'])
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('product.index', compact(
            'products',
            'search',
            'estado',
            'activeCount',
            'inactiveCount',
            'hasProducts',
        ));
    }

    /**
     * Consulta base de productos filtrada por el término de búsqueda.
     *
     * Usa "ilike" (PostgreSQL) para ignorar mayúsculas/minúsculas.
     */
    private function searchedProducts(string $search): Builder
    {
        return Product::query()->when($search !== '', function (Builder $query) use ($search) {
            $like = '%'.$search.'%';

            $query->where(function (Builder $query) use ($like) {
                $query->where('name', 'ilike', $like)
                    ->orWhereHas('baseUnit', function (Builder $unit) use ($like) {
                        $unit->where('name', 'ilike', $like)
                            ->orWhere('symbol', 'ilike', $like);
                    });
            });
        });
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $units = Unit::where('active', true)->orderBy('name')->get();

        return view('product.create', compact('units'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->normalizeName($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:30'],
            'base_unit_id' => ['required', 'integer', 'exists:units,id'],
            'single_batch' => ['boolean'],
            'active' => ['boolean'],
        ]);

        Product::create($validated);

        return redirect()
            ->route('products.index')
            ->with('status', 'Producto creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): View
    {
        $product->load(['baseUnit', 'presentations.unit']);

        return view('product.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): View
    {
        $units = Unit::where('active', true)->orderBy('name')->get();

        return view('product.edit', compact('product', 'units'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->normalizeName($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:30'],
            'base_unit_id' => ['required', 'integer', 'exists:units,id'],
            'single_batch' => ['boolean'],
            'active' => ['boolean'],
        ]);

        $product->update($validated);

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'Producto actualizado correctamente.');
    }

    /**
     * Fija el stock del producto a partir de la presentación de compra activa.
     *
     * El stock se guarda en unidad base: paquetes * factor de conversión de la
     * presentación de compra + unidades sueltas.
     */
    public function updateStock(Request $request, Product $product): RedirectResponse
    {
        $purchasePresentation = $product->presentations()
            ->where('purchase_enable', true)
            ->where('active', true)
            ->first();

        if ($purchasePresentation === null) {
            return redirect()
                ->route('products.show', $product)
                ->withErrors(['stock' => 'El producto no tiene una presentación de compra activa.']);
        }

        $validated = $request->validate([
            'stock_packages' => ['nullable', 'integer', 'min:0'],
            'stock_loose' => ['nullable', 'integer', 'min:0'],
            'expected_stock' => ['required', 'integer', 'min:0'],
        ]);

        $conversionFactor = max(1, (int) $purchasePresentation->conversion_factor);
        $packages = (int) ($validated['stock_packages'] ?? 0);
        $loose = (int) ($validated['stock_loose'] ?? 0);

        // Bloqueo optimista: el ajuste solo se aplica si el stock sigue siendo el
        // que el encargado vio al abrir el formulario. Si una venta, entrega o
        // compra lo modificó mientras tanto, el UPDATE no afecta filas y se avisa,
        // en lugar de sobrescribir (y perder) ese movimiento.
        $affected = Product::query()
            ->whereKey($product->id)
            ->where('stock', (int) $validated['expected_stock'])
            ->update(['stock' => $packages * $conversionFactor + $loose]);

        if ($affected === 0) {
            $currentStock = (int) Product::query()->whereKey($product->id)->value('stock');
            $currentLabel = $this->stockLabel($currentStock, $purchasePresentation, $product);

            // Sin withInput(): el formulario debe volver a mostrar el stock real
            // actual, no los valores enviados, para que el encargado revise sobre
            // datos frescos y no reenvíe sin querer el valor desactualizado.
            return redirect()
                ->route('products.show', $product)
                ->withErrors([
                    'stock' => "El stock cambió mientras editabas (ahora hay {$currentLabel}). Revisa y vuelve a guardar.",
                ]);
        }

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'Stock actualizado correctamente.');
    }

    /**
     * Deactivate the specified resource (logical delete).
     *
     * No se realiza borrado físico: solo se cambia "active" a false.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->update(['active' => false]);

        return redirect()
            ->route('products.index')
            ->with('status', 'Producto dado de baja correctamente.');
    }

    /**
     * Reactivate the specified resource (logical restore).
     *
     * Vuelve a poner "active" a true.
     */
    public function restore(Product $product): RedirectResponse
    {
        $product->update(['active' => true]);

        return redirect()
            ->route('products.index')
            ->with('status', 'Producto reactivado correctamente.');
    }

    /**
     * Normaliza el nombre antes de validar: sin espacios en los extremos, primera
     * letra en mayúscula y el resto en minúscula ("  ARROZ " -> "Arroz").
     *
     * Se hace antes de la validación para que se valide el valor que realmente
     * se guardará (mismo criterio que en UnitController).
     */
    private function normalizeName(Request $request): void
    {
        $name = $request->input('name');

        if (! is_string($name)) {
            return;
        }

        $request->merge(['name' => Str::ucfirst(Str::lower(trim($name)))]);
    }

    /**
     * Stock legible: paquetes de la presentación de compra + unidades sueltas
     * ("2 Cajas y 3 Unidades"), con el mismo formato que la ficha del producto
     * (product/show.blade.php). Si la presentación de compra es de 1 unidad base,
     * solo se muestran unidades sueltas.
     */
    private function stockLabel(int $stock, ProductPresentation $purchasePresentation, Product $product): string
    {
        $stock = max(0, $stock);
        $factor = (int) $purchasePresentation->conversion_factor;
        $packageUnit = $purchasePresentation->loadMissing('unit')->unit?->name;
        $baseUnit = $product->loadMissing('baseUnit')->baseUnit?->name;

        // Pluralización simple en español: vocal final -> +s, consonante -> +es.
        $pluralize = fn (string $name, int $count): string => $count === 1
            ? $name
            : (preg_match('/[aeiou]$/i', $name) ? $name.'s' : $name.'es');

        if ($baseUnit === null) {
            return (string) $stock;
        }

        if ($factor <= 1 || $packageUnit === null) {
            return $stock.' '.$pluralize($baseUnit, $stock);
        }

        $packages = intdiv($stock, $factor);
        $loose = $stock % $factor;
        $parts = [];

        if ($packages > 0) {
            $parts[] = $packages.' '.$pluralize($packageUnit, $packages);
        }

        if ($loose > 0) {
            $parts[] = $loose.' '.$pluralize($baseUnit, $loose);
        }

        return $parts !== [] ? implode(' y ', $parts) : '0 '.$pluralize($baseUnit, 0);
    }
}
