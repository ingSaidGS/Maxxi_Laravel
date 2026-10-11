<?php

namespace App\Services;

use App\Models\SaleNoteDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SalePreparationService
{
    /**
     * Arma la hoja de preparación para una fecha de entrega.
     *
     * Toma las líneas ACTIVAS de las notas de venta "pendiente" cuya
     * "scheduled_for" cae en el día indicado y las agrupa por presentación
     * (mismo producto + mismo empaque), sumando la cantidad pedida.
     *
     * La clave de agrupación es "presentation_id", no el tamaño: una "arroba"
     * (grande) y una "libra" (pequeño) del mismo producto quedan en renglones
     * separados porque son empaques distintos. El "presentation_size" viaja
     * como dato de apoyo para quien prepara.
     *
     * @return Collection<int, array{
     *     presentation_id: int,
     *     product: string,
     *     unit: string,
     *     factor: int,
     *     base_unit_symbol: string,
     *     size: string|null,
     *     total: int,
     *     breakdown: Collection<int, array{sale_note_id: int|null, customer: string, quantity: int}>
     * }>
     */
    public function forDate(Carbon $date): Collection
    {
        return SaleNoteDetail::query()
            ->where('active', true)
            ->whereHas('saleNote', function ($query) use ($date): void {
                $query->where('status', 'pendiente')
                    ->whereDate('scheduled_for', $date->toDateString());
            })
            ->with(['saleNote', 'presentation.product.baseUnit', 'presentation.unit'])
            ->get()
            ->groupBy('presentation_id')
            ->map(fn (Collection $lines): array => $this->buildRow($lines))
            ->sortBy([
                ['product', 'asc'],
                ['unit', 'asc'],
            ])
            ->values();
    }

    /**
     * Construye un renglón de la hoja a partir de las líneas de una presentación.
     *
     * El total es la suma de las cantidades pedidas en TODAS las notas (la
     * cantidad ya viene en la unidad de la presentación). El desglose guarda,
     * por nota, el cliente y lo que aporta cada una.
     *
     * @param  Collection<int, SaleNoteDetail>  $lines
     * @return array<string, mixed>
     */
    protected function buildRow(Collection $lines): array
    {
        /** @var SaleNoteDetail $first */
        $first = $lines->first();
        $presentation = $first->presentation;

        return [
            'presentation_id' => (int) $presentation->id,
            'product' => (string) ($presentation->product?->name ?? '—'),
            'unit' => (string) ($presentation->unit?->name ?? '—'),
            'factor' => (int) $presentation->conversion_factor,
            'base_unit_symbol' => (string) ($presentation->product?->baseUnit?->symbol ?? ''),
            'size' => $presentation->presentation_size,
            'total' => (int) $lines->sum('quantity'),
            'breakdown' => $lines
                ->groupBy('sale_note_id')
                ->map(function (Collection $noteLines): array {
                    $saleNote = $noteLines->first()->saleNote;

                    return [
                        'sale_note_id' => $saleNote?->id,
                        'customer' => (string) (($saleNote?->customer_name) ?: '—'),
                        'quantity' => (int) $noteLines->sum('quantity'),
                    ];
                })
                ->sortBy('sale_note_id')
                ->values(),
        ];
    }
}
