# AGENTS.md — Maxxi Laravel

Guía de contexto, reglas y arquitectura para agentes de IA que trabajen en este proyecto.
Leer antes de modificar cualquier archivo.

---

## 1. Descripción del proyecto

- **Nombre:** Maxxi Laravel
- **Tipo:** Aplicación web Laravel de inventario y ventas tipo punto de venta, con
  compras, notas de venta (preventa/pedido), alistamiento (picking) y reportes.
- **Idioma de trabajo:** español (nombres de dominio, mensajes, vistas y comunicación
  con el usuario).
- **Estado:** funcional y en evolución. Existen migraciones, modelos, seeders,
  controllers, Form Requests, servicios de dominio, gates por rol, middleware de
  seguridad, vistas Blade completas y pruebas de autenticación/usuarios.

---

## 2. Stack y entorno

| Ítem | Valor |
|---|---|
| PHP | **`^8.4`** (según `composer.json`) |
| Framework | Laravel Framework **12.69.3** |
| Base de datos (local) | **PostgreSQL** (`DB_CONNECTION=pgsql`, base `maxxi_laravel`) |
| Base de datos (tests) | **SQLite `:memory:`** (ver `phpunit.xml`) |
| Auth | Laravel Breeze |
| Testing | Pest **4.7** (`pestphp/pest`, `pest-plugin-laravel`) |
| Formato | Laravel Pint (`laravel/pint`) |
| Frontend | Vite 7 + Tailwind + Alpine.js |
| Escaneo / mapas | `html5-qrcode`, `leaflet` |
| PDF | `barryvdh/laravel-dompdf` 3.1 |
| Excel | `phpoffice/phpspreadsheet` 5.10 |

> **Importante (versión de Laravel):** Laravel 12.69.3 **NO** tiene `$table->check()`
> en `Blueprint`. Ver §8.1 para el patrón correcto de constraints `CHECK`.

> **Importante (PostgreSQL vs SQLite):** el entorno local es PostgreSQL, pero los tests
> corren en SQLite en memoria. Hay SQL específico de PostgreSQL (`ilike`, `::numeric`)
> que **no** está cubierto por la suite. Ver §9.

---

## 3. Arquitectura del dominio

Cadena principal de inventario/ventas/compras:

```
Unit ──< Product ──< ProductPresentation ──< SaleDetail >── Sale >── User
                          │                     │
                          │                     └──< SaleNoteDetail >── SaleNote >── User
                          │                                              └──> Sale (al entregar)
                          └──< PurchaseDetail >── Purchase >── User
```

- Las **notas de venta** son preventas/pedidos: no mueven stock. Al entregarse se
  materializan en una **venta** (`sale_notes.sale_id`) y recién entonces se descuenta
  stock.
- Las **compras** incrementan stock y actualizan el costo de referencia de la
  presentación comprada (que a su vez recalcula el costo por unidad base y los precios
  de venta).

### 3.1 Modelos y tablas

| Modelo | Tabla | Notas |
|---|---|---|
| `Unit` | `units` | unidades de medida |
| `Product` | `products` | producto base, ligado a una unidad base; guarda `stock` y `base_unit_cost` |
| `ProductPresentation` | `product_presentations` | empaques/presentaciones de un producto |
| `Sale` | `sales` | venta (cabecera) |
| `SaleDetail` | `sale_details` | detalle de venta |
| `Purchase` | `purchases` | compra (cabecera) |
| `PurchaseDetail` | `purchase_details` | detalle de compra |
| `SaleNote` | `sale_notes` | nota de venta / preventa / pedido |
| `SaleNoteDetail` | `sale_note_details` | detalle de nota, con baja lógica y contador de picking |
| `User` | `users` | usuario (encargado/empleado), con baja lógica y horario de acceso |

### 3.2 Relaciones

