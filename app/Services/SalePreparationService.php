<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPresentation;
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
     * Si $date es null NO se filtra por fecha de entrega: se incluyen TODAS las
     * notas pendientes, tengan o no una fecha agendada (el campo "scheduled_for"
     * es opcional en el formulario de nota de venta).
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
    public function forDate(?Carbon $date = null): Collection
    {
        return SaleNoteDetail::query()
            ->where('active', true)
            ->whereHas('saleNote', function ($query) use ($date): void {
                $query->where('status', 'pendiente');

                if ($date !== null) {
                    $query->whereDate('scheduled_for', $date->toDateString());
                }
            })
            ->with(['saleNote', 'presentation.product.baseUnit', 'presentation.product.presentations', 'presentation.unit'])
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
     * El total es la suma de la cantidad base pedida en TODAS las notas, dividida
     * por el factor de referencia del producto+unidad: la cantidad se muestra en
     * la unidad de referencia (igual que en la venta). El desglose guarda, por
     * nota, el cliente y lo que aporta cada una, en esa misma unidad.
     *
     * @param  Collection<int, SaleNoteDetail>  $lines
     * @return array<string, mixed>
     */
    protected function buildRow(Collection $lines): array
    {
        /** @var SaleNoteDetail $first */
        $first = $lines->first();
        $presentation = $first->presentation;
        $product = $presentation->product;
        $reference = $this->referenceFactor($product, $presentation);

        return [
            'presentation_id' => (int) $presentation->id,
            'product' => (string) ($product?->name ?? '—'),
            'unit' => (string) ($presentation->unit?->name ?? '—'),
            'factor' => (int) $presentation->conversion_factor,
            'base_unit_symbol' => (string) ($product?->baseUnit?->symbol ?? ''),
            'size' => $presentation->presentation_size,
            'total' => (int) $lines->sum('base_quantity') / $reference,
            'breakdown' => $lines
                ->groupBy('sale_note_id')
                ->map(function (Collection $noteLines) use ($reference): array {
                    $saleNote = $noteLines->first()->saleNote;

                    return [
                        'sale_note_id' => $saleNote?->id,
                        'customer' => (string) (($saleNote?->customer_name) ?: '—'),
                        'quantity' => (int) $noteLines->sum('base_quantity') / $reference,
                    ];
                })
                ->sortBy('sale_note_id')
                ->values(),
        ];
    }

    /**
     * Factor de referencia para expresar la cantidad en la unidad mostrada.
     *
     * Es el MENOR "conversion_factor" ACTIVO del catálogo de presentaciones del
     * mismo producto y la misma unidad (no de las líneas de la nota), tal como en
     * la venta: así una arroba de 48 lb se muestra como "2 arrobas" cuando el
     * producto también tiene una arroba de 24 lb. Si no hay referencias, se usa
     * el factor de la propia presentación.
     */
    protected function referenceFactor(?Product $product, ProductPresentation $presentation): int
    {
        $factor = $product?->presentations
            ->filter(fn (ProductPresentation $candidate): bool => $candidate->active
                && (int) $candidate->unit_id === (int) $presentation->unit_id)
            ->min('conversion_factor');

        return max((int) ($factor ?: $presentation->conversion_factor), 1);
    }
}
