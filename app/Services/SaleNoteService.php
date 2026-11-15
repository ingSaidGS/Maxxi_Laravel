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
        $saleNote->fill([
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'noted_at' => $data['noted_at'],
            'scheduled_for' => $data['scheduled_for'],
            'delivery_latitude' => $data['delivery_latitude'] ?? null,
            'delivery_longitude' => $data['delivery_longitude'] ?? null,
            'sale_discount' => $data['sale_discount'],
        ]);

        // El total se calcula DESPUÉS de asignar el descuento nuevo: si se
        // calculara antes, usaría el descuento anterior y quedaría desfasado.
        $saleNote->total = $this->totalFromActiveDetails($saleNote);
        $saleNote->save();

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
     * Actualiza la cantidad de una línea activa y recalcula el total de la nota.
     *
     * Solo se permite en notas pendientes: si la nota ya fue entregada o
     * anulada, ensurePending() lanza un error de validación. Se conservan los
     * valores históricos de la línea (conversion_factor, unit_price y
     * base_unit_cost_at_note); solo se recalculan "quantity", "base_quantity" y
     * "subtotal" a partir del precio unitario guardado.
     *
     * @throws ValidationException
     */
    public function updateDetailQuantity(SaleNoteDetail $detail, int $quantity): void
    {
        $this->ensurePending($detail->saleNote);

        if (! $detail->active) {
            throw ValidationException::withMessages([
                'quantity' => 'No se puede editar la cantidad de una línea quitada.',
            ]);
        }

        $baseQuantity = (int) $detail->conversion_factor * $quantity;

        if ($baseQuantity > 2147483647) {
            throw ValidationException::withMessages([
                'quantity' => 'La cantidad indicada es demasiado grande.',
            ]);
        }

        $detail->update([
            'quantity' => $quantity,
            'base_quantity' => $baseQuantity,
            'subtotal' => round($quantity * (float) $detail->unit_price, 1),
        ]);

        $this->refreshTotal($detail->saleNote);
    }

    /**
     * Agrega una presentación a la nota y recalcula el total.
     *
     * Solo se permite en notas pendientes. Si la presentación ya tiene una línea
     * ACTIVA en la nota, se suma la cantidad a esa línea (no se duplica el
     * renglón) conservando su precio histórico; en caso contrario se crea una
     * línea nueva con el precio de venta y el costo actuales (igual que al
     * registrar la nota). En ambos casos el total se recalcula desde las líneas
     * activas.
     *
     * @throws ValidationException
     */
    public function addDetail(SaleNote $saleNote, int $presentationId, int $quantity): SaleNoteDetail
    {
        $this->ensurePending($saleNote);

        $presentation = ProductPresentation::with('product')->findOrFail($presentationId);

        return DB::transaction(function () use ($saleNote, $presentation, $quantity) {
            $existing = $saleNote->saleNoteDetails()
                ->where('presentation_id', $presentation->id)
                ->where('active', true)
                ->first();

            // Ya está en la nota: se acumula la cantidad en la línea existente.
            if ($existing !== null) {
                $this->updateDetailQuantity($existing, (int) $existing->quantity + $quantity);

                return $existing->refresh();
            }

            $detail = $saleNote->saleNoteDetails()->create(
                $this->saleNoteDetailAttributes($presentation, $quantity)
            );

            $this->refreshTotal($saleNote);

            return $detail;
        });
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

            // Productos "de única vez" que esta entrega dejó sin stock: se dan de
            // baja en la misma transacción, igual que en la venta directa.
            $this->stock->deactivateDepletedSingleBatch(
                collect($requirements)->pluck('product_id')
            );

            $saleNote->update([
                'status' => 'entregada',
                'delivered_at' => now(),
                'sale_id' => $sale->id,
            ]);

            return $sale;
        });
    }

    /**
     * Líneas activas de una nota pendiente cuyo precio guardado ya no coincide
     * con el precio de venta vigente del catálogo.
     *
     * El precio de una nota se congela al tomar el pedido; esto permite avisar al
     * encargado de que el catálogo cambió y ofrecerle actualizarlo. Solo se
     * consideran presentaciones que se pueden vender hoy (producto y
     * presentación activos, venta habilitada y precio definido): las demás no
     * tienen un precio vigente al cual actualizar.
     *
     * "new_total" es el total que tendría la nota con los precios vigentes
     * (respetando su descuento).
     *
     * @return array{lines: list<array{detail: SaleNoteDetail, current_price: float, new_subtotal: float}>, new_total: float}
     */
    public function priceChanges(SaleNote $saleNote): array
    {
        if ($saleNote->status !== 'pendiente') {
            return ['lines' => [], 'new_total' => (float) $saleNote->total];
        }

        $details = $saleNote->saleNoteDetails()
            ->where('active', true)
            ->with(['presentation.product.baseUnit', 'presentation.unit'])
            ->get();

        $lines = [];
        $subtotals = 0.0;

        foreach ($details as $detail) {
            $currentPrice = $this->currentSalePrice($detail);

            if ($currentPrice !== null && abs($currentPrice - round((float) $detail->unit_price, 1)) >= 0.001) {
                $newSubtotal = round((int) $detail->quantity * $currentPrice, 1);
                $lines[] = ['detail' => $detail, 'current_price' => $currentPrice, 'new_subtotal' => $newSubtotal];
                $subtotals += $newSubtotal;

                continue;
            }

            $subtotals += (float) $detail->subtotal;
        }

        return [
            'lines' => $lines,
            'new_total' => round(max(0, $subtotals - (float) $saleNote->sale_discount), 1),
        ];
    }

    /**
     * Actualiza al precio vigente las líneas cuyo precio cambió y recalcula el
     * total de la nota.
     *
     * Bloqueo optimista: "$shownPrices" son los precios que el encargado vio en
     * el aviso ([id de línea => precio]). Si al confirmar los cambios no son
     * exactamente esos (el catálogo volvió a cambiar), no se aplica nada y se
     * pide revisar de nuevo. Además del precio y el subtotal, se actualiza el
     * costo de referencia de la línea al costo actual del producto, para que la
     * nota no muestre un costo viejo junto a un precio nuevo.
     *
     * @param  array<int, float>  $shownPrices
     * @return int cantidad de líneas actualizadas
     *
     * @throws ValidationException
     */
    public function refreshPrices(SaleNote $saleNote, array $shownPrices): int
    {
        return DB::transaction(function () use ($saleNote, $shownPrices) {
            $saleNote = SaleNote::query()
                ->whereKey($saleNote->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensurePending($saleNote);

            $lines = $this->priceChanges($saleNote)['lines'];

            $current = collect($lines)->mapWithKeys(
                fn (array $line) => [$line['detail']->id => $line['current_price']]
            );
            $shown = collect($shownPrices)->map(fn ($price) => round((float) $price, 1));

            $matches = $current->count() === $shown->count()
                && $current->every(fn (float $price, int $id) => $shown->has($id) && abs($shown[$id] - $price) < 0.001);

            if ($lines === [] || ! $matches) {
                throw ValidationException::withMessages([
                    'prices' => $lines === []
                        ? 'Los precios de la nota ya están actualizados.'
                        : 'Los precios volvieron a cambiar mientras revisabas. Revisa el aviso y vuelve a confirmar.',
                ]);
            }

            foreach ($lines as $line) {
                $line['detail']->update([
                    'unit_price' => $line['current_price'],
                    'subtotal' => $line['new_subtotal'],
                    'base_unit_cost_at_note' => round((float) ($line['detail']->presentation->product->base_unit_cost ?? 0), 2),
                ]);
            }

            $this->refreshTotal($saleNote);

            return count($lines);
        });
    }

    /**
     * Precio de venta vigente de la presentación de una línea, o null si hoy no
     * se puede vender (producto o presentación de baja, venta deshabilitada o
     * sin precio definido).
     */
    protected function currentSalePrice(SaleNoteDetail $detail): ?float
    {
        $presentation = $detail->presentation;

        if ($presentation === null
            || ! $presentation->active
            || ! $presentation->sale_enable
            || $presentation->sale_price === null
            || ! $presentation->product?->active) {
            return null;
        }

        return round((float) $presentation->sale_price, 1);
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
