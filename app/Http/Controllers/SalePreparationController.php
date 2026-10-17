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
     * Muestra la hoja de preparación.
     *
     * Sin "date" en el query string se muestran TODAS las notas pendientes,
     * tengan o no una fecha de entrega agendada (el campo es opcional en el
     * formulario de nota de venta). Si se indica una fecha válida ("Y-m-d"), se
     * limita a las notas pendientes agendadas ese día.
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
     *
     * Devuelve null cuando no llega una fecha válida ("Y-m-d"), lo que significa
     * "todas las notas pendientes" (sin filtrar por fecha de entrega).
     */
    protected function resolveDate(mixed $value): ?Carbon
    {
        if (is_string($value) && Carbon::hasFormat($value, 'Y-m-d')) {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        return null;
    }
}
