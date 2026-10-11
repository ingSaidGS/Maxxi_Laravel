<?php

namespace App\Services;

use App\Models\ProductPresentation;
use App\Models\Sale;
use App\Models\SaleNote;
use App\Models\SaleNoteDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleNoteService
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * Registra una nota de venta con sus líneas.
     *
     * La nota es una preventa/pedido tomado (normalmente) fuera del local: NO
     * mueve stock. El stock se descuenta recién al entregar (ver deliver()).
     *
     * El "unit_price" (y por lo tanto el subtotal) se deriva del precio de venta
     * de la presentación al momento de registrar la nota y queda como histórico;
     * el total es la suma de los subtotales menos el descuento, con un piso de
     * cero.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $userId): SaleNote
    {
        $details = $this->resolveDetails($data['details'] ?? []);
        $total = $this->totalFromLines($details, (float) $data['sale_discount']);

        return DB::transaction(function () use ($data, $userId, $details, $total) {
            $saleNote = SaleNote::create([
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'noted_at' => $data['noted_at'],
                'scheduled_for' => $data['scheduled_for'],
                'delivery_latitude' => $data['delivery_latitude'] ?? null,
                'delivery_longitude' => $data['delivery_longitude'] ?? null,
                'sale_discount' => $data['sale_discount'],
                'total' => $total,
                'user_id' => $userId,
            ]);

            foreach ($details as $detail) {
                $saleNote->saleNoteDetails()->create($detail['attributes']);
            }

            return $saleNote;
        });
    }

    /**
     * Actualiza la cabecera de la nota y recalcula su total.
     *
     * Las líneas no se modifican aquí: se quitan o reactivan individualmente
     * (deactivateDetail()/restoreDetail()) y el total se recalcula desde las
     * líneas activas.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateHeader(SaleNote $saleNote, array $data): SaleNote
    {
        $saleNote->update([
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'noted_at' => $data['noted_at'],
            'scheduled_for' => $data['scheduled_for'],
            'delivery_latitude' => $data['delivery_latitude'] ?? null,
            'delivery_longitude' => $data['delivery_longitude'] ?? null,
            'sale_discount' => $data['sale_discount'],
            'total' => $this->totalFromActiveDetails($saleNote),
        ]);

        return $saleNote;
    }

    /**
     * Quita (baja lógica) una línea de la nota y recalcula el total.
     *
     * El cliente puede cancelar un producto antes de la entrega; la línea no se
     * elimina, se marca "active = false" para conservar el histórico y deja de
     * contar en el total y en la venta generada al entregar.
     */
    public function deactivateDetail(SaleNoteDetail $detail): void
    {
        $this->ensurePending($detail->saleNote);

        $detail->update(['active' => false]);
        $this->refreshTotal($detail->saleNote);
    }

    /**
     * Reactiva una línea quitada previamente y recalcula el total.
     */
    public function restoreDetail(SaleNoteDetail $detail): void
    {
        $this->ensurePending($detail->saleNote);

        $detail->update(['active' => true]);
        $this->refreshTotal($detail->saleNote);
    }

    /**
     * Anula la nota (baja lógica vía estado).
     *
     * La nota no se elimina: pasa a "anulada" para conservar el histórico. Solo
     * se pueden anular notas pendientes.
     */
    public function cancel(SaleNote $saleNote): void
    {
        $this->ensurePending($saleNote);

        $saleNote->update(['status' => 'anulada']);
    }

    /**
     * Entrega la nota: genera la venta, descuenta stock y marca "entregada".
     *
     * Recién en la entrega se materializa la venta: se copian las líneas ACTIVAS
     * de la nota (con su precio y costo históricos), se descuenta el stock de cada
     * producto, se registra el pago recibido (efectivo/QR) y el estado/deuda de la
     * venta se derivan igual que en una venta directa. Todo ocurre en una única
     * transacción: si el stock es insuficiente, no se registra nada.
     *
     * El "base_unit_cost_at_sale" de cada línea se toma del costo actual del
     * producto al momento de la entrega (COGS realizado).
     *
     * @param  array{received_cash: float, received_qr: float}  $payments
     *
     * @throws ValidationException
     */
    public function deliver(SaleNote $saleNote, array $payments, int $userId): Sale
    {
        return DB::transaction(function () use ($saleNote, $payments, $userId) {
            $saleNote = SaleNote::query()
                ->whereKey($saleNote->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensurePending($saleNote);

            $details = $saleNote->saleNoteDetails()
                ->where('active', true)
                ->with('presentation.product')
                ->get();

            if ($details->isEmpty()) {
                throw ValidationException::withMessages([
                    'details' => 'La nota no tiene líneas activas para entregar.',
                ]);
            }

            $subtotals = round((float) $details->sum('subtotal'), 1);

            $amounts = $this->resolveAmounts($subtotals, [
                'sale_discount' => (float) $saleNote->sale_discount,
                'cash' => (float) ($payments['received_cash'] ?? 0),
                'qr' => (float) ($payments['received_qr'] ?? 0),
            ]);

            $this->ensureCustomerForFiada($amounts['status'], [
                'customer_name' => $saleNote->customer_name,
                'customer_phone' => $saleNote->customer_phone,
            ]);

            $sale = Sale::create([
                'customer_name' => $saleNote->customer_name,
                'customer_phone' => $saleNote->customer_phone,
                'sold_at' => now(),
                'total' => $amounts['total'],
                'status' => $amounts['status'],
                'sale_discount' => $saleNote->sale_discount,
                'cash' => $amounts['cash'],
                'qr' => $amounts['qr'],
                'debt' => $amounts['debt'],
                'user_id' => $userId,
            ]);

            $requirements = [];

            foreach ($details as $detail) {
                $presentation = $detail->presentation;

                $sale->saleDetails()->create([
                    'presentation_id' => $detail->presentation_id,
                    'quantity' => (int) $detail->quantity,
                    'conversion_factor' => (int) $detail->conversion_factor,
                    'base_quantity' => (int) $detail->base_quantity,
                    'unit_price' => round((float) $detail->unit_price, 1),
                    'base_unit_cost_at_sale' => round((float) ($presentation->product?->base_unit_cost ?? 0), 2),
                    'subtotal' => round((float) $detail->subtotal, 1),
                ]);

                $requirements[] = [
                    'product_id' => (int) $presentation->product_id,
                    'base_quantity' => (int) $detail->base_quantity,
                ];
            }

            $this->stock->decrementForSale($requirements);

            $saleNote->update([
                'status' => 'entregada',
                'delivered_at' => now(),
                'sale_id' => $sale->id,
            ]);

            return $sale;
        });
    }

    /**
     * Total de la nota recalculado desde sus líneas activas (1 decimal).
     *
     * "total" = suma de subtotales activos - descuento, con un piso de cero.
     */
    public function totalFromActiveDetails(SaleNote $saleNote): float
    {
        $subtotals = (float) $saleNote->saleNoteDetails()->where('active', true)->sum('subtotal');

        return round(max(0, $subtotals - (float) $saleNote->sale_discount), 1);
    }

    /**
     * Verifica que la nota esté pendiente antes de modificarla.
     *
     * @throws ValidationException
     */
    protected function ensurePending(SaleNote $saleNote): void
    {
        if ($saleNote->status !== 'pendiente') {
            throw ValidationException::withMessages([
                'status' => 'Solo se pueden modificar notas pendientes.',
            ]);
        }
    }

    /**
     * Recalcula y persiste el total de la nota.
     */
    protected function refreshTotal(SaleNote $saleNote): void
    {
        $saleNote->update(['total' => $this->totalFromActiveDetails($saleNote)]);
    }

    /**
     * Resuelve las líneas del formulario en presentaciones y sus atributos.
     *
     * @param  array<int, array{presentation_id: int|string, quantity: int|string}>  $rows
     * @return array<int, array{presentation: ProductPresentation, attributes: array<string, mixed>}>
     */
    protected function resolveDetails(array $rows): array
    {
        $presentations = ProductPresentation::with('product')
            ->whereIn('id', collect($rows)->pluck('presentation_id'))
            ->get()
            ->keyBy('id');

        $details = [];

        foreach ($rows as $row) {
            $presentation = $presentations->get($row['presentation_id']);

            if ($presentation === null) {
                continue;
            }

            $details[] = [
                'presentation' => $presentation,
                'attributes' => $this->saleNoteDetailAttributes($presentation, (int) $row['quantity']),
            ];
        }

        return $details;
    }

    /**
     * Atributos de una línea de nota derivados de la presentación.
     *
     * El "unit_price" es el precio de venta de la presentación al registrar la
     * nota y se guarda como histórico; "conversion_factor" y "base_quantity" se
     * derivan de la presentación; "base_unit_cost_at_note" es el costo por unidad
     * base del producto al registrar la nota. El subtotal es cantidad × precio.
     *
     * @return array<string, mixed>
     */
    protected function saleNoteDetailAttributes(ProductPresentation $presentation, int $quantity): array
    {
        $conversionFactor = (int) $presentation->conversion_factor;
        $unitPrice = round((float) $presentation->sale_price, 1);

        return [
            'presentation_id' => $presentation->id,
            'quantity' => $quantity,
            'conversion_factor' => $conversionFactor,
            'base_quantity' => $conversionFactor * $quantity,
            'unit_price' => $unitPrice,
            'base_unit_cost_at_note' => round((float) ($presentation->product?->base_unit_cost ?? 0), 2),
            'subtotal' => round($quantity * $unitPrice, 1),
        ];
    }

    /**
     * Total a partir de las líneas resueltas al registrar la nota (1 decimal).
     *
     * @param  array<int, array{presentation: ProductPresentation, attributes: array<string, mixed>}>  $details
     */
    protected function totalFromLines(array $details, float $discount): float
    {
        $subtotals = array_sum(array_map(
            fn (array $detail): float => (float) $detail['attributes']['subtotal'],
            $details,
        ));

        return round(max(0, $subtotals - $discount), 1);
    }

    /**
     * Deriva los importes de la venta a partir de los subtotales.
     *
     * Misma lógica que la venta directa: "total" = subtotales - descuento; el
     * estado se resuelve contra el monto pagado (efectivo + QR) y la deuda
     * "fiada" es lo que queda por pagar. El vuelto se devuelve del efectivo: si el
     * pago supera el total, se registra en "cash" solo lo justo para que efectivo
     * + QR == total (el excedente no se guarda).
     *
     * @param  array<string, mixed>  $data
     * @return array{total: float, paid: float, cash: float, qr: float, change: float, status: string, debt: float}
     */
    protected function resolveAmounts(float $subtotals, array $data): array
    {
        $total = round(max(0, $subtotals - (float) $data['sale_discount']), 1);
        $cash = round((float) $data['cash'], 1);
        $qr = round((float) $data['qr'], 1);
        $paid = round($cash + $qr, 1);
        $status = $this->resolveStatus($paid, $total);
        $debt = $status === 'fiada' ? round(max(0, $total - $paid), 1) : 0.0;
        $change = $status === 'fiada' ? 0.0 : round(max(0, $paid - $total), 1);

        if ($change > 0) {
            $qr = min($qr, $total);
            $cash = round(max(0, $total - $qr), 1);
        }

        return [
            'total' => $total,
            'paid' => $paid,
            'cash' => $cash,
            'qr' => $qr,
            'change' => $change,
            'status' => $status,
            'debt' => $debt,
        ];
    }

    /**
     * Determina el estado de la venta según el monto cubierto.
     *
     * "pagada" cuando efectivo + QR es mayor o igual al total; "fiada" en caso
     * contrario. La tolerancia de 0.05 evita falsos negativos por redondeo.
     */
    protected function resolveStatus(float $paid, float $total): string
    {
        return $paid >= $total - 0.05 ? 'pagada' : 'fiada';
    }

    /**
     * Exige nombre y teléfono del cliente cuando la venta queda "fiada".
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    protected function ensureCustomerForFiada(string $status, array $data): void
    {
        if ($status !== 'fiada') {
            return;
        }

        $errors = [];

        if (blank($data['customer_name'] ?? null)) {
            $errors['customer_name'] = 'El nombre del cliente es obligatorio cuando la venta queda fiada.';
        }

        if (blank($data['customer_phone'] ?? null)) {
            $errors['customer_phone'] = 'El teléfono del cliente es obligatorio cuando la venta queda fiada.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