- `Unit::products()` → `hasMany(Product, 'base_unit_id')`
- `Unit::presentations()` → `hasMany(ProductPresentation, 'unit_id')`
- `Product::baseUnit()` → `belongsTo(Unit, 'base_unit_id')`
- `Product::presentations()` → `hasMany(ProductPresentation, 'product_id')`
- `ProductPresentation::product()` / `unit()` → `belongsTo`
- `ProductPresentation::saleDetails()` / `saleNoteDetails()` → `hasMany`
- `Sale::user()` → `belongsTo(User, 'user_id')`
- `Sale::saleDetails()` → `hasMany(SaleDetail, 'sale_id')`
- `Sale::saleNotes()` → `hasMany(SaleNote, 'sale_id')`
- `SaleDetail::sale()` / `presentation()` → `belongsTo`
- `Purchase::user()` → `belongsTo(User, 'user_id')`
- `Purchase::purchaseDetails()` → `hasMany(PurchaseDetail, 'purchase_id')`
- `PurchaseDetail::purchase()` / `presentation()` → `belongsTo`
- `SaleNote::user()` / `sale()` → `belongsTo`
- `SaleNote::saleNoteDetails()` → `hasMany(SaleNoteDetail, 'sale_note_id')`
- `SaleNoteDetail::saleNote()` / `presentation()` → `belongsTo`

> **Asimetría conocida:** `User` **no** tiene relaciones inversas (`sales()`,
> `purchases()`, `saleNotes()`).

### 3.3 Esquema resumido de tablas

Todas las tablas de negocio tienen `timestamps()` y `down()` con `Schema::dropIfExists`.

- **units:** `id`, `name` varchar(10), `symbol` varchar(3), `active` bool (default true),
  timestamps.
- **products:** `id`, `name` varchar(20), `base_unit_id` FK→units, `stock` int
  (default 0), `base_unit_cost` decimal(10,2) (default 0), `active` bool (default true),
  timestamps. CHECK: `stock >= 0`, `base_unit_cost >= 0`.
- **product_presentations:** `id`, `product_id` FK→products, `unit_id` FK→units,
  `conversion_factor` int, `purchase_enable` bool, `sale_enable` bool,
  `reference_purchase_cost` decimal(10,2) **nullable**, `desired_profit` decimal(10,1),
  `sale_price` decimal(10,1) **nullable**, `barcode` varchar(20) nullable,
  `presentation_size` enum(`pequeño`,`grande`) nullable, `active` bool (default true),
  timestamps. CHECK: `conversion_factor >= 0`, `reference_purchase_cost >= 0`,
  `desired_profit >= 0`, `sale_price >= 0`.
- **sales:** `id`, `customer_name` varchar(20) nullable, `customer_phone` varchar(10)
  nullable, `sold_at` datetime, `total` decimal(10,1), `status` enum(`pagada`,`fiada`),
  `user_id` FK→users, `sale_discount` decimal(10,1), `cash` decimal(10,1), `qr`
  decimal(10,1), `debt` decimal(10,1), timestamps. CHECK `>= 0` en `total`,
  `sale_discount`, `cash`, `qr`, `debt`.
- **sale_details:** `id`, `sale_id` FK→sales, `presentation_id` FK→product_presentations,
  `quantity` int, `conversion_factor` int, `base_quantity` int, `unit_price`
  decimal(10,1), `base_unit_cost_at_sale` decimal(10,2), `subtotal` decimal(10,1),
  timestamps. CHECK `>= 0` en todas las columnas numéricas.
- **purchases:** `id`, `supplier` varchar(15) **nullable**, `purchased_at` datetime,
  `voucher_number` varchar(8) **nullable**, `total` decimal(10,2), `user_id` FK→users,
  timestamps. CHECK: `total >= 0`.
- **purchase_details:** `id`, `purchase_id` FK→purchases, `presentation_id`
  FK→product_presentations, `quantity` int, `conversion_factor` int, `base_quantity`
  int, `unit_price` decimal(10,2), `subtotal` decimal(10,2), timestamps. CHECK `>= 0`.
- **sale_notes:** `id`, `customer_name` varchar(20) nullable, `customer_phone`
  varchar(10) nullable, `noted_at` datetime, `scheduled_for` datetime nullable,
  `delivered_at` datetime nullable, `delivery_latitude` decimal(10,7) nullable,
  `delivery_longitude` decimal(10,7) nullable, `sale_discount` decimal(10,1), `total`
  decimal(10,1), `status` enum(`pendiente`,`entregada`,`anulada`) default `pendiente`,
  `sale_id` FK→sales nullable, `user_id` FK→users, timestamps. CHECK: `sale_discount >= 0`,
  `total >= 0`.
- **sale_note_details:** `id`, `sale_note_id` FK→sale_notes, `presentation_id`
  FK→product_presentations, `quantity` int, `conversion_factor` int, `base_quantity`
  int, `unit_price` decimal(10,1), `base_unit_cost_at_note` decimal(10,2), `subtotal`
  decimal(10,1), `active` bool (default true), `picked_quantity` int (default 0),
  timestamps. CHECK `>= 0` en todas las numéricas.
