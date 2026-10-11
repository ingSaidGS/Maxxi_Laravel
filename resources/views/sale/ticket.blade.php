@php
    // Misma lógica de cantidades que sale/show.blade.php: se expresa la cantidad de
    // cada línea en la unidad de su presentación, normalizada por el menor factor de
    // conversión activo del grupo (producto + unidad). Es solo visual.
    $pluralize = fn (string $name, int|float $count): string => (float) $count == 1.0
        ? $name
        : (preg_match('/[aeiou]$/i', $name) ? $name.'s' : $name.'es');

    $refFactorByGroup = $sale->saleDetails
        ->map(fn ($detail) => $detail->presentation?->product)
        ->filter()
        ->unique('id')
        ->flatMap(fn ($product) => $product->presentations)
        ->filter(fn ($presentation) => $presentation->active)
        ->groupBy(fn ($presentation) => $presentation->product_id.'|'.$presentation->unit_id)
        ->map(fn ($presentations) => (int) $presentations->min('conversion_factor'));

    $formatQuantity = fn (int|float $value): string => floor((float) $value) == (float) $value
        ? (string) (int) $value
        : number_format((float) $value, 1, '.', '');

    $quantityLabel = function ($detail) use ($pluralize, $formatQuantity, $refFactorByGroup): string {
        $presentation = $detail->presentation;
        $unit = $presentation?->unit;

        $ref = 0;

        if ($presentation && $unit) {
            $key = ($presentation->product?->id ?? 'sin-producto').'|'.$presentation->unit_id;
            $ref = (int) ($refFactorByGroup[$key] ?? $presentation->conversion_factor);
        }

        if ($ref <= 0) {
            $ref = (int) ($presentation?->conversion_factor ?: 1);
        }

        $value = (int) $detail->base_quantity / $ref;
        $number = $formatQuantity($value);

        if ($unit?->name) {
            return $number.' '.$pluralize($unit->name, $value);
        }

        return $unit?->symbol ? $number.' '.$unit->symbol : $number;
    };

    $money = fn ($value): string => number_format((float) $value, 1, '.', '');
    $subtotals = (float) $sale->saleDetails->sum('subtotal');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket venta #{{ $sale->id }}</title>
    <style>
        /* Impresora térmica: 80mm por defecto. Para rollo de 58mm usa:  @page { size: 58mm auto; margin: 1mm; } */
        @page { size: 80mm auto; margin: 2mm; }

        * { box-sizing: border-box; }

        html, body { margin: 0; padding: 0; }

        body {
            width: 100%;
            max-width: 76mm;
            margin: 0 auto;
            padding: 4px 0;
            font-family: "Courier New", Courier, monospace;
            font-size: 11px;
            line-height: 1.35;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .muted { color: #333; }

        .store { font-size: 16px; font-weight: 700; letter-spacing: .5px; }
        .doc { font-size: 12px; font-weight: 700; }

        hr { border: 0; border-top: 1px dashed #000; margin: 5px 0; }

        .row { display: flex; justify-content: space-between; gap: 8px; }
        .row > span:last-child { text-align: right; white-space: nowrap; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 2px 0; vertical-align: top; }
        th { border-bottom: 1px solid #000; font-size: 10px; text-align: left; }
        th.right, td.right { text-align: right; }
        td.qty { width: 22%; }
        td.prod { width: 36%; word-break: break-word; }
        td.price { width: 20%; white-space: nowrap; }
        td.sub { width: 22%; white-space: nowrap; }

        .total-row { font-size: 14px; font-weight: 700; }

        .footer { margin-top: 8px; text-align: center; font-size: 9px; line-height: 1.5; }

        .no-print { margin: 8px auto; text-align: center; }
        .no-print button {
            font: inherit;
            padding: 8px 14px;
            margin: 0 4px;
            cursor: pointer;
            border: 1px solid #000;
            border-radius: 4px;
            background: #fff;
        }

        @media print {
            .no-print { display: none !important; }
            body { max-width: none; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()">Imprimir</button>
        <button type="button" onclick="window.close()">Cerrar</button>
    </div>

    <div class="center">
        <div class="store">Maxxi</div>
        <div class="muted">Sistema de Ventas</div>
    </div>

    <hr>

    <div class="row">
        <span class="doc">TICKET DE VENTA</span>
        <span class="doc">#{{ $sale->id }}</span>
    </div>
    <div class="row"><span class="muted">Fecha</span><span>{{ $sale->sold_at?->format('d/m/Y H:i') }}</span></div>
    <div class="row"><span class="muted">Estado</span><span>{{ $sale->status === 'pagada' ? 'PAGADA' : 'FIADA' }}</span></div>
    <div class="row"><span class="muted">Cliente</span><span>{{ $sale->customer_name ?: '—' }}</span></div>
    @if ($sale->customer_phone)
        <div class="row"><span class="muted">Teléfono</span><span>{{ $sale->customer_phone }}</span></div>
    @endif
    <div class="row"><span class="muted">Cajero</span><span>{{ $sale->user?->name ?? '—' }}</span></div>

    <hr>

    <table>
        <thead>
            <tr>
                <th class="qty">Cant.</th>
                <th class="prod">Producto</th>
                <th class="right price">P/U</th>
                <th class="right sub">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sale->saleDetails as $detail)
                <tr>
                    <td class="qty">{{ $quantityLabel($detail) }}</td>
                    <td class="prod">{{ $detail->presentation?->product?->name ?? '—' }}</td>
                    <td class="right price">{{ $money($detail->unit_price) }}</td>
                    <td class="right sub">{{ $money($detail->subtotal) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="center muted" colspan="4">Sin detalle</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <hr>

    <div class="row"><span class="muted">Subtotal</span><span>{{ $money($subtotals) }}</span></div>
    @if ((float) $sale->sale_discount > 0)
        <div class="row"><span class="muted">Descuento</span><span>- {{ $money($sale->sale_discount) }}</span></div>
    @endif
    <div class="row total-row"><span>TOTAL Bs.</span><span>{{ $money($sale->total) }}</span></div>
    <div class="row"><span class="muted">Efectivo</span><span>{{ $money($sale->cash) }}</span></div>
    <div class="row"><span class="muted">QR</span><span>{{ $money($sale->qr) }}</span></div>
    @if ((float) $sale->debt > 0)
        <div class="row bold"><span>FIADO</span><span>{{ $money($sale->debt) }}</span></div>
    @endif

    <hr>

    <div class="footer">
        ¡Gracias por su compra!<br>
        Sistema Desarrollado por Aura Tech<br>
        Contacto: 68749269
    </div>

    <script>
        // Abre el diálogo de impresión al cargar (el botón manual queda como respaldo
        // por si el navegador bloquea la impresión automática).
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script>
</body>
</html>
