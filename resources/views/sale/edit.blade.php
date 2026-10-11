<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar venta</title>
</head>
<body>
    <h1>Editar venta</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form id="sale-form" action="{{ route('sales.update', $sale) }}" method="POST">
        @csrf
        @method('PUT')

        <p>
            <label for="customer_name">Cliente (obligatorio si la venta queda fiada)</label>
            <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', $sale->customer_name) }}" maxlength="20">
        </p>

        <p>
            <label for="customer_phone">Teléfono (obligatorio si la venta queda fiada)</label>
            <input type="text" id="customer_phone" name="customer_phone" value="{{ old('customer_phone', $sale->customer_phone) }}" maxlength="10">
        </p>

        <p>
            <label for="sold_at">Fecha de venta</label>
            <input type="datetime-local" id="sold_at" name="sold_at" value="{{ old('sold_at', $sale->sold_at?->format('Y-m-d\TH:i')) }}" required>
        </p>

        <p>
            <small>El estado (pagada/fiada), el fio y el cajero se determinan automáticamente al guardar.</small>
        </p>

        <p>
            <label for="sale_discount">Descuento</label>
            <input type="number" id="sale_discount" name="sale_discount" value="{{ old('sale_discount', $sale->sale_discount) }}" step="0.1" min="0" required>
        </p>

        <p>
            <label for="cash">Efectivo</label>
            <input type="number" id="cash" name="cash" value="{{ old('cash', $sale->cash) }}" step="0.1" min="0" required>
        </p>

        <p>
            <label for="qr">QR</label>
            <input type="number" id="qr" name="qr" value="{{ old('qr', $sale->qr) }}" step="0.1" min="0" required>
        </p>

        <p>
            <strong>Total (calculado): <span id="total-display">{{ number_format((float) $sale->total, 1) }}</span></strong>
            <small>Se recalcula a partir de los detalles y el descuento.</small>
        </p>

        <p>
            <strong>Estado: <span id="status-display">{{ $sale->status }}</span></strong>
            <small>Si efectivo + QR es menor que el total, se marca como FIADA.</small>
        </p>

        <p>
            <strong>Fio (deuda): <span id="debt-display">{{ number_format((float) $sale->debt, 1) }}</span></strong>
            <small>Se calcula como total - (efectivo + QR).</small>
        </p>

        <p>
            <button type="submit">Actualizar</button>
            <a href="{{ route('sales.index') }}">Cancelar</a>
        </p>
    </form>

    <script>
        (function () {
            var subtotals = {{ (float) $subtotals }};

            var form = document.getElementById('sale-form');
            var discountInput = document.getElementById('sale_discount');
            var cashInput = document.getElementById('cash');
            var qrInput = document.getElementById('qr');
            var nameInput = document.getElementById('customer_name');
            var phoneInput = document.getElementById('customer_phone');
            var totalDisplay = document.getElementById('total-display');
            var debtDisplay = document.getElementById('debt-display');
            var statusDisplay = document.getElementById('status-display');

            function money(n) {
                return (Math.round(n * 10) / 10).toFixed(1);
            }

            function computeAmounts() {
                var total = Math.round(Math.max(0, subtotals - (parseFloat(discountInput.value) || 0)) * 10) / 10;
                var paid = (parseFloat(cashInput.value) || 0) + (parseFloat(qrInput.value) || 0);
                var fiada = paid < total - 0.05;

                return {
                    total: total,
                    paid: paid,
                    fiada: fiada,
                    debt: fiada ? Math.max(0, total - paid) : 0,
                };
            }

            function render() {
                var amounts = computeAmounts();

                totalDisplay.textContent = money(amounts.total);
                debtDisplay.textContent = money(amounts.debt);
                statusDisplay.textContent = amounts.fiada ? 'fiada' : 'pagada';

                nameInput.required = amounts.fiada;
                phoneInput.required = amounts.fiada;
            }

            discountInput.addEventListener('input', render);
            cashInput.addEventListener('input', render);
            qrInput.addEventListener('input', render);

            form.addEventListener('submit', function (event) {
                var amounts = computeAmounts();

                if (amounts.fiada) {
                    var confirmed = window.confirm(
                        'La venta quedará marcada como FIADA por un fio de ' + money(amounts.debt) + '. ¿Deseas continuar?'
                    );

                    if (!confirmed) {
                        event.preventDefault();
                    }
                }
            });

            render();
        })();
    </script>
</body>
</html>