- **users:** `id`, `name`, `email` unique, `tipo` enum(`encargado`,`empleado`) default
  `empleado`, `active` bool (default true), `access_start_time` time nullable,
  `access_end_time` time nullable, `email_verified_at`, `password`, `remember_token`,
  timestamps. También existen `password_reset_tokens` y `sessions`.

> `sales.status` se llama `status` (no `estatus`). Valores válidos: `pagada`, `fiada`.

---

## 4. Roles, permisos y seguridad

### 4.1 Tipos de usuario

`App\Enums\UserType` (backed enum string), casteado en `User::$casts`:

- `UserType::Encargado` (`encargado`) → acceso total.
- `UserType::Empleado` (`empleado`) → operación restringida.

Helpers en `User`: `isEncargado()`, `isEmpleado()`.

### 4.2 Gates (`app/Providers/AppServiceProvider.php`)

Todos los gates son **exclusivos del encargado**:

| Gate | Cubre |
|---|---|
| `manage-catalog` | unidades, productos y presentaciones (crear/editar/stock/bajas) |
| `manage-sales` | listar/ver/editar ventas y cobrar fiadas |
| `manage-sale-notes` | todo en notas de venta salvo `show` |
| `manage-purchases` | listar/ver/editar compras |
| `view-reports` | reportes y exportaciones |
| `manage-users` | CRUD y activación/desactivación de usuarios |

`sale-preparations` (picking) **no** tiene gate: ambos roles.

### 4.3 Matriz real de acceso (según rutas)

| Módulo | Empleado | Encargado |
|---|---|---|
| Catálogo (`units`, `products`, `product-presentations`) | solo `index`/`show` | todo |
| Ventas | solo `create`/`store`; `ticket` sin gate | todo + `cobrar` |
| Compras | solo `create`/`store` | todo |
| Notas de venta | solo `show` | todo + `entregar` + detalles |
| Preparación/picking | todo (sin gate) | todo |
| Reportes | sin acceso | todo |
| Usuarios | sin acceso | todo |

> **Ojo:** los comentarios de `routes/web.php` dicen que el empleado “ve” ventas y
> compras, pero las rutas `sales.index`, `sales.show`, `purchases.index` y
> `purchases.show` están dentro de `can:manage-sales` / `can:manage-purchases`. El
> código manda: el empleado **no** puede listar ni ver ventas/compras. Ver §10.

### 4.4 Restricciones de negocio para el empleado

- **No** aplica descuento en ventas: el `sale_discount` enviado se fuerza a `0`.
- **No** registra ventas fiadas: si el pago no cubre el total, se rechaza con error.
- **No** edita ventas ni cobra fiadas.
- **No** crea/edita/dar de baja catálogo ni ajusta stock.

### 4.5 Middleware de seguridad

Registrados globalmente en `bootstrap/app.php` para el grupo `web`:

1. `EnsureUserIsActive` → cierra sesión si `active === false` (403 JSON o redirect a
   login).
2. `EnsureWithinAccessSchedule` → cierra sesión del empleado fuera de su horario
   (403 JSON o redirect a login).

Además, `LoginRequest::authenticate()` valida `active => true` y el horario **antes**
de dejar la sesión iniciada. `bootstrap/app.php` también configura
`trustProxies(at: '*')`.

### 4.6 Horario de acceso

- Opcional: si `access_start_time` o `access_end_time` es null, no restringe.
- Deben venir ambos; la salida debe ser **estrictamente posterior** a la entrada (no se
  permiten rangos que crucen medianoche).
- El fin del rango es **exclusivo** (07:00–19:00 permite hasta 18:59:59).
- Los **encargados** nunca están sujetos al horario.

### 4.7 Contraseñas

Política global en `AppServiceProvider`:
`Password::defaults(fn () => Password::min(8)->letters()->numbers())`.
Aplica a perfil, restablecimiento y CRUD de usuarios.

---

## 5. Reglas de negocio

### 5.1 Ventas (`SaleController`)

- El carrito vive dentro de la venta; no hay recurso `sale-details` expuesto.
- `total = max(0, suma(subtotales) - sale_discount)` (1 decimal).
- `status` se deriva: `pagada` si `cash + qr >= total - 0.05`; `fiada` en caso
  contrario.
