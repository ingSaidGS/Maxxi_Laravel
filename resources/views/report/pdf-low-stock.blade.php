<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos con stock bajo</title>
    <style>
        /*
         * Estética Maxxi UI adaptada a DomPDF (misma paleta que report/pdf.blade.php):
         * navy #1A2E45, orange #F87613, cream #FFFAE8, slate #485467.
         * DomPDF no soporta flex/grid/border-radius: layout con tablas.
         */
        @page { margin: 12mm 12mm 16mm 12mm; }

        body { font-family: 'DejaVu Sans', Verdana, Arial, sans-serif; font-size: 9pt; color: #1F2937; }

        h1 { font-size: 15pt; font-weight: bold; color: #1A2E45; margin: 0; }
        .subtitle { font-size: 8pt; color: #485467; margin: 2pt 0 0 0; }
        .rule { border-bottom: 2.5pt solid #F87613; margin: 5pt 0 0 0; }

        h2 { font-size: 10pt; font-weight: bold; color: #1A2E45; margin: 12pt 0 0 0; text-transform: uppercase; letter-spacing: 0.3pt; }
        .sec-bar { background-color: #F87613; color: #F87613; padding: 0 4pt 0 0; margin-right: 4pt; }

        table.data { width: 100%; border-collapse: collapse; margin-top: 4pt; }
        table.data th { background-color: #1A2E45; color: #FFFFFF; font-size: 7pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.2pt; text-align: left; border: 1pt solid #1A2E45; padding: 3.5pt 6pt; }
        table.data td { border: 1pt solid #D9DDE3; padding: 3pt 6pt; font-size: 8.5pt; }
        table.data tr.alt td { background-color: #FFFAE8; }
        .num { text-align: right; }
        .m-amber { color: #D97706; font-weight: bold; }
        .m-red { color: #DC2626; font-weight: bold; }
        .muted { color: #6B7280; font-style: italic; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 6.5pt; color: #9CA3AF; border-top: 0.75pt solid #E5E7EB; padding-top: 2pt; }
    </style>
</head>
<body>
    <h1>Productos con stock bajo</h1>
    <p class="subtitle">
        Umbral: stock ≤ {{ $threshold }} {{ $threshold === 1 ? 'caja' : 'cajas' }} (unidad de compra) ·
        Generado el {{ now()->format('d/m/Y H:i') }}
    </p>
    <div class="rule"></div>

    <h2><span class="sec-bar">|</span>Productos por debajo del umbral</h2>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 6%;">#</th>
                <th>Producto</th>
                <th class="num">Stock (unidad de compra)</th>
                <th class="num">Unidad base</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                @php
                    // Stock en unidad de compra: "1 cja 20 ud", "2 cja", "20 ud" o "Sin stock".
                    $stock = max((int) $product->stock, 0);
                    $factor = max((int) $product->conversion_factor, 1);
                    $packs = intdiv($stock, $factor);
                    $loose = $stock % $factor;
                    $parts = [];
                    if ($packs > 0) { $parts[] = $packs.' cja'; }
                    if ($loose > 0) { $parts[] = $loose.' ud'; }
                    $purchaseStock = $stock <= 0 ? 'Sin stock' : ($parts ? implode(' ', $parts) : $stock.' ud');
                @endphp
                <tr @class(['alt' => $loop->even])>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $product->name }}</td>
                    <td class="num {{ $stock > 0 ? 'm-amber' : 'm-red' }}">{{ $purchaseStock }}</td>
                    <td class="num">{{ $stock }} ud</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Sin productos con stock bajo para este umbral.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Maxxi · Productos con stock bajo (umbral {{ $threshold }}) · Página {PAGE_NUM} de {PAGE_COUNT}
    </div>
</body>
</html>
