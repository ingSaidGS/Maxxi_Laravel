<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte</title>
    <style>
        /*
         * Estética Maxxi UI adaptada a DomPDF:
         * - Paleta maxxi (navy #1A2E45, orange #F87613, cream #FFFAE8, slate #485467).
         * - Color por concepto de monto, consistente con la vista web:
         *   Total/Netas naranja, Efectivo verde, QR azul, Fiado ámbar, Ganancia esmeralda.
         * - DomPDF no soporta flex/grid/border-radius: layout con tablas y bordes sólidos.
         */
        @page { margin: 12mm 12mm 16mm 12mm; }

        body { font-family: 'DejaVu Sans', Verdana, Arial, sans-serif; font-size: 9pt; color: #1F2937; }

        /* Encabezado de página */
        h1 { font-size: 15pt; font-weight: bold; color: #1A2E45; margin: 0; }
        .subtitle { font-size: 8pt; color: #485467; margin: 2pt 0 0 0; }
        .rule { border-bottom: 2.5pt solid #F87613; margin: 5pt 0 0 0; }

        /* Secciones (icono-barra naranja + título marino, como en la vista web) */
        h2 { font-size: 10pt; font-weight: bold; color: #1A2E45; margin: 12pt 0 0 0; text-transform: uppercase; letter-spacing: 0.3pt; }
        .sec-bar { background-color: #F87613; color: #F87613; padding: 0 4pt 0 0; margin-right: 4pt; }

        /* Tarjetas KPI (grid simulado con tabla; imita las tarjetas de la vista web) */
        .kpi { width: 100%; border-collapse: separate; margin-top: 5pt; }
        .kpi td { width: 25%; border: 1pt solid #E5E7EB; border-left: 3.5pt solid #9CA3AF; background-color: #FFFFFF; padding: 4pt 6pt; vertical-align: top; }
        .kpi .c-orange { border-left-color: #F87613; }
        .kpi .c-green  { border-left-color: #059669; }
        .kpi .c-blue   { border-left-color: #2563EB; }
        .kpi .c-amber  { border-left-color: #D97706; }
        .kpi .c-emerald{ border-left-color: #047857; }
        .kpi .c-slate  { border-left-color: #485467; }
        .kpi .c-navy   { border-left-color: #1A2E45; }
        .kpi .c-gray   { border-left-color: #9CA3AF; }
        .lbl { font-size: 6.5pt; color: #6B7280; text-transform: uppercase; letter-spacing: 0.2pt; margin: 0; }
        .val { font-size: 11pt; font-weight: bold; color: #1F2937; margin: 1pt 0 0 0; }
        .sub { font-size: 6.5pt; color: #6B7280; margin: 1pt 0 0 0; }
        .v-orange  { color: #D36B15; }
        .v-green   { color: #059669; }
        .v-blue    { color: #2563EB; }
        .v-amber   { color: #D97706; }
        .v-emerald { color: #047857; }
        .v-navy    { color: #1A2E45; }
        .v-slate   { color: #485467; }

        /* Tablas de datos (cabecera marina, filas alternadas crema, montos coloreados) */
        table.data { width: 100%; border-collapse: collapse; margin-top: 4pt; }
        table.data th { background-color: #1A2E45; color: #FFFFFF; font-size: 7pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.2pt; text-align: left; border: 1pt solid #1A2E45; padding: 3.5pt 6pt; }
        table.data td { border: 1pt solid #D9DDE3; padding: 3pt 6pt; font-size: 8.5pt; }
        table.data tr.alt td { background-color: #FFFAE8; }
        .num { text-align: right; }
        .m-orange  { color: #D36B15; font-weight: bold; }
        .m-green   { color: #059669; }
        .m-blue    { color: #2563EB; }
        .m-amber   { color: #D97706; }
        .muted { color: #6B7280; font-style: italic; }

        /* Pie de página con numeración */
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 6.5pt; color: #9CA3AF; border-top: 0.75pt solid #E5E7EB; padding-top: 2pt; }
    </style>
</head>
<body>
    <h1>Reporte del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</h1>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</p>
    <div class="rule"></div>

    <h2><span class="sec-bar">|</span>Resumen económico</h2>
    <table class="kpi">
        <tr>
            <td class="c-orange">
                <p class="lbl">Ventas netas</p>
                <p class="val v-orange">Bs. {{ number_format($economic['net'], 1, '.', ',') }}</p>
                <p class="sub">Bruto Bs. {{ number_format($economic['gross'], 1, '.', ',') }}</p>
            </td>
            <td class="c-green">
                <p class="lbl">Efectivo</p>
                <p class="val v-green">Bs. {{ number_format($economic['cash'], 1, '.', ',') }}</p>
            </td>
            <td class="c-blue">
                <p class="lbl">QR</p>
                <p class="val v-blue">Bs. {{ number_format($economic['qr'], 1, '.', ',') }}</p>
            </td>
            <td class="c-amber">
                <p class="lbl">Fiado</p>
                <p class="val v-amber">Bs. {{ number_format($economic['debt'], 1, '.', ',') }}</p>
            </td>
        </tr>
        <tr>
            <td class="c-emerald">
                <p class="lbl">Ganancia bruta</p>
                <p class="val v-emerald">Bs. {{ number_format($economic['profit'], 1, '.', ',') }}</p>
                <p class="sub">Margen {{ number_format($economic['margin'], 1, '.', ',') }}%</p>
            </td>
            <td class="c-slate">
                <p class="lbl">Inversión</p>
                <p class="val v-slate">Bs. {{ number_format($economic['cost'], 1, '.', ',') }}</p>
                <p class="sub">{{ number_format($economic['cost_share'], 1, '.', ',') }}% de las ventas</p>
            </td>
            <td class="c-navy">
                <p class="lbl">Ventas</p>
                <p class="val v-navy">{{ number_format($economic['sales_count'], 0, '.', ',') }}</p>
                <p class="sub">Ticket prom. Bs. {{ number_format($economic['avg_ticket'], 1, '.', ',') }}</p>
            </td>
            <td class="c-gray">
                <p class="lbl">Descuentos</p>
                <p class="val v-slate">Bs. {{ number_format($economic['discount'], 1, '.', ',') }}</p>
            </td>
        </tr>
    </table>

    <h2><span class="sec-bar">|</span>Ventas por día</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Fecha</th>
                <th class="num">Ventas</th>
                <th class="num">Netas</th>
                <th class="num">Efectivo</th>
                <th class="num">QR</th>
                <th class="num">Fiado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($byDay as $row)
                <tr @class(['alt' => $loop->even])>
                    <td>{{ \Illuminate\Support\Carbon::parse($row->day)->format('d/m/Y') }}</td>
                    <td class="num">{{ number_format($row->sales_count, 0, '.', ',') }}</td>
                    <td class="num m-orange">Bs. {{ number_format($row->net, 1, '.', ',') }}</td>
                    <td class="num m-green">Bs. {{ number_format($row->cash, 1, '.', ',') }}</td>
                    <td class="num m-blue">Bs. {{ number_format($row->qr, 1, '.', ',') }}</td>
                    <td class="num m-amber">Bs. {{ number_format($row->debt, 1, '.', ',') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Sin ventas en el rango.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2><span class="sec-bar">|</span>Top productos vendidos</h2>
    <table class="data">
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th class="num">Cantidad (unidad base)</th>
                <th>Unidad</th>
                <th class="num">Ingresos</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($topProducts as $row)
                <tr @class(['alt' => $loop->even])>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td class="num">{{ number_format($row->quantity, 0, '.', ',') }}</td>
                    <td>{{ $row->unit_symbol ?? '' }}</td>
                    <td class="num m-orange">Bs. {{ number_format($row->revenue, 1, '.', ',') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Sin productos vendidos en el rango.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2><span class="sec-bar">|</span>Ventas por usuario</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Usuario</th>
                <th class="num">Ventas</th>
                <th class="num">Netas</th>
                <th class="num">Efectivo</th>
                <th class="num">QR</th>
                <th class="num">Fiado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($byUser as $row)
                <tr @class(['alt' => $loop->even])>
                    <td>{{ $row->user_name }}</td>
                    <td class="num">{{ number_format($row->sales_count, 0, '.', ',') }}</td>
                    <td class="num m-orange">Bs. {{ number_format($row->net, 1, '.', ',') }}</td>
                    <td class="num m-green">Bs. {{ number_format($row->cash, 1, '.', ',') }}</td>
                    <td class="num m-blue">Bs. {{ number_format($row->qr, 1, '.', ',') }}</td>
                    <td class="num m-amber">Bs. {{ number_format($row->debt, 1, '.', ',') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Sin ventas por usuario en el rango.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Maxxi · Reporte del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }} · Página {PAGE_NUM} de {PAGE_COUNT}
    </div>
</body>
</html>