- `debt = total - (cash + qr)` cuando es fiada; `0` cuando es pagada.
- **Vuelto:** si el pago supera el total, `cash`/`qr` se ajustan para que
  `cash + qr == total`; el excedente no se guarda.
- Si queda `fiada`, `customer_name` y `customer_phone` son obligatorios.
- Venta + detalles + descuento de stock ocurren en una única transacción.
- `sold_at` se recibe del formulario; el cajero (`user_id`) sale de la sesión.
- `cobrar`: suma cobros a `cash`/`qr` y recalcula `status`/`debt`; solo aplica a
  ventas `fiada`.
- `destroy` responde **405** (las ventas no se eliminan).

### 5.2 Notas de venta (`SaleNoteService`)

- **No mueven stock** al crearse ni al editarse.
- El `unit_price`, `conversion_factor`, `base_quantity` y `base_unit_cost_at_note` se
  derivan del catálogo al registrar la línea y quedan como histórico.
- `total = max(0, suma(subtotales activos) - sale_discount)`.
- Solo se modifican notas **pendientes** (`ensurePending`).
- Las líneas se quitan/reactivan con `active` (baja lógica) y el total se recalcula.
- `addDetail`: si la presentación ya tiene línea activa, suma cantidad; si no, crea
  línea nueva.
- `entregar()`: en una transacción, copia las líneas **activas** a una venta, descuenta
  stock, captura pago (`received_cash`/`received_qr`), deriva estado/deuda con la misma
  lógica que la venta directa y marca la nota `entregada` con `delivered_at` y `sale_id`.
- `destroy` = **anular** (estado `anulada`), no borrar.

### 5.3 Compras y costos (`PurchaseService`, `ProductPresentationService`)

- Compra + detalles + incremento de stock + actualización de costo ocurren en una
  transacción.
- `total` = suma de subtotales (2 decimales).
- El `unit_price` ingresado pasa a ser el `reference_purchase_cost` de la presentación
  comprada.
- `base_unit_cost` del producto = `reference_purchase_cost / conversion_factor`.
- `sale_price` de cada presentación activa con `sale_enable` se recalcula como
  `costo_unitario_base * conversion_factor + desired_profit`, con redondeo especial:
  decimal `< 0.3` → hacia abajo; `0.3–0.6` → `N.5`; `> 0.6` → `N+1`.
- Solo puede existir **una** presentación `purchase_enable + active` por producto
  (validado en el servicio, no en la base).
- `update` de compra solo modifica la cabecera; las líneas son históricas.

### 5.4 Stock (`StockService`)

- Se guarda en **unidad base** del producto.
- Venta y entrega de nota **decrementan** stock de forma atómica y condicional
  (`WHERE stock >= cantidad`); si no alcanza, se lanza `ValidationException` y se
  revierte la transacción.
- Compra **incrementa** stock.
- Los bloqueos se ordenan por `product_id` ascendente para evitar interbloqueos.
- El ajuste manual de stock (`PATCH products/{product}/stock`) se expresa en
  `stock_packages * conversion_factor + stock_loose` usando la presentación de compra
  activa.

### 5.5 Alistamiento / picking (`SalePreparationService`)

- Solo notas `pendiente` y líneas activas de presentaciones `presentation_size =
  pequeño`.
- `picked_quantity` se incrementa con cada escaneo del `barcode`, sin superar
  `quantity`.
- `unscan` resta 1 sin bajar de 0; `complete` iguala `picked_quantity = quantity`;
  `reset` lo pone en 0.
- No mueve stock.

### 5.6 Bajas lógicas y estados

- Entidades con `active` **no se eliminan físicamente jamás**: `units`, `products`,
  `product-presentations`, `sale_note_details` y `users`.
- `destroy()` de `units`/`products`/`product-presentations` = `active = false`;
  `restore()` = `active = true`.
- Usuarios: `PATCH users/{user}/activate` y `PATCH users/{user}/deactivate` (no hay
  `destroy`). Al desactivar se limpia `remember_token` y se borran sus filas de
  `sessions`.
- Notas de venta: la baja es por **estado** (`anulada`).
- Ventas: no se eliminan (405).
- Compras: no deberían eliminarse (no hay baja lógica). Ver §10.

---

## 6. Capa de servicios (`app/Services/`)

