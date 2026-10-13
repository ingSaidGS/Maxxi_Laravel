<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Sale;
use App\Models\SaleNote;
use App\Models\Unit;
use App\Services\ReportService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    /**
     * Panel principal: resumen de hoy, tendencia de la semana, top de productos,
     * ventas recientes, inventario y notas de venta pendientes.
     */
    public function index(): View
    {
        $today = Carbon::today();
        $weekStart = $today->copy()->subDays(6);

        $todayData = $this->reports->forRange($today, $today);
        $weekData = $this->reports->forRange($weekStart, $today);

        // Serie de los últimos 7 días, rellenando con 0 los días sin ventas.
        $byDayKeyed = $weekData['byDay']->keyBy(
            fn ($row): string => Carbon::parse($row->day)->toDateString()
        );

        $trend = collect(range(6, 0))->map(function (int $offset) use ($today, $byDayKeyed): array {
            $date = $today->copy()->subDays($offset);
            $row = $byDayKeyed->get($date->toDateString());

            return [
                'date' => $date,
                'net' => (float) ($row->net ?? 0),
                'count' => (int) ($row->sales_count ?? 0),
            ];
        });

        // Notas de venta pendientes, primero las que tienen fecha programada.
        $pendingNotes = SaleNote::query()
            ->where('status', 'pendiente')
            ->orderByRaw('scheduled_for IS NULL')
            ->orderBy('scheduled_for')
            ->get();

        $outOfStock = Product::query()
            ->where('active', true)
            ->where('stock', '<=', 0)
            ->orderBy('name')
            ->get();

        return view('dashboard', [
            'today' => $today,
            'economic' => $todayData['economic'],
            'trend' => $trend,
            'trendMax' => max(1.0, (float) $trend->max('net')),
            'trendTotal' => (float) $trend->sum('net'),
            'recentSales' => Sale::with('user')->orderByDesc('sold_at')->limit(5)->get(),
            'topProducts' => $todayData['topProducts']->take(5),
            'inventory' => [
                'products' => Product::where('active', true)->count(),
                'presentations' => ProductPresentation::where('active', true)->count(),
                'units' => Unit::where('active', true)->count(),
                'outOfStock' => $outOfStock->count(),
            ],
            'outOfStock' => $outOfStock->take(5),
            'pendingNotesCount' => $pendingNotes->count(),
            'nextDeliveries' => $pendingNotes->take(5),
        ]);
    }
}
