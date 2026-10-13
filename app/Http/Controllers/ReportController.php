<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    /**
     * Muestra el tablero de reportes para un rango de fechas.
     *
     * Por defecto el rango es el día de hoy; se puede cambiar con las query
     * strings "from" y "to" (formato Y-m-d).
     */
    public function index(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);

        return view('report.index', [
            'from' => $from,
            'to' => $to,
            ...$this->reports->forRange($from, $to),
        ]);
    }

    /**
     * Exporta el reporte del rango en un archivo Excel con las cuatro secciones.
     *
     * No usa dependencias externas: Excel abre una tabla HTML guardada con
     * extensión .xls. Se antepone el BOM UTF-8 para que los acentos se lean bien.
     * Los montos van como números sin el prefijo "Bs." para poder sumarlos.
     */
    public function exportExcel(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);
        $data = $this->reports->forRange($from, $to);

        $filename = 'reporte_'.$from->format('Y-m-d').'_'.$to->format('Y-m-d').'.xls';

        $html = "\xEF\xBB\xBF".view('report.excel', [
            'from' => $from,
            'to' => $to,
            ...$data,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Resuelve el rango de fechas a partir del query string.
     *
     * Si falta una fecha, se usa hoy. Si el rango viene invertido, se ordena.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveRange(Request $request): array
    {
        $today = Carbon::today();

        $from = $this->parseDate($request->query('from')) ?? $today->copy();
        $to = $this->parseDate($request->query('to')) ?? $today->copy();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    /**
     * Convierte un valor "Y-m-d" en una fecha a medianoche, o null si no es válido.
     */
    protected function parseDate(?string $value): ?Carbon
    {
        if ($value !== null && Carbon::hasFormat($value, 'Y-m-d')) {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        return null;
    }
}