| Servicio | Responsabilidad |
|---|---|
| `StockService` | incremento/decremento atómico de stock |
| `ProductPresentationService` | unicidad de presentación de compra, costo base y recálculo de precios |
| `PurchaseService` | alta de compra + stock + costo |
| `SaleNoteService` | alta/edición/anulación/entrega de notas, totales y pagos |
| `SalePreparationService` | hoja de preparación y picking |
| `ReportService` | resúmenes económicos, por día, por usuario, top productos y stock bajo |

La lógica de la **venta directa** sigue en `SaleController` (no en un servicio). Al
tocar reglas de ventas, revisar que no se dupliquen/desincronicen con `SaleNoteService`
(comparten `resolveAmounts`, `resolveStatus` y `ensureCustomerForFiada`).

---

## 7. Convenciones de código

### 7.1 Modelos (`app/Models/`)

- Clase en **singular** y PascalCase.
- **No** declarar `$table` (la convención funciona).
- `$fillable` incluye **siempre las claves foráneas**.
- `casts()` como método (estilo Laravel 12):
  - booleanos → `'boolean'`
  - decimales → misma precisión que la migración (`'decimal:1'`, `'decimal:2'`,
    `'decimal:7'`)
  - fechas → `'datetime'`
  - `User::tipo` → `UserType::class`
- Relaciones con import explícito del tipo (`BelongsTo`, `HasMany`) y FK explícita
  cuando no sigue la convención.

### 7.2 Migraciones (`database/migrations/`)

- Orden cronológico con prefijo de fecha. Las de negocio usan
  `2026_10_08_00000X_...`; las de usuarios usan fechas posteriores.
- `$table->id()` para PK; `$table->foreignId('x_id')->constrained('tabla')` para FKs.
- “Money” se implementa como `decimal(10, 1)` o `decimal(10, 2)` según la columna.
- `$table->timestamps()` presente en las tablas de negocio.
- `down()` usa `Schema::dropIfExists(...)` (o `dropColumn` en migraciones `change`).
- **CHECK constraints** con `DB::statement` (ver §8.1).

### 7.3 Controllers (`app/Http/Controllers/`)

- Resource de 7 métodos generado con `php artisan make:controller --resource`.
- **Route-model binding** en `show/edit/update/destroy/restore` (`Unit $unit`).
- Respuestas con `view()` y `redirect()->with('status'|'success'|'errors')`.
- JSON solo en endpoints puntuales: `sale-preparations.*` (scan/unscan/complete/reset),
  `reports/low-stock`, y `destroy` de ventas (405).
- Validación: usar Form Request cuando exista (ver §7.4); en su defecto, validación
  inline con los límites reales de la columna.

### 7.4 Form Requests (`app/Http/Requests/`)

Existen y se usan para:

- `ProductPresentation/Store|UpdateProductPresentationRequest` (+ trait
  `HasPresentationRules` con `dataForPersistence()`).
- `Purchase/Store|UpdatePurchaseRequest` (+ trait `HasPurchaseRules`).
- `SaleNote/Store|Update|DeliverSaleNoteRequest`, `StoreSaleNoteDetailRequest`,
  `UpdateSaleNoteDetailQuantityRequest` (+ trait `HasSaleNoteRules`).
- `ProfileUpdateRequest` y `Auth/LoginRequest`.

Siguen con validación inline: `UnitController`, `ProductController`,
`SaleController`, `UserController`.

Patrón habitual: `authorize(): true` + `dataForPersistence()` para separar validación
de persistencia; `prepareForValidation()` normaliza importes vacíos a `"0"` y campos
opcionales vacíos a `null`.

### 7.5 Seeders (`database/seeders/`)

- `DatabaseSeeder` encadena: `Unit → Product → ProductPresentation → Sale →
  SaleDetail`, y crea/actualiza los usuarios de prueba.
- **Idempotentes**: `updateOrCreate` con clave natural (`symbol`, `name`, `barcode`,
  `customer_name`) y `firstOrCreate` para usuarios.
- **Nunca** `factory()->create()` en `DatabaseSeeder`.
- **Faltan** seeders de `Purchase`, `PurchaseDetail`, `SaleNote` y `SaleNoteDetail`.

### 7.6 Rutas (`routes/web.php`)

- Todo el negocio va dentro de `Route::middleware('auth')`; `require auth.php` al final
  (Breeze).
- Recursos registrados: `units`, `products`, `product-presentations` (sin `index`),
  `sales`, `purchases`, `sale-notes`, además de `users`, `reports` y
  `sale-preparations` (estas últimas con rutas manuales).
