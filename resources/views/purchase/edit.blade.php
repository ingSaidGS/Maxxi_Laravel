@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-2 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
    ];
@endphp

<x-layouts.sistema title="Editar compra #{{ $purchase->id }}">
    {{-- Encabezado de página --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Editar compra #{{ $purchase->id }}</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Actualiza los datos de la compra. Las líneas no se modifican.</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <a href="{{ route('purchases.show', $purchase) }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Ver detalle
            </a>
            <a href="{{ route('purchases.index') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Volver al listado
            </a>
        </div>
    </div>

    {{-- Mensajes de error --}}
    @if ($errors->any())
        <div role="alert" class="mb-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86l-8.48 14.7A1.5 1.5 0 003.1 21h17.8a1.5 1.5 0 001.3-2.44l-8.48-14.7a1.5 1.5 0 00-2.6 0z" />
            </svg>
            <div>
                <p class="mb-1 font-semibold">Revisa los siguientes errores:</p>
                <ul class="mb-0 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form id="purchase-form" action="{{ route('purchases.update', $purchase) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Sección: Datos de la compra --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Datos de la compra</h2>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="supplier" class="mb-1 block text-sm font-medium text-gray-700">Proveedor <span class="font-normal text-gray-400">(opcional)</span></label>
                    <input type="text" id="supplier" name="supplier" value="{{ old('supplier', $purchase->supplier) }}" maxlength="15"
                           placeholder="Ej. Distribuidora XYZ" autocomplete="off"
                           @error('supplier') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm focus:ring-2 @error('supplier') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                    @error('supplier')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="voucher_number" class="mb-1 block text-sm font-medium text-gray-700">Comprobante <span class="font-normal text-gray-400">(opcional)</span></label>
                    <input type="text" id="voucher_number" name="voucher_number" value="{{ old('voucher_number', $purchase->voucher_number) }}" maxlength="8"
                           placeholder="Ej. F-00123" autocomplete="off"
                           @error('voucher_number') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm focus:ring-2 @error('voucher_number') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                    @error('voucher_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="purchased_at" class="mb-1 block text-sm font-medium text-gray-700">Fecha de compra</label>
                    <input type="datetime-local" id="purchased_at" name="purchased_at" value="{{ old('purchased_at', $purchase->purchased_at?->format('Y-m-d\TH:i')) }}" required
                           @error('purchased_at') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm focus:ring-2 sm:max-w-xs @error('purchased_at') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                    @error('purchased_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Sección: Líneas (solo lectura) --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-4 flex items-center gap-2">
                <h2 class="mb-0 text-lg font-semibold text-gray-800">Líneas de la compra</h2>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Solo lectura</span>
            </div>

            @if ($purchase->purchaseDetails->isEmpty())
                <p class="mb-0 text-sm text-gray-500">Esta compra no tiene líneas registradas.</p>
            @else
                {{-- Tabla (escritorio) --}}
                <div class="hidden overflow-hidden rounded-xl border border-gray-200 md:block">
                    <table class="mb-0 w-full border-collapse text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide">
                                <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Producto</th>
                                <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Cantidad</th>
                                <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Equivale a</th>
                                <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Costo unitario</th>
                                <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchase->purchaseDetails as $detail)
                                <tr class="border-t border-gray-100">
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $detail->presentation?->product?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums text-gray-800">{{ $detail->quantity }} {{ $detail->presentation?->unit?->name ?? $detail->presentation?->unit?->symbol }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums text-gray-500">{{ $detail->base_quantity }} {{ $detail->presentation?->product?->baseUnit?->name ?? $detail->presentation?->product?->baseUnit?->symbol }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums text-gray-600">Bs. {{ $detail->unit_price }}</td>
                                    <td class="px-4 py-3 text-right font-medium tabular-nums text-gray-800">Bs. {{ $detail->subtotal }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas (móvil) --}}
                <div class="space-y-3 md:hidden">
                    @foreach ($purchase->purchaseDetails as $detail)
                        <div class="rounded-xl border border-gray-200 bg-white p-4">
                            <p class="mb-0 font-medium text-gray-800">{{ $detail->presentation?->product?->name ?? '—' }}</p>
                            <p class="mb-0 mt-1 text-sm text-gray-500">Cantidad: <span class="tabular-nums text-gray-800">{{ $detail->quantity }} {{ $detail->presentation?->unit?->name ?? $detail->presentation?->unit?->symbol }}</span></p>
                            <p class="mb-0 mt-0.5 text-sm text-gray-500">Equivale a: <span class="tabular-nums text-gray-800">{{ $detail->base_quantity }} {{ $detail->presentation?->product?->baseUnit?->name ?? $detail->presentation?->product?->baseUnit?->symbol }}</span></p>
                            <p class="mb-0 mt-0.5 text-sm text-gray-500">Costo unitario: <span class="tabular-nums text-gray-800">Bs. {{ $detail->unit_price }}</span></p>
                            <p class="mb-0 mt-2 text-sm text-gray-500">Subtotal: <span class="font-semibold tabular-nums text-gray-800">Bs. {{ $detail->subtotal }}</span></p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center justify-between gap-3 rounded-lg border border-maxxi-orange/30 bg-maxxi-orange/5 px-4 py-3">
                    <span class="text-sm font-semibold uppercase tracking-wide text-maxxi-orange-dark">Total</span>
                    <span class="text-lg font-bold tabular-nums text-maxxi-orange-dark">Bs. {{ $purchase->total }}</span>
                </div>
            @endif

            <p class="mt-2 mb-0 text-xs text-gray-500">Las líneas de una compra son históricas; para ajustar cantidades o costos registra una nueva compra.</p>
        </div>

        {{-- Acciones --}}
        <div class="flex flex-col gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('purchases.show', $purchase) }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">Cancelar</a>
            <button type="submit" class="{{ $botones['primario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Guardar
            </button>
        </div>
    </form>

    {{-- Modal: confirmar actualización de la compra --}}
    <x-modal name="confirm-purchase" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-maxxi-orange/10 text-maxxi-orange-dark">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Guardar los cambios?</h2>
                    <p class="mb-0 text-sm text-gray-600">Se actualizarán los datos de la compra #{{ $purchase->id }}.</p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-purchase')">
                    Cancelar
                </button>
                <button type="button" id="confirm-purchase-button" class="{{ $botones['primario'] }}">
                    Sí, guardar
                </button>
            </div>
        </div>
    </x-modal>

    <script>
        (function () {
            var form = document.getElementById('purchase-form');
            var confirmButton = document.getElementById('confirm-purchase-button');
            var bypassConfirm = false;

            if (!form) { return; }

            form.addEventListener('submit', function (event) {
                if (bypassConfirm) { return; }

                event.preventDefault();

                if (!window.Alpine) {
                    if (window.confirm('¿Guardar los cambios de la compra?')) {
                        bypassConfirm = true;
                        form.submit();
                        bypassConfirm = false;
                    }
                    return;
                }

                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-purchase' }));
            });

            if (confirmButton) {
                confirmButton.addEventListener('click', function () {
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirm-purchase' }));
                    bypassConfirm = true;

                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }

                    bypassConfirm = false;
                });
            }
        })();
    </script>
</x-layouts.sistema>
