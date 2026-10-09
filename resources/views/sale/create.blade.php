<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva venta</title>
</head>
<body>
    <h1>Nueva venta</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form id="sale-form" action="{{ route('sales.store') }}" method="POST">
        @csrf

        <p>
            <label for="customer_name">Cliente</label>
            <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" maxlength="20" required>
        </p>

        <p>
            <label for="customer_phone">Teléfono</label>
            <input type="text" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="10" required>
        </p>

        <p>
            <label for="sold_at">Fecha de venta</label>
            <input type="datetime-local" id="sold_at" name="sold_at" value="{{ old('sold_at', now()->format('Y-m-d\TH:i')) }}" required>
        </p>

        <p>
            <label for="status">Estado</label>
            <select id="status" name="status" required>
                <option value="pagada" @selected(old('status', 'pagada') === 'pagada')>Pagada</option>
                <option value="fiada" @selected(old('status') === 'fiada')>Fiada</option>
            </select>
        </p>

        <p>
            <label for="user_id">Usuario (cajero)</label>
            <select id="user_id" name="user_id" required>
                <option value="">-- Seleccionar --</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>
        </p>

        <h2>Carrito</h2>

        @if ($presentations->isEmpty())
            <p>No hay presentaciones con venta habilitada.</p>
        @else
            <p>
                <select id="presentation-picker">
                    @foreach ($presentations as $presentation)
                        <option value="{{ $presentation['id'] }}">{{ $presentation['label'] }} — {{ $presentation['price'] }}</option>
                    @endforeach
                </select>
                <input type="number" id="presentation-qty" value="1" min="1" step="1">
                <button type="button" id="add-item">Agregar</button>
            </p>

            <table border="1" cellpadding="6" cellspacing="0">
                <thead>
                    <tr>
                        <th>Presentación</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Quitar</th>
                    </tr>
                </thead>
                <tbody id="cart-body"></tbody>
            </table>
        @endif

        <div id="cart-hidden"></div>

        <h2>Pago</h2>

        <p>
            <label for="sale_discount">Descuento</label>
            <input type="number" id="sale_discount" name="sale_discount" value="{{ old('sale_discount', 0) }}" step="0.1" min="0" required>
        </p>

        <p>
            <label for="cash">Efectivo</label>
            <input type="number" id="cash" name="cash" value="{{ old('cash', 0) }}" step="0.1" min="0" required>
        </p>

        <p>
            <label for="qr">QR</label>
            <input type="number" id="qr" name="qr" value="{{ old('qr', 0) }}" step="0.1" min="0" required>
        </p>

        <p>
            <label for="debt">Fiado (deuda)</label>
            <input type="number" id="debt" name="debt" value="{{ old('debt', 0) }}" step="0.1" min="0" required>
        </p>

        <p>
            <strong>Total: <span id="total-display">0.0</span></strong>
        </p>

        <p>
            <button type="submit">Guardar</button>
            <a href="{{ route('sales.index') }}">Cancelar</a>
        </p>
    </form>

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
            var hidden = document.getElementById('cart-hidden');
            var totalDisplay = document.getElementById('total-display');
            var picker = document.getElementById('presentation-picker');
            var qtyInput = document.getElementById('presentation-qty');
            var discountInput = document.getElementById('sale_discount');
            var addButton = document.getElementById('add-item');

            function money(n) {
                return (Math.round(n * 10) / 10).toFixed(1);
            }

            function subtotalOf(item) {
                return item.quantity * byId[item.presentation_id].price;
            }

            function render() {
                tbody.innerHTML = '';
                hidden.innerHTML = '';

                cart.forEach(function (item, index) {
                    var p = byId[item.presentation_id];

                    var tr = document.createElement('tr');

                    var tdLabel = document.createElement('td');
                    tdLabel.textContent = p.label;

                    var tdPrice = document.createElement('td');
                    tdPrice.textContent = money(p.price);

                    var tdQty = document.createElement('td');
                    var qty = document.createElement('input');
                    qty.type = 'number';
                    qty.min = '1';
                    qty.step = '1';
                    qty.value = item.quantity;
                    qty.addEventListener('change', function () {
                        item.quantity = Math.max(1, parseInt(qty.value, 10) || 1);
                        render();
                    });
                    tdQty.appendChild(qty);

                    var tdSubtotal = document.createElement('td');
                    tdSubtotal.textContent = money(subtotalOf(item));

                    var tdRemove = document.createElement('td');
                    var remove = document.createElement('button');
                    remove.type = 'button';
                    remove.textContent = 'Quitar';
                    remove.addEventListener('click', function () {
                        cart.splice(index, 1);
                        render();
                    });
                    tdRemove.appendChild(remove);

                    tr.appendChild(tdLabel);
                    tr.appendChild(tdPrice);
                    tr.appendChild(tdQty);
                    tr.appendChild(tdSubtotal);
                    tr.appendChild(tdRemove);
                    tbody.appendChild(tr);

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

                var subtotals = cart.reduce(function (acc, item) {
                    return acc + subtotalOf(item);
                }, 0);

                var discount = parseFloat(discountInput.value) || 0;

                totalDisplay.textContent = money(Math.max(0, subtotals - discount));
            }

            if (addButton) {
                addButton.addEventListener('click', function () {
                    var id = parseInt(picker.value, 10);

                    if (!byId[id]) {
                        return;
                    }

                    var qty = Math.max(1, parseInt(qtyInput.value, 10) || 1);
                    var existing = cart.filter(function (item) {
                        return item.presentation_id === id;
                    })[0];

                    if (existing) {
                        existing.quantity += qty;
                    } else {
                        cart.push({ presentation_id: id, quantity: qty });
                    }

                    qtyInput.value = '1';
                    render();
                });
            }

            discountInput.addEventListener('input', render);

            render();
        })();
    </script>
</body>
</html>