- **No existe** el recurso `sale-details` (el detalle se gestiona dentro de la venta).
- Las entidades con `restore` lo declaran **antes** del `resource`:
  ```php
  Route::patch('units/{unit}/restore', [UnitController::class, 'restore'])->name('units.restore');
  Route::resource('units', UnitController::class)->except(['index', 'show']);
  ```
- URIs en **kebab-case**; parámetros de binding en **snake_case**:
  `product-presentations/{product_presentation}`, `sale-notes/{sale_note}`,
  `sale-notes/{sale_note}/details/{sale_note_detail}`.
- **Orden importante:** las rutas literales (`create`) se registran antes que las de
  binding (`{sale}`) cuando comparten prefijo.
- `sales` y `purchases` no tienen `restore`. `sales.destroy` responde 405.
- `purchases.destroy` existe pero su método está vacío (ver §10).
- Total actual: **90 rutas**.

### 7.7 Vistas (`resources/views/`)

- Layout principal: `<x-layouts.sistema title="...">` (incluye `<x-topbar />`).
- Vistas por entidad: `unit/`, `product/`, `product-presentation/`, `sale/`,
  `purchase/`, `sale-note/`, `user/`, `report/`, `sale-preparation/`.
- `sale/ticket.blade.php` es autónoma (impresión térmica 80/58 mm, sin layout).
- Componentes reutilizables en `resources/views/components/` (`topbar`, `modal`,
  botones, inputs, `sale-note/location-picker`, etc.).
- Los permisos se reflejan en la UI con `@can('manage-*')`.
- Al crear o refactorizar vistas, usar el skill **Maxxi UI** para mantener la
  consistencia visual.

### 7.8 Estilo

- **Laravel Pint**: `vendor/bin/pint`.
- PSR-12, docblocks descriptivos en español, nombres de dominio en español.
- Preferir `ValidationException::withMessages()` para errores de regla de negocio.

---

## 8. Reglas y gotchas (leer antes de tocar)

### 8.1 `CHECK` constraints: usar `DB::statement`, NO `$table->check()`

`$table->check()` **no existe** en Laravel 12.69.3. Usar SQL crudo tras
`Schema::create`/`Schema::table`:

```php
DB::statement('ALTER TABLE products ADD CONSTRAINT products_stock_check CHECK (stock >= 0)');
```

Convención de nombre: `<tabla>_<columna>_check`. En PostgreSQL es válido. El `down()`
no necesita eliminar los constraints (el drop de tabla los elimina); si se agrega una
columna con `Schema::table`, el `down()` solo elimina la columna.

### 8.2 Enums

- `sales.status`: `pagada` | `fiada`.
- `sale_notes.status`: `pendiente` | `entregada` | `anulada`.
- `product_presentations.presentation_size`: `pequeño` | `grande`.
- `users.tipo`: enum PHP `UserType` (`encargado` | `empleado`).

Los estados de venta/nota se tratan como string en el código; no hay enum PHP para
ellos. Valor inválido = error de base de datos.

### 8.3 Sin factories (salvo `User`)

Los modelos de negocio no usan `HasFactory` y no existen factories. Por eso los
seeders usan `updateOrCreate`. Si se necesita volumen aleatorio, crear factories
primero.

### 8.4 SQL específico de PostgreSQL

- `ilike` en los filtros de búsqueda de ventas, compras y notas.
- `products.stock::numeric` en `ReportService::lowStock()`.
- Los tests corren en SQLite, así que estas consultas **no** están cubiertas. No
  cambiarlas a algo “portable” sin revisar el comportamiento en PostgreSQL.

### 8.5 Integridad que vive en la aplicación, no en la base

- Una sola presentación de compra activa por producto.
- `picked_quantity <= quantity` (se controla en el servicio; la base solo exige
  `>= 0`).
- `sale_notes.sale_id` se llena al entregar.
- El “último encargado activo” no puede desactivarse ni degradarse.

### 8.6 Zona horaria y fechas

- Se usa `Carbon`/`now()`; los filtros de listado son inclusivos por día.
- Los formularios envían `Y-m-d` y `Y-m-d H:i`.
- El fin del horario de acceso es exclusivo.

---

## 9. Testing

