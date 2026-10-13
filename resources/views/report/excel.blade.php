@php
    // Formato numérico para Excel: sin miles y con punto decimal, para que las
    // celdas se reconozcan como números y se puedan sumar.
    $money = fn (float|int|string|null $value): string => number_format((float) $value, 1, '.', '');
    $qty = fn (float|int|string|null $value): string => (string) (int) $value;
    $percent = fn (float|int|string|null $value): string => number_format((float) $value, 1, '.', '');
    $date = fn (string $value): string => \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
@endphp
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Reporte</title>
    <style>
        table { border-collapse: collapse; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 11pt; }
        td, th { border: 1px solid #b7b7b7; padding: 4px 8px; }
        th { background-color: #1A2E45; color: #ffffff; font-weight: bold; text-align: left; }
        .num { text-align: right; }
        .title { font-size: 15pt; font-weight: bold; color: #1A2E45; border: none; }
        .subtitle { color: #666666; border: none; }
        .section { background-color: #F87613; color: #ffffff; font-size: 12pt; font-weight: bold; }
        .spacer { border: none; }
    </style>
</head>
<body>
    <table>
        <tr><td colspan="6" class="title">Reporte del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</td></tr>
        <tr><td colspan="6" class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</td></tr>
    </table>

    <table>
        <tr><td colspan="2" class="spacer"></td></tr>
        <tr><td colspan="2" class="section">RESUMEN ECONÓMICO</td></tr>
        <tr><th>Concepto</th><th class="num">Valor</th></tr>
        <tr><td>Ventas netas</td><td class="num">{{ $money($economic['net']) }}</td></tr>
        <tr><td>Ventas brutas</td><td class="num">{{ $money($economic['gross']) }}</td></tr>
        <tr><td>Descuentos</td><td class="num">{{ $money($economic['discount']) }}</td></tr>
        <tr><td>Efectivo</td><td class="num">{{ $money($economic['cash']) }}</td></tr>
        <tr><td>QR</td><td class="num">{{ $money($economic['qr']) }}</td></tr>
        <tr><td>Fiado</td><td class="num">{{ $money($economic['debt']) }}</td></tr>
        <tr><td>Inversión (costo)</td><td class="num">{{ $money($economic['cost']) }}</td></tr>
        <tr><td>Ganancia bruta</td><td class="num">{{ $money($economic['profit']) }}</td></tr>
        <tr><td>Margen (%)</td><td class="num">{{ $percent($economic['margin']) }}</td></tr>
        <tr><td>Inversión sobre ventas (%)</td><td class="num">{{ $percent($economic['cost_share']) }}</td></tr>
        <tr><td>N° de ventas</td><td class="num">{{ $qty($economic['sales_count']) }}</td></tr>
        <tr><td>Ticket promedio</td><td class="num">{{ $money($economic['avg_ticket']) }}</td></tr>
    </table>

    <table>
        <tr><td colspan="6" class="spacer"></td></tr>
        <tr><td colspan="6" class="section">VENTAS POR DÍA</td></tr>
        <tr>
            <th>Fecha</th>
            <th class="num">Ventas</th>
            <th class="num">Netas</th>
            <th class="num">Efectivo</th>
            <th class="num">QR</th>
            <th class="num">Fiado</th>
        </tr>
        @forelse ($byDay as $row)
            <tr>
                <td>{{ $date($row->day) }}</td>
                <td class="num">{{ $qty($row->sales_count) }}</td>
                <td class="num">{{ $money($row->net) }}</td>
                <td class="num">{{ $money($row->cash) }}</td>
                <td class="num">{{ $money($row->qr) }}</td>
                <td class="num">{{ $money($row->debt) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Sin ventas en el rango.</td></tr>
        @endforelse
    </table>

    <table>
        <tr><td colspan="5" class="spacer"></td></tr>
        <tr><td colspan="5" class="section">TOP PRODUCTOS VENDIDOS</td></tr>
        <tr>
            <th>#</th>
            <th>Producto</th>
            <th class="num">Cantidad (unidad base)</th>
            <th>Unidad</th>
            <th class="num">Ingresos</th>
        </tr>
        @forelse ($topProducts as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $row->product_name }}</td>
                <td class="num">{{ $qty($row->quantity) }}</td>
                <td>{{ $row->unit_symbol ?? '' }}</td>
                <td class="num">{{ $money($row->revenue) }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Sin productos vendidos en el rango.</td></tr>
        @endforelse
    </table>

    <table>
        <tr><td colspan="6" class="spacer"></td></tr>
        <tr><td colspan="6" class="section">VENTAS POR USUARIO</td></tr>
        <tr>
            <th>Usuario</th>
            <th class="num">Ventas</th>
            <th class="num">Netas</th>
            <th class="num">Efectivo</th>
            <th class="num">QR</th>
            <th class="num">Fiado</th>
        </tr>
        @forelse ($byUser as $row)
            <tr>
                <td>{{ $row->user_name }}</td>
                <td class="num">{{ $qty($row->sales_count) }}</td>
                <td class="num">{{ $money($row->net) }}</td>
                <td class="num">{{ $money($row->cash) }}</td>
                <td class="num">{{ $money($row->qr) }}</td>
                <td class="num">{{ $money($row->debt) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Sin ventas por usuario en el rango.</td></tr>
        @endforelse
    </table>
</body>
</html>
