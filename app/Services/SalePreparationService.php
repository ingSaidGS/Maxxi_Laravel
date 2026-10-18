<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\SaleNote;
use App\Models\SaleNoteDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
     *     breakdown: Collection<int, array{
     *         sale_note_id: int|null,
     *         customer: string,
     *         quantity: int,
     *         sale_note_detail_id: int,
     *         barcode: string|null,
     *         packages: int,
     *         picked: int
     *     }>
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
                ->map(function (Collection $noteLines) use ($reference, $presentation): array {
                    $saleNote = $noteLines->first()->saleNote;

                    return [
                        'sale_note_id' => $saleNote?->id,
                        'customer' => (string) (($saleNote?->customer_name) ?: '—'),
                        'quantity' => (int) $noteLines->sum('base_quantity') / $reference,
                        // Datos para el alistamiento (picking): la línea de la nota
                        // que se escanea y su avance (paquetes pedidos vs. contados).
                        'sale_note_detail_id' => (int) $noteLines->first()->id,
                        'barcode' => $presentation->barcode,
                        'packages' => (int) $noteLines->sum('quantity'),
                        'picked' => (int) $noteLines->sum('picked_quantity'),
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

    /**
     * Registra un escaneo de alistamiento (picking) sobre una nota pendiente.
     *
     * Busca, entre las líneas ACTIVAS de empaques PEQUEÑOS de la nota, la que
     * corresponde al código de barras escaneado y suma 1 a su contador
     * "picked_quantity", sin superar la cantidad pedida. El incremento es
     * atómico para tolerar varios equipos escaneando a la vez.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function pick(SaleNote $saleNote, string $barcode): array
    {
        $this->ensurePending($saleNote);

        $details = $this->pickingDetailsFor($saleNote, $barcode);

        if ($details->isEmpty()) {
            throw ValidationException::withMessages([
                'barcode' => 'Este código no corresponde a un empaque pequeño de esta nota.',
            ]);
        }

        /** @var SaleNoteDetail|null $detail */
        $detail = $details->first(
            fn (SaleNoteDetail $candidate): bool => (int) $candidate->picked_quantity < (int) $candidate->quantity
        );

        if ($detail === null) {
            return [
                'scanned' => false,
                'exhausted' => true,
                'detail' => $this->detailPayload($details->first()),
                'note' => $this->noteProgress($saleNote),
            ];
        }

        SaleNoteDetail::query()
            ->whereKey($detail->id)
            ->whereColumn('picked_quantity', '<', 'quantity')
            ->increment('picked_quantity');

        $detail->refresh();

        return [
            'scanned' => true,
            'exhausted' => false,
            'detail' => $this->detailPayload($detail),
            'note' => $this->noteProgress($saleNote),
        ];
    }

    /**
     * Marca una línea de alistamiento como COMPLETA de una sola vez
     * ("picked_quantity" = "quantity").
     *
     * Pensado para empaques pequeños SIN código de barras (p. ej. granel, arroz
     * a kilo), que no se pueden escanear: el contador salta al total pedido con
     * un solo clic. No mueve stock; el stock se descuenta al entregar la nota.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function completeDetail(SaleNote $saleNote, SaleNoteDetail $detail): array
    {
        $this->ensurePending($saleNote);
        $this->ensurePickingDetail($saleNote, $detail);

        SaleNoteDetail::query()
            ->whereKey($detail->id)
            ->update(['picked_quantity' => DB::raw('quantity')]);

        $detail->refresh();

        return [
            'scanned' => true,
            'exhausted' => false,
            'detail' => $this->detailPayload($detail),
            'note' => $this->noteProgress($saleNote),
        ];
    }

    /**
     * Reinicia el alistamiento de una línea ("picked_quantity" = 0).
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function resetDetail(SaleNote $saleNote, SaleNoteDetail $detail): array
    {
        $this->ensurePending($saleNote);
        $this->ensurePickingDetail($saleNote, $detail);

        SaleNoteDetail::query()
            ->whereKey($detail->id)
            ->update(['picked_quantity' => 0]);

        $detail->refresh();

        return [
            'scanned' => false,
            'exhausted' => false,
            'detail' => $this->detailPayload($detail),
            'note' => $this->noteProgress($saleNote),
        ];
    }

    /**
     * Deshace un escaneo de alistamiento: resta 1 al contador de la línea, sin
     * bajar de cero.
     *
     * A diferencia del escaneo (que ubica la línea por código de barras), aquí
     * la línea se identifica por su id, de modo que el botón "deshacer" también
     * funciona en empaques sin código.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function unpickDetail(SaleNote $saleNote, SaleNoteDetail $detail): array
    {
        $this->ensurePending($saleNote);
        $this->ensurePickingDetail($saleNote, $detail);

        SaleNoteDetail::query()
            ->whereKey($detail->id)
            ->where('picked_quantity', '>', 0)
            ->decrement('picked_quantity');

        $detail->refresh();

        return [
            'scanned' => false,
            'exhausted' => false,
            'detail' => $this->detailPayload($detail),
            'note' => $this->noteProgress($saleNote),
        ];
    }

    /**
     * Líneas activas de empaques pequeños de la nota que coinciden con el código
     * de barras escaneado.
     *
     * @return Collection<int, SaleNoteDetail>
     */
    protected function pickingDetailsFor(SaleNote $saleNote, string $barcode): Collection
    {
        return SaleNoteDetail::query()
            ->where('sale_note_id', $saleNote->id)
            ->where('active', true)
            ->whereHas('presentation', function ($query) use ($barcode): void {
                $query->where('barcode', $barcode)->where('presentation_size', 'pequeño');
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * Avance de alistamiento de una línea.
     *
     * @return array<string, mixed>
     */
    protected function detailPayload(SaleNoteDetail $detail): array
    {
        $quantity = (int) $detail->quantity;
        $picked = (int) $detail->picked_quantity;

        return [
            'id' => (int) $detail->id,
            'quantity' => $quantity,
            'picked' => $picked,
            'completed' => $picked >= $quantity,
        ];
    }

    /**
     * Avance de alistamiento de una nota (solo empaques pequeños activos).
     *
     * @return array<string, mixed>
     */
    protected function noteProgress(SaleNote $saleNote): array
    {
        $details = SaleNoteDetail::query()
            ->where('sale_note_id', $saleNote->id)
            ->where('active', true)
            ->whereHas('presentation', function ($query): void {
                $query->where('presentation_size', 'pequeño');
            })
            ->get(['id', 'quantity', 'picked_quantity']);

        $linesTotal = $details->count();
        $linesComplete = $details
            ->filter(fn (SaleNoteDetail $detail): bool => (int) $detail->picked_quantity >= (int) $detail->quantity)
            ->count();

        return [
            'sale_note_id' => (int) $saleNote->id,
            'lines_total' => $linesTotal,
            'lines_complete' => $linesComplete,
            'packed' => $linesTotal > 0 && $linesComplete === $linesTotal,
        ];
    }

    /**
     * Verifica que la línea pertenezca a la nota y sea un empaque pequeño activo.
     *
     * @throws ValidationException
     */
    protected function ensurePickingDetail(SaleNote $saleNote, SaleNoteDetail $detail): void
    {
        $valid = (int) $detail->sale_note_id === (int) $saleNote->id
            && $detail->active
            && $detail->presentation
            && $detail->presentation->presentation_size === 'pequeño';

        if (! $valid) {
            throw ValidationException::withMessages([
                'detail' => 'La línea no corresponde a un empaque pequeño de esta nota.',
            ]);
        }
    }

    /**
     * Verifica que la nota esté pendiente antes de alistarla.
     *
     * @throws ValidationException
     */
    protected function ensurePending(SaleNote $saleNote): void
    {
        if ($saleNote->status !== 'pendiente') {
            throw ValidationException::withMessages([
                'barcode' => 'Solo se puede alistar una nota pendiente.',
            ]);
        }
    }
}