- **Pest** con `RefreshDatabase` en `tests/Feature` (ver `tests/Pest.php`).
- `phpunit.xml` fuerza: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`,
  `SESSION_DRIVER=array`, `MAIL_MAILER=array`, `CACHE_STORE=array`.
- Cobertura actual: autenticación, verificación de email, reset de contraseña,
  confirmación de contraseña, perfil, política de contraseñas, estado de usuario
  (activar/desactivar), horario de acceso (login y middleware).
- **Sin cobertura**: ventas, stock, notas de venta, compras, reportes, picking y
  exportaciones. Es la deuda de testing más importante.
- Al escribir tests de dominio, tener en cuenta que SQLite no soporta `ilike` ni
  `::numeric`; esas rutas necesitarían PostgreSQL o una abstracción.

---

## 10. Deuda técnica conocida

- `app/Http/Controllers/SaleDetailController.php`, `PurchaseDetailController.php` y
  `SaleNoteDetailController.php` existen pero **no están ruteados** (código muerto o
  legado).
- `PurchaseController::destroy()` está vacío, pero la ruta `DELETE purchases/{purchase}`
  sí existe y está protegida por `manage-purchases`: responde 200 sin hacer nada.
- `sales.ticket` y `sale-notes.show` no tienen gate: cualquier usuario autenticado
  puede abrir un ticket o una nota si conoce el id.
- Los comentarios de `routes/web.php` sobre lo que “ve” el empleado en ventas/compras
  no coinciden con los gates reales (§4.3).
- `routes/web.php` anida un `Route::middleware('auth')` dentro del grupo `auth`
  (redundante, inofensivo).
- La lógica de importes/estado de venta está duplicada entre `SaleController` y
  `SaleNoteService`.
- No hay seeders de compras ni de notas de venta.
- `User` no tiene relaciones inversas (`sales()`, `purchases()`, `saleNotes()`).
- `database/database.sqlite` existe en el repo, pero el entorno local usa PostgreSQL.
- No existe `routes/api.php` (Laravel 12 no lo crea por defecto).

---

## 11. Comandos útiles

```bash
# Base de datos
php artisan db:seed                # solo siembra (idempotente)
php artisan migrate:status
# NO ejecutar `php artisan migrate:fresh`, `migrate:refresh` o `migrate:reset`
# sin permiso explícito del usuario.

# Calidad
php -l <archivo.php>               # lint de sintaxis
vendor/bin/pint                    # formato
php artisan test                   # pruebas Pest (SQLite en memoria)

# Rutas
php artisan route:list             # inventario real de rutas (90)
php artisan route:list --path=sales -v

# Pruebas manuales de lógica (tinker, con rollback para no dejar datos)
php artisan tinker --execute="..."
```

Credenciales de prueba (tras `db:seed`), contraseña `password`:

- `test@example.com` → encargado
- `cajero@example.com` → empleado

---

## 12. Flujo de trabajo recomendado (Git)

1. **AUDITAR** el estado actual antes de cambiar.
2. **VERIFICAR CONSUMIDORES** (¿quién usa lo que vas a tocar?).
3. **CLASIFICAR RIESGO** de la modificación.
4. **DECIDIR** si está justificada.
5. **MODIFICAR SOLO SI ESTÁ JUSTIFICADO.**
6. **PROBAR** (`php -l`, `migrate`, `tinker` con rollback, `pest`, revisar rutas).
7. **REVISAR DIFF** (incluir `git diff --check`).
8. **COMMIT.**
9. **PUSH.**

Reglas:

- Las fases marcadas como **READ-ONLY** no deben modificar archivos.
- No hacer commit/push si una fase lo prohíbe expresamente.
- Los artefactos de auditoría en `/tmp/opencode/` son temporales y **no** forman parte
  del repositorio.
- `clasp push` **no aplica** a este proyecto (es Laravel, no Google Apps Script).
- Si se cambia el esquema, actualizar este documento y las migraciones en el mismo
  cambio.

---

## 13. Forma de trabajar con el usuario

- El usuario da instrucciones en español, a menudo concisas; ejecutar exactamente lo
  pedido, sin ampliar el alcance.
- Si una instrucción es ambigua o implica una decisión de diseño con impacto,
  **preguntar antes**.
- Al terminar una tarea: explicar qué se hizo, decisiones tomadas y cómo se probó.
- No modificar ni eliminar trabajo previo del usuario sin confirmar.
- **Antes de cambiar la versión de PHP o dependencias del stack, preguntar
  explícitamente.**
