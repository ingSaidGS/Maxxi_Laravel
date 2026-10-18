<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    public function exportExcel(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $data = $this->reports->forRange($from, $to);

        $filename = 'reporte_'.$from->format('Y-m-d').'_'.$to->format('Y-m-d').'.xlsx';

        $spreadsheet = new Spreadsheet;
        (new ReportExport($from, $to, $data))->generate($spreadsheet);
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
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

    /**
     * Endpoint JSON para productos con stock bajo en unidad de compra.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'threshold' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        return response()->json($this->reports->lowStock((int) $validated['threshold']));
    }

    /**
     * Exporta a PDF los productos con stock bajo para el umbral indicado.
     *
     * Es independiente del rango de fechas (igual que el listado en pantalla):
     * reutiliza la misma consulta de "lowStock" y respeta el umbral elegido en la
     * sección «Productos con stock bajo».
     */
    public function exportLowStockPdf(Request $request)
    {
        $validated = $request->validate([
            'threshold' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $threshold = (int) $validated['threshold'];

        $pdf = Pdf::loadView('report.pdf-low-stock', [
            'threshold' => $threshold,
            'products' => $this->reports->lowStock($threshold),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('stock_bajo_umbral_'.$threshold.'.pdf');
    }

    public function exportPdf(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);
        $data = $this->reports->forRange($from, $to);

        $pdf = Pdf::loadView('report.pdf', [
            'from' => $from,
            'to' => $to,
            ...$data,
        ])->setPaper('a4', 'portrait');

        $filename = 'reporte_'.$from->format('Y-m-d').'_'.$to->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
