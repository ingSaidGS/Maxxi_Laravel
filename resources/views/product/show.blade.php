<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de producto</title>
</head>
<body>
    <h1>Producto #{{ $product->id }}</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    @php
        $purchasePresentation = $product->presentations
            ->first(fn ($presentation) => $presentation->purchase_enable && $presentation->active);
        $purchaseConversionFactor = (int) ($purchasePresentation?->conversion_factor ?? 0);
        $purchaseCost = $purchasePresentation?->reference_purchase_cost;
        $unitPurchasePrice = $purchasePresentation !== null && $purchaseConversionFactor > 0 && $purchaseCost !== null
            ? number_format((float) $purchaseCost / $purchaseConversionFactor, 2, '.', '')
            : null;

        // Pluralización simple en español: vocal final -> +s, consonante -> +es.
        $pluralize = fn (string $name, int $count): string => $count === 1
            ? $name
            : (preg_match('/[aeiou]$/i', $name) ? $name.'s' : $name.'es');

        // Desglose del stock: paquetes de la presentación de compra + unidades sueltas.
        $stock = max(0, (int) $product->stock);
        $stockLabel = (string) $stock;

        if ($purchaseConversionFactor > 1 && $purchasePresentation?->unit !== null && $product->baseUnit !== null) {
            $stockPackages = intdiv($stock, $purchaseConversionFactor);
            $stockLoose = $stock % $purchaseConversionFactor;
            $stockParts = [];

            if ($stockPackages > 0) {
                $stockParts[] = $stockPackages.' '.$pluralize($purchasePresentation->unit->name, $stockPackages);
            }

            if ($stockLoose > 0) {
                $stockParts[] = $stockLoose.' '.$pluralize($product->baseUnit->name, $stockLoose);
            }

            $stockLabel = $stockParts !== []
                ? implode(' y ', $stockParts)
                : '0 '.$pluralize($product->baseUnit->name, 0);
        } elseif ($product->baseUnit !== null) {
            $stockLabel = $stock.' '.$pluralize($product->baseUnit->name, $stock);
        }
    @endphp

    <dl>
        <dt>Nombre</dt>
        <dd>{{ $product->name }}</dd>

        <dt>Unidad base</dt>
        <dd>
            @if ($product->baseUnit)
                {{ $product->baseUnit->name }} ({{ $product->baseUnit->symbol }})
            @else
                —
            @endif
        </dd>

        <dt>Stock</dt>
        <dd>
            {{ $stockLabel }}

            @if ($purchasePresentation)
                <button type="button" id="stock-toggle">Actualizar stock</button>

                <form action="{{ route('products.stock.update', $product) }}" method="POST" id="stock-form" hidden>
                    @csrf
                    @method('PATCH')

                    @if ($purchaseConversionFactor > 1)
                        <label for="stock_packages">{{ $purchasePresentation->unit?->name ?? 'Paquetes' }}</label>
                        <input type="number" id="stock_packages" name="stock_packages" min="0" step="1"
                               value="{{ old('stock_packages', intdiv($stock, $purchaseConversionFactor)) }}">
                    @endif

                    <label for="stock_loose">{{ $product->baseUnit?->name ?? 'Unidades' }}</label>
                    <input type="number" id="stock_loose" name="stock_loose" min="0" step="1"
                           value="{{ old('stock_loose', $purchaseConversionFactor > 1 ? $stock % $purchaseConversionFactor : $stock) }}">

                    <button type="submit">Guardar</button>
                </form>
            @endif
        </dd>

        <dt>Activo</dt>
        <dd>{{ $product->active ? 'Sí' : 'No' }}</dd>

        <dt>Precio de compra unitario</dt>
        <dd>{{ $unitPurchasePrice ?? '—' }}</dd>

        <dt>Presentación de compra</dt>
        <dd>
            @if ($purchasePresentation && $purchasePresentation->unit && $product->baseUnit)
                {{ $purchasePresentation->unit->name }} de {{ $purchasePresentation->conversion_factor }} {{ $product->baseUnit->name }} a {{ $purchasePresentation->reference_purchase_cost }} bs
            @else
                —
            @endif
        </dd>
    </dl>

    <p>
        <a href="{{ route('products.edit', $product) }}">Editar</a>
        <a href="{{ route('products.index') }}">Volver al listado</a>
    </p>

    <h2>Presentaciones</h2>

    <p>
        <a href="{{ route('product-presentations.create', ['product_id' => $product->id]) }}">Nueva presentación</a>
    </p>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Unidad</th>
                <th>Factor</th>
                <th>Precio venta</th>
                <th>Costo ref.</th>
                <th>Ganancia</th>
                <th>Compra</th>
                <th>Venta</th>
                <th>Código de barras</th>
                <th>Activa</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($product->presentations as $presentation)
                <tr>
                    <td>{{ $presentation->id }}</td>
                    <td>
                        @if ($presentation->unit)
                            {{ $presentation->unit->name }} ({{ $presentation->unit->symbol }})
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $presentation->conversion_factor }}</td>
                    <td>{{ $presentation->sale_price ?? '—' }}</td>
                    <td>{{ $presentation->reference_purchase_cost ?? '—' }}</td>
                    <td>{{ $presentation->desired_profit }}</td>
                    <td>{{ $presentation->purchase_enable ? 'Sí' : 'No' }}</td>
                    <td>{{ $presentation->sale_enable ? 'Sí' : 'No' }}</td>
                    <td>{{ $presentation->barcode ?? '—' }}</td>
                    <td>{{ $presentation->active ? 'Sí' : 'No' }}</td>
                    <td>
                        <a href="{{ route('product-presentations.show', $presentation) }}">Ver</a>
                        <a href="{{ route('product-presentations.edit', $presentation) }}">Editar</a>

                        @if ($presentation->active)
                            <form action="{{ route('product-presentations.destroy', $presentation) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Dar de baja</button>
                            </form>
                        @else
                            <form action="{{ route('product-presentations.restore', $presentation) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit">Reactivar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11">Este producto no tiene presentaciones.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        const stockToggle = document.getElementById('stock-toggle');
        const stockForm = document.getElementById('stock-form');

        if (stockToggle && stockForm) {
            stockToggle.addEventListener('click', () => {
                stockForm.hidden = !stockForm.hidden;
            });
        }
    </script>
</body>
</html>
