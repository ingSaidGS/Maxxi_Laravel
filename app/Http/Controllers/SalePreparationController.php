<?php

namespace App\Http\Controllers;

use App\Services\SalePreparationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SalePreparationController extends Controller
{
    public function __construct(private readonly SalePreparationService $preparation) {}

    /**
     * Muestra la hoja de preparación para una fecha de entrega.
     *
     * Por defecto se muestra el día siguiente ("mañana"), que es cuando suele
     * agendarse la entrega; la fecha se puede cambiar por query string.
     */
    public function index(Request $request): View
    {
        $date = $this->resolveDate($request->query('date'));
        $rows = $this->preparation->forDate($date);

        $notesCount = $rows
            ->flatMap(fn (array $row): array => $row['breakdown']->pluck('sale_note_id')->all())
            ->filter()
            ->unique()
            ->count();

        $customersCount = $rows
            ->flatMap(fn (array $row): array => $row['breakdown']->pluck('customer')->all())
            ->reject(fn (string $customer): bool => $customer === '—')
            ->unique()
            ->count();

        return view('sale-preparation.index', [
            'date' => $date,
            'rows' => $rows,
            'notesCount' => $notesCount,
            'customersCount' => $customersCount,
        ]);
    }

    /**
     * Resuelve la fecha de entrega a partir del query string.
     */
    protected function resolveDate(?string $value): Carbon
    {
        if ($value !== null && Carbon::hasFormat($value, 'Y-m-d')) {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        return Carbon::tomorrow();
    }
}
