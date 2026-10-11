@php
    // Variantes de botón reutilizables (ver skill .opencode/skills/maxxi-ui).
    $botones = [
        'primario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-2 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'peligro' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1',
        'exito' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-green-200 bg-white px-3 py-1.5 text-sm font-medium text-green-700 transition hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-300 focus:ring-offset-1',
    ];
@endphp

<x-layouts.sistema title="Nueva venta">
    {{-- Encabezado de página --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="mb-0 text-2xl font-semibold text-gray-800">Nueva venta</h1>
            <p class="mb-0 mt-1 text-sm text-gray-500">Registra una venta y arma el carrito de productos.</p>
        </div>

        <a href="{{ route('sales.index') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Volver al listado
        </a>
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

    <form id="sale-form" action="{{ route('sales.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Aviso: campos derivados --}}
        <div class="flex items-center gap-3 rounded-lg border border-maxxi-navy/15 bg-maxxi-navy/5 px-4 py-3">
            <svg class="h-5 w-5 shrink-0 text-maxxi-navy" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <p class="mb-0 text-sm text-gray-600">El estado (pagada/fiada) y el cajero se determinan automáticamente al guardar.</p>
        </div>

        {{-- Sección: Datos de la venta --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Datos de la venta</h2>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="customer_name" class="mb-1 block text-sm font-medium text-gray-700">
                        Cliente <span class="font-normal text-gray-400">(obligatorio si la venta queda fiada)</span>
                    </label>
                    <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" maxlength="20"
                           placeholder="Ej. Juan Pérez" autocomplete="off"
                           @error('customer_name') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm focus:ring-2 @error('customer_name') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                    @error('customer_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="customer_phone" class="mb-1 block text-sm font-medium text-gray-700">
                        Teléfono <span class="font-normal text-gray-400">(obligatorio si la venta queda fiada)</span>
                    </label>
                    <input type="text" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="10"
                           placeholder="Ej. 77712345" inputmode="tel" autocomplete="off"
                           @error('customer_phone') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm focus:ring-2 @error('customer_phone') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                    @error('customer_phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="sold_at" class="mb-1 block text-sm font-medium text-gray-700">Fecha de venta</label>
                    <input type="datetime-local" id="sold_at" name="sold_at" value="{{ old('sold_at', now()->format('Y-m-d\TH:i')) }}" required
                           @error('sold_at') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm focus:ring-2 sm:max-w-xs @error('sold_at') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                    @error('sold_at')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Sección: Carrito --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Carrito</h2>

            @if ($presentations->isEmpty())
                {{-- Estado vacío --}}
                <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center">
                    <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                    </svg>
                    <p class="mb-0 mt-3 font-medium text-gray-700">No hay presentaciones con venta habilitada</p>
                    <p class="mb-0 text-sm text-gray-500">Habilita la venta en alguna presentación para poder venderla.</p>
                </div>
            @else
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div id="presentation-search-wrap" class="relative flex-1">
                        <label for="presentation-search" class="mb-1 block text-sm font-medium text-gray-700">Producto</label>
                        <input type="text" id="presentation-search" autocomplete="off" spellcheck="false"
                               role="combobox" aria-expanded="false" aria-controls="presentation-results" aria-autocomplete="list"
                               placeholder="Escribe el nombre o escanea el código de barras…"
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                        <ul id="presentation-results" role="listbox"
                            class="absolute z-20 mt-1 hidden max-h-64 w-full list-none overflow-y-auto rounded-xl border border-gray-200 bg-white py-1 pl-0 shadow-xl ring-1 ring-black/5"></ul>
                    </div>

                    <div class="w-full sm:w-28">
                        <label for="presentation-qty" class="mb-1 block text-sm font-medium text-gray-700">Cantidad</label>
                        <input type="number" id="presentation-qty" value="1" min="1" step="1" placeholder="1"
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
                    </div>

                    <button type="button" id="add-item" class="{{ $botones['marino'] }} w-full sm:w-auto">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                        </svg>
                        Agregar
                    </button>
                </div>

                <p class="mt-2 mb-0 text-xs text-gray-500">Escribe para buscar por nombre o escanea el código de barras; presiona Enter o haz clic en un resultado para agregarlo.</p>

                {{-- Tabla (escritorio) --}}
                <div class="mt-4 hidden overflow-hidden rounded-xl border border-gray-200 md:block">
                    <table class="mb-0 w-full border-collapse text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide">
                                <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Cantidad</th>
                                <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Producto</th>
                                <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Precio</th>
                                <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Subtotal</th>
                                <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Quitar</th>
                            </tr>
                        </thead>
                        <tbody id="cart-body"></tbody>
                    </table>
                </div>

                {{-- Tarjetas (móvil) --}}
                <div id="cart-cards" class="mt-4 space-y-3 md:hidden"></div>

                <p id="cart-empty" class="mt-4 mb-0 text-sm text-gray-500">El carrito está vacío. Agrega presentaciones para vender.</p>
            @endif

            <div id="cart-hidden"></div>
        </div>

        {{-- Sección: Pago --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Pago</h2>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <label for="sale_discount" class="mb-1 block text-sm font-medium text-gray-700">Descuento (Bs.)</label>
                    <input type="number" id="sale_discount" name="sale_discount" value="{{ old('sale_discount', '') }}" step="0.1" min="0" inputmode="decimal"
                           placeholder="Bs. 0.0"
                           @error('sale_discount') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm focus:ring-2 @error('sale_discount') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                    @error('sale_discount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="cash" class="mb-1 block text-sm font-medium text-gray-700">Efectivo (Bs.)</label>
                    <input type="number" id="cash" name="cash" value="{{ old('cash', '') }}" step="0.1" min="0" inputmode="decimal"
                           placeholder="Bs. 0.0"
                           @error('cash') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm focus:ring-2 @error('cash') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                    @error('cash')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <button type="button" id="cash-full-payment" class="{{ $botones['secundario'] }} mt-2 w-full">
                        Pago completo
                    </button>
                </div>

                <div>
                    <label for="qr" class="mb-1 block text-sm font-medium text-gray-700">QR (Bs.)</label>
                    <input type="number" id="qr" name="qr" value="{{ old('qr', '') }}" step="0.1" min="0" inputmode="decimal"
                           placeholder="Bs. 0.0"
                           @error('qr') aria-invalid="true" @enderror
                           class="block w-full rounded-lg shadow-sm focus:ring-2 @error('qr') border-red-300 focus:border-red-400 focus:ring-red-200 @else border-gray-300 focus:border-maxxi-orange focus:ring-maxxi-orange/40 @enderror" />
                    @error('qr')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Resumen calculado --}}
            <div class="mt-5 grid grid-cols-2 gap-3 rounded-lg border border-gray-200 bg-gray-50/60 p-4 sm:grid-cols-4">
                <div>
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Total</p>
                    <p class="mb-0 mt-1 text-lg font-semibold tabular-nums text-gray-800"><span id="total-display">Bs. 0.0</span></p>
                </div>

                <div>
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Estado</p>
                    <p class="mb-0 mt-1">
                        <span id="status-display" class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">pagada</span>
                    </p>
                </div>

                <div>
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Fio (deuda)</p>
                    <p class="mb-0 mt-1 text-lg font-semibold tabular-nums text-gray-800"><span id="debt-display">Bs. 0.0</span></p>
                </div>

                <div>
                    <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Vuelto</p>
                    <p class="mb-0 mt-1 text-lg font-semibold tabular-nums text-maxxi-navy"><span id="change-display">Bs. 0.0</span></p>
                </div>
            </div>

            <p id="applied-cash-note" class="mt-2 mb-0 hidden text-xs font-medium text-maxxi-navy"></p>

            <p class="mt-2 mb-0 text-xs text-gray-500">Si efectivo + QR es menor que el total, se marca como FIADA. Si es mayor, el vuelto se devuelve del efectivo y solo se registra lo justo (efectivo + QR = total).</p>
        </div>

        {{-- Acciones --}}
        <div class="flex flex-col gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('sales.index') }}" class="{{ $botones['secundario'] }} w-full sm:w-auto">Cancelar</a>
            <button type="submit" class="{{ $botones['primario'] }} w-full sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Guardar
            </button>
        </div>
    </form>

    {{-- Modal: confirmar registro de la venta --}}
    <x-modal name="confirm-sale" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-maxxi-orange/10 text-maxxi-orange-dark">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Registrar la venta?</h2>
                    <p id="confirm-summary" class="mb-0 text-sm text-gray-600"></p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-sale')">
                    Cancelar
                </button>
                <button type="button" id="confirm-sale-button" class="{{ $botones['primario'] }}">
                    Sí, registrar
                </button>
            </div>
        </div>
    </x-modal>

    {{-- Modal: confirmar venta fiada --}}
    <x-modal name="confirm-fiada" max-width="md" focusable>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </span>
                <div>
                    <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Confirmar venta fiada?</h2>
                    <p class="mb-1 text-sm text-gray-600">
                        Esta venta se registrará como <span class="font-semibold text-amber-700">FIADA</span>.
                    </p>
                    <p id="fiada-summary" class="mb-0 text-sm text-gray-600"></p>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-fiada')">
                    Cancelar
                </button>
                <button type="button" id="confirm-fiada-button" class="{{ $botones['primario'] }}">
                    Sí, registrar fiada
                </button>
            </div>
        </div>
    </x-modal>

    {{-- Modal: venta registrada con éxito --}}
    @if (session('success'))
        @php
            $successData = session('success');
            $successData = is_array($successData) ? $successData : [];
            $successIsFiada = ($successData['status'] ?? 'pagada') === 'fiada';
            $successDebt = (float) ($successData['debt'] ?? 0);
            $successTotal = (float) ($successData['total'] ?? 0);
            $successChange = (float) ($successData['change'] ?? 0);
            $successSaleId = $successData['sale_id'] ?? null;
        @endphp

        <x-modal name="sale-created" :show="true" max-width="md" focusable>
            <div class="p-6 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </span>

                <h2 class="mb-1 mt-4 text-lg font-semibold text-gray-800">¡Venta registrada!</h2>
                <p class="mb-0 text-sm text-gray-600">
                    @if ($successIsFiada)
                        La venta quedó registrada como FIADA. Fio pendiente: Bs. {{ number_format($successDebt, 1) }}.
                    @else
                        La venta por Bs. {{ number_format($successTotal, 1) }} se registró correctamente.
                    @endif
                </p>
                @if (! $successIsFiada && $successChange > 0)
                    <p class="mb-0 mt-1 text-sm font-semibold text-maxxi-navy">Vuelto: Bs. {{ number_format($successChange, 1) }}</p>
                @endif

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-center">
                    @if ($successSaleId)
                        <a href="{{ route('sales.show', $successSaleId) }}" class="{{ $botones['secundario'] }}">Ver detalle</a>
                        <a href="{{ route('sales.ticket', $successSaleId) }}" target="_blank" rel="noopener" class="{{ $botones['marino'] }}">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z" />
                            </svg>
                            Imprimir
                        </a>
                    @endif
                    <button type="button" class="{{ $botones['primario'] }}" x-on:click="$dispatch('close-modal', 'sale-created')">
                        Cerrar
                    </button>
                </div>
            </div>
        </x-modal>
    @endif

    <script>
        (function () {
            var presentations = {{ Js::from($presentations) }};
            var byId = {};
            presentations.forEach(function (p) { byId[p.id] = p; });

            var seed = {{ Js::from(old('details', [])) }};
            var cart = [];
            seed.forEach(function (d) {
                var id = parseInt(d.presentation_id, 10);
                var qty = parseInt(d.quantity, 10);
                if (byId[id]) {
                    cart.push({ presentation_id: id, quantity: qty > 0 ? qty : 1 });
                }
            });

            var tbody = document.getElementById('cart-body');
            var cards = document.getElementById('cart-cards');
            var empty = document.getElementById('cart-empty');
            var hidden = document.getElementById('cart-hidden');
            var totalDisplay = document.getElementById('total-display');
            var searchWrap = document.getElementById('presentation-search-wrap');
            var searchInput = document.getElementById('presentation-search');
            var resultsList = document.getElementById('presentation-results');
            var visible = [];
            var highlight = -1;
            var qtyInput = document.getElementById('presentation-qty');
            var discountInput = document.getElementById('sale_discount');
            var addButton = document.getElementById('add-item');

            var form = document.getElementById('sale-form');
            var cashInput = document.getElementById('cash');
            var qrInput = document.getElementById('qr');
            var nameInput = document.getElementById('customer_name');
            var phoneInput = document.getElementById('customer_phone');
            var debtDisplay = document.getElementById('debt-display');
            var changeDisplay = document.getElementById('change-display');
            var appliedCashNote = document.getElementById('applied-cash-note');
            var statusDisplay = document.getElementById('status-display');

            var inputClass = 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange';
            var statusPaidClass = 'inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700';
            var statusDebtClass = 'inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700';
            var removeClass = @json($botones['peligro']);

            function money(n) {
                return 'Bs. ' + (Math.round(n * 10) / 10).toFixed(1);
            }

            function subtotalOf(item) {
                return item.quantity * byId[item.presentation_id].price;
            }

            function baseQuantityOf(item) {
                return item.quantity * byId[item.presentation_id].conversionFactor;
            }

            // Pluralización simple en español, igual que en product/show.blade.php:
            // vocal final -> +s, consonante -> +es. Los símbolos no se pluralizan.
            function pluralizeUnit(name, count) {
                if (count === 1) { return name; }
                return /[aeiou]$/i.test(name) ? name + 's' : name + 'es';
            }

            function unitQuantityLabel(name, symbol, quantity) {
                var unit = name ? pluralizeUnit(name, quantity) : (symbol || '');
                return quantity + (unit ? ' ' + unit : '');
            }

            function parseMoney(v) {
                var s = (v === undefined || v === null) ? '' : String(v).trim();
                if (s === '') return 0;
                s = s.replace(',', '.');
                var num = parseFloat(s);
                return isNaN(num) ? 0 : num;
            }

            function computeAmounts() {
                var subtotals = cart.reduce(function (acc, item) {
                    return acc + subtotalOf(item);
                }, 0);

                var discount = Math.max(0, parseMoney(discountInput.value));
                var total = Math.round(Math.max(0, subtotals - discount) * 10) / 10;
                var cash = parseMoney(cashInput.value);
                var qr = parseMoney(qrInput.value);
                var paid = cash + qr;
                var fiada = paid < total - 0.05;

                // El vuelto se devuelve del efectivo: nunca se registra un pago mayor
                // al total. "cashApplied" es el efectivo que realmente se guardará.
                var change = fiada ? 0 : Math.round(Math.max(0, paid - total) * 10) / 10;
                var cashApplied = change > 0
                    ? Math.max(0, Math.round((total - qr) * 10) / 10)
                    : cash;

                return {
                    total: total,
                    paid: paid,
                    cash: cash,
                    qr: qr,
                    cashApplied: cashApplied,
                    change: change,
                    fiada: fiada,
                    debt: fiada ? Math.max(0, total - paid) : 0,
                };
            }

            function makeQtyInput(item) {
                var qty = document.createElement('input');
                qty.type = 'number';
                qty.min = '1';
                qty.step = '1';
                qty.value = item.quantity;
                qty.className = inputClass;
                qty.addEventListener('change', function () {
                    item.quantity = Math.max(1, parseInt(qty.value, 10) || 1);
                    render();
                });
                return qty;
            }

            function makeRemoveButton(index) {
                var button = document.createElement('button');
                button.type = 'button';
                button.textContent = 'Quitar';
                button.className = removeClass;
                button.addEventListener('click', function () {
                    cart.splice(index, 1);
                    render();
                });
                return button;
            }

            function render() {
                if (tbody) { tbody.innerHTML = ''; }
                if (cards) { cards.innerHTML = ''; }
                if (hidden) { hidden.innerHTML = ''; }

                cart.forEach(function (item, index) {
                    var p = byId[item.presentation_id];
                    var subtotal = subtotalOf(item);

                    // Fila (escritorio)
                    var tr = document.createElement('tr');
                    tr.className = 'border-t border-gray-100';

                    var tdQty = document.createElement('td');
                    tdQty.className = 'px-4 py-2';
                    var qtyWrap = document.createElement('div');
                    qtyWrap.className = 'ml-auto w-24';
                    qtyWrap.appendChild(makeQtyInput(item));
                    tdQty.appendChild(qtyWrap);

                    var tdDesc = document.createElement('td');
                    tdDesc.className = 'px-4 py-3';
                    var descName = document.createElement('p');
                    descName.className = 'mb-0 font-medium text-gray-800';
                    descName.textContent = p.label || p.productName || '';
                    var descEquiv = document.createElement('p');
                    descEquiv.className = 'mb-0 mt-0.5 text-xs text-gray-500';
                    descEquiv.textContent = 'Equivale a ' + unitQuantityLabel(p.baseUnitName, p.baseUnitSymbol, baseQuantityOf(item));
                    tdDesc.appendChild(descName);
                    tdDesc.appendChild(descEquiv);

                    var tdPrice = document.createElement('td');
                    tdPrice.className = 'px-4 py-3 text-right tabular-nums text-gray-600';
                    tdPrice.textContent = money(p.price);

                    var tdSubtotal = document.createElement('td');
                    tdSubtotal.className = 'px-4 py-3 text-right font-medium tabular-nums text-gray-800';
                    tdSubtotal.textContent = money(subtotalOf(item));

                    var tdRemove = document.createElement('td');
                    tdRemove.className = 'px-4 py-2';
                    var removeWrap = document.createElement('div');
                    removeWrap.className = 'flex justify-end';
                    removeWrap.appendChild(makeRemoveButton(index));
                    tdRemove.appendChild(removeWrap);

                    tr.appendChild(tdQty);
                    tr.appendChild(tdDesc);
                    tr.appendChild(tdPrice);
                    tr.appendChild(tdSubtotal);
                    tr.appendChild(tdRemove);
                    tbody.appendChild(tr);

                    // Tarjeta (móvil)
                    var card = document.createElement('div');
                    card.className = 'rounded-xl border border-gray-200 bg-white p-4 shadow-sm';

                    var header = document.createElement('div');
                    header.className = 'flex items-start justify-between gap-3';
                    var title = document.createElement('p');
                    title.className = 'mb-0 font-semibold text-gray-800';
                    title.textContent = p.label || p.productName || '';
                    header.appendChild(title);
                    card.appendChild(header);

                    var meta = document.createElement('div');
                    meta.className = 'mt-2 flex items-end justify-between gap-3';

                    var priceWrap = document.createElement('div');
                    var qtyLine = document.createElement('p');
                    qtyLine.className = 'mb-0 text-sm text-gray-500';
                    qtyLine.textContent = 'Cant.: ' + item.quantity + ' ' + (p.unitName || p.unitSymbol || '');
                    var equivLine = document.createElement('p');
                    equivLine.className = 'mb-0 mt-0.5 text-sm text-gray-500';
                    equivLine.textContent = 'Equivale a ' + unitQuantityLabel(p.baseUnitName, p.baseUnitSymbol, baseQuantityOf(item));
                    var priceLine = document.createElement('p');
                    priceLine.className = 'mb-0 mt-0.5 text-sm text-gray-500';
                    priceLine.innerHTML = 'Precio: <span class="tabular-nums text-gray-800">' + money(p.price) + '</span>';
                    var subtotalLine = document.createElement('p');
                    subtotalLine.className = 'mb-0 mt-0.5 text-sm text-gray-500';
                    subtotalLine.innerHTML = 'Subtotal: <span class="font-semibold tabular-nums text-gray-800">' + money(subtotal) + '</span>';
                    priceWrap.appendChild(qtyLine);
                    priceWrap.appendChild(equivLine);
                    priceWrap.appendChild(priceLine);
                    priceWrap.appendChild(subtotalLine);

                    var cardActions = document.createElement('div');
                    cardActions.className = 'flex justify-end';
                    cardActions.appendChild(makeRemoveButton(index));

                    meta.appendChild(priceWrap);
                    meta.appendChild(cardActions);
                    card.appendChild(meta);

                    cards.appendChild(card);

                    // Inputs ocultos enviados al servidor
                    var pid = document.createElement('input');
                    pid.type = 'hidden';
                    pid.name = 'details[' + index + '][presentation_id]';
                    pid.value = item.presentation_id;
                    hidden.appendChild(pid);

                    var hiddenQty = document.createElement('input');
                    hiddenQty.type = 'hidden';
                    hiddenQty.name = 'details[' + index + '][quantity]';
                    hiddenQty.value = item.quantity;
                    hidden.appendChild(hiddenQty);
                });

                if (empty) {
                    empty.hidden = cart.length > 0;
                }

                var amounts = computeAmounts();

                totalDisplay.textContent = money(amounts.total);
                debtDisplay.textContent = money(amounts.debt);
                changeDisplay.textContent = money(amounts.change);
                statusDisplay.textContent = amounts.fiada ? 'fiada' : 'pagada';
                statusDisplay.className = amounts.fiada ? statusDebtClass : statusPaidClass;

                if (appliedCashNote) {
                    if (amounts.change > 0) {
                        appliedCashNote.textContent = 'Se registrarán ' + money(amounts.cashApplied) + ' en efectivo; el vuelto de ' + money(amounts.change) + ' se devuelve al cliente.';
                        appliedCashNote.classList.remove('hidden');
                    } else {
                        appliedCashNote.classList.add('hidden');
                    }
                }

                nameInput.required = amounts.fiada;
                phoneInput.required = amounts.fiada;
            }

            function hideResults() {
                if (resultsList) {
                    resultsList.innerHTML = '';
                    resultsList.classList.add('hidden');
                }
                visible = [];
                highlight = -1;
                if (searchInput) {
                    searchInput.setAttribute('aria-expanded', 'false');
                    searchInput.removeAttribute('aria-activedescendant');
                }
            }

            function matchPresentation(presentation, query) {
                var label = (presentation.label || '').toLowerCase();
                var barcode = (presentation.barcode || '').toLowerCase();

                return label.indexOf(query) !== -1 || (barcode !== '' && barcode.indexOf(query) !== -1);
            }

            function exactBarcodeId(query) {
                var match = presentations.filter(function (presentation) {
                    return (presentation.barcode || '').toLowerCase() === query;
                })[0];

                return match ? match.id : null;
            }

            function exactLabelId(query) {
                var match = presentations.filter(function (presentation) {
                    var label = (presentation.label || '').toLowerCase().trim();
                    return label === query;
                })[0];

                return match ? match.id : null;
            }

            function refreshResults() {
                if (!resultsList || !searchInput) { return; }

                var query = (searchInput.value || '').toLowerCase().trim();
                visible = query === '' ? [] : presentations.filter(function (presentation) {
                    return matchPresentation(presentation, query);
                });

                if (highlight >= visible.length) { highlight = visible.length - 1; }
                if (highlight < 0) { highlight = visible.length ? 0 : -1; }

                resultsList.innerHTML = '';

                if (query === '') {
                    hideResults();
                    return;
                }

                var autoId = exactBarcodeId(query) || exactLabelId(query);
                if (autoId !== null) {
                    hideResults();
                    addById(autoId);
                    return;
                }

                if (!visible.length) {
                    var none = document.createElement('li');
                    none.setAttribute('role', 'presentation');
                    none.className = 'px-4 py-2 text-sm text-gray-400';
                    none.textContent = 'Sin coincidencias';
                    resultsList.appendChild(none);
                    resultsList.classList.remove('hidden');
                    searchInput.setAttribute('aria-expanded', 'true');
                    return;
                }

                var highlightedId = null;

                visible.slice(0, 50).forEach(function (presentation, index) {
                    var li = document.createElement('li');
                    li.id = 'presentation-result-' + presentation.id;
                    li.setAttribute('role', 'option');
                    li.setAttribute('aria-selected', index === highlight ? 'true' : 'false');
                    li.className = 'flex cursor-pointer items-center justify-between gap-3 px-4 py-2 text-sm transition '
                        + (index === highlight ? 'bg-maxxi-orange/10' : 'hover:bg-gray-50');
                    li.addEventListener('mousedown', function (event) { event.preventDefault(); });
                    li.addEventListener('click', function () { addById(presentation.id); });

                    var info = document.createElement('span');
                    info.className = 'min-w-0';

                    var name = document.createElement('span');
                    name.className = 'block truncate font-medium text-gray-900';
                    name.textContent = presentation.label;
                    info.appendChild(name);

                    if (presentation.barcode) {
                        var code = document.createElement('span');
                        code.className = 'block text-xs text-gray-400';
                        code.textContent = 'CB: ' + presentation.barcode;
                        info.appendChild(code);
                    }

                    var price = document.createElement('span');
                    price.className = 'shrink-0 font-semibold tabular-nums text-maxxi-navy';
                    price.textContent = money(presentation.price);

                    li.appendChild(info);
                    li.appendChild(price);
                    resultsList.appendChild(li);

                    if (index === highlight) { highlightedId = li.id; }
                });

                if (highlightedId) {
                    searchInput.setAttribute('aria-activedescendant', highlightedId);
                } else {
                    searchInput.removeAttribute('aria-activedescendant');
                }

                resultsList.classList.remove('hidden');
                searchInput.setAttribute('aria-expanded', 'true');
            }

            function addById(id) {
                if (!byId[id]) { return; }

                var qty = Math.max(1, parseInt(qtyInput && qtyInput.value, 10) || 1);
                var existing = cart.filter(function (item) {
                    return item.presentation_id === id;
                })[0];

                if (existing) {
                    existing.quantity += qty;
                } else {
                    cart.push({ presentation_id: id, quantity: qty });
                }

                if (qtyInput) { qtyInput.value = '1'; }
                if (searchInput) { searchInput.value = ''; }
                hideResults();
                render();

                if (searchInput) { searchInput.focus(); }
            }

            function addCurrentSelection() {
                if (!searchInput) { return; }

                var query = (searchInput.value || '').toLowerCase().trim();
                if (query === '') { return; }

                var id = exactBarcodeId(query) || exactLabelId(query);
                if (id === null && visible.length) {
                    id = visible[Math.max(0, highlight)].id;
                }

                if (id !== null) { addById(id); }
            }

            if (searchInput && resultsList) {
                searchInput.addEventListener('input', function () {
                    highlight = 0;
                    refreshResults();
                });

                searchInput.addEventListener('focus', refreshResults);

                searchInput.addEventListener('keydown', function (event) {
                    if (event.key === 'ArrowDown') {
                        if (visible.length) {
                            event.preventDefault();
                            highlight = Math.min(highlight + 1, visible.length - 1);
                            refreshResults();
                        }
                    } else if (event.key === 'ArrowUp') {
                        if (visible.length) {
                            event.preventDefault();
                            highlight = Math.max(highlight - 1, 0);
                            refreshResults();
                        }
                    } else if (event.key === 'Enter') {
                        event.preventDefault();
                        addCurrentSelection();
                    } else if (event.key === 'Escape') {
                        hideResults();
                    }
                });
            }

            if (searchWrap) {
                document.addEventListener('click', function (event) {
                    if (!searchWrap.contains(event.target)) { hideResults(); }
                });
            }

            if (addButton) {
                addButton.addEventListener('click', addCurrentSelection);
            }

            discountInput.addEventListener('input', render);
            cashInput.addEventListener('input', render);
            qrInput.addEventListener('input', render);

            // "Pago completo": coloca en efectivo el total calculado.
            var fullPaymentButton = document.getElementById('cash-full-payment');

            if (fullPaymentButton) {
                fullPaymentButton.addEventListener('click', function () {
                    var amounts = computeAmounts();
                    cashInput.value = amounts.total.toFixed(1);
                    render();
                });
            }

            var bypassConfirm = false;
            var confirmSummary = document.getElementById('confirm-summary');
            var fiadaSummary = document.getElementById('fiada-summary');
            var confirmButton = document.getElementById('confirm-sale-button');
            var fiadaButton = document.getElementById('confirm-fiada-button');

            // Abre el modal de confirmación correspondiente: uno específico cuando la
            // venta queda "fiada" y el genérico cuando queda "pagada".
            function openConfirmModal() {
                var amounts = computeAmounts();

                if (!window.Alpine) {
                    // Respaldo por si el modal no está disponible.
                    var fallback = amounts.fiada
                        ? 'Vas a registrar una venta FIADA con un fio de ' + money(amounts.debt) + '.'
                        : 'Vas a registrar la venta por ' + money(amounts.total) + '.';

                    if (window.confirm(fallback + ' ¿Deseas continuar?')) {
                        bypassConfirm = true;
                        form.submit();
                        bypassConfirm = false;
                    }
                    return;
                }

                if (amounts.fiada) {
                    if (fiadaSummary) {
                        fiadaSummary.textContent = 'El cliente quedará con un fio pendiente de ' + money(amounts.debt) + '.';
                    }
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-fiada' }));
                    return;
                }

                if (confirmSummary) {
                    var message = 'Vas a registrar la venta por ' + money(amounts.total) + '.';

                    if (amounts.change > 0) {
                        message += ' Se devolverá un vuelto de ' + money(amounts.change) + ' y se registrarán ' + money(amounts.cashApplied) + ' en efectivo.';
                    }

                    confirmSummary.textContent = message + ' Se descontará el stock de las presentaciones.';
                }
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-sale' }));
            }

            form.addEventListener('submit', function (event) {
                if (bypassConfirm) { return; }

                event.preventDefault();
                openConfirmModal();
            });

            // Ambos modales (pagada y fiada) confirman enviando el formulario.
            function submitConfirmedSale() {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirm-sale' }));
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirm-fiada' }));

                bypassConfirm = true;

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }

                bypassConfirm = false;
            }

            if (confirmButton) {
                confirmButton.addEventListener('click', submitConfirmedSale);
            }

            if (fiadaButton) {
                fiadaButton.addEventListener('click', submitConfirmedSale);
            }

            render();
        })();
    </script>
</x-layouts.sistema>
