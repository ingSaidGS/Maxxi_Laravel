# AGENTS.md — Maxxi Laravel

Guía de contexto, reglas y arquitectura para agentes de IA que trabajen en este proyecto.
Leer antes de modificar cualquier archivo.

---

## 1. Descripción del proyecto

- **Nombre:** Maxxi Laravel
- **Tipo:** Aplicación web Laravel (sistema de inventario y ventas tipo punto de venta).
- **Idioma de trabajo:** español (nombres de dominio, mensajes y comunicación con el usuario).
- **Estado:** en desarrollo. Migraciones, modelos, seeders y (parcialmente) controllers ya existen.
  Aún **no hay vistas ni rutas** de los recursos de negocio.

---

## 2. Stack y entorno

| Ítem | Valor |
|---|---|
| PHP | `^8.2` |
| Framework | Laravel Framework **12.69.3** |
| Base de datos | **PostgreSQL** (`DB_CONNECTION=pgsql`, base `maxxi_laravel`) |
| Auth | Laravel Breeze |
| Testing | Pest (`pestphp/pest`) |
| Formato | Laravel Pint (`laravel/pint`) |
| Frontend | Vite + Tailwind |

> **Importante (versión):** Laravel 12.69.3 **NO** tiene `$table->check()` en `Blueprint`.
> Ver §6.1 para el patrón correcto de constraints `CHECK`.

---

## 3. Arquitectura del dominio

Cadena principal de inventario/ventas:

```
Unit ──< Product ──< ProductPresentation ──< SaleDetail >── Sale >── User
                          └──< SaleDetail (presentation_id)
```

### 3.1 Modelos y tablas

| Modelo | Tabla | Notas |
|---|---|---|
| `Unit` | `units` | unidades de medida |
| `Product` | `products` | producto base, ligado a una unidad base |
| `ProductPresentation` | `product_presentations` | presentaciones/empaques de un producto |
| `Sale` | `sales` | venta (cabecera) |
| `SaleDetail` | `sale_details` | detalle de venta |
| `User` | `users` | usuario/cajero (Breeze) |

### 3.2 Relaciones (todas bidireccionales)

- `Unit::products()` → `hasMany(Product, 'base_unit_id')`
- `Unit::presentations()` → `hasMany(ProductPresentation, 'unit_id')`
- `Product::baseUnit()` → `belongsTo(Unit, 'base_unit_id')`
- `Product::presentations()` → `hasMany(ProductPresentation, 'product_id')`
- `ProductPresentation::product()` → `belongsTo(Product, 'product_id')`
- `ProductPresentation::unit()` → `belongsTo(Unit, 'unit_id')`
- `ProductPresentation::saleDetails()` → `hasMany(SaleDetail, 'presentation_id')`
- `Sale::user()` → `belongsTo(User, 'user_id')`
- `Sale::saleDetails()` → `hasMany(SaleDetail, 'sale_id')`
- `SaleDetail::sale()` → `belongsTo(Sale, 'sale_id')`
- `SaleDetail::presentation()` → `belongsTo(ProductPresentation, 'presentation_id')`

> `User` todavía **no** tiene la relación inversa `sales()`. Es una asimetría conocida.

### 3.3 Esquema resumido de tablas

- **units:** `id`, `name` varchar(10), `symbol` varchar(3), `active` boolean (default true), timestamps.
- **products:** `id`, `name` varchar(20), `base_unit_id` FK→units, `reference_purchase_cost` decimal(10,2), `active` boolean (default true), timestamps. CHECK `reference_purchase_cost >= 0`.
- **product_presentations:** `id`, `product_id` FK→products, `unit_id` FK→units, `conversion_factor` int, `sale_price` decimal(10,1), `purchase_enable` bool, `sale_enable` bool, `barcode` varchar(20) **nullable**, `active` bool (default true), timestamps. CHECK `conversion_factor >= 0` y `sale_price >= 0`.
- **sales:** `id`, `customer_name` varchar(20), `customer_phone` varchar(10), `sold_at` datetime, `total` decimal(10,1), `status` enum(`pagada`,`fiada`), `user_id` FK→users, `sale_discount` decimal(10,1), `cash` decimal(10,1), `qr` decimal(10,1), `debt` decimal(10,1), timestamps. CHECK `>= 0` en `total`, `sale_discount`, `cash`, `qr`, `debt`.
- **sale_details:** `id`, `sale_id` FK→sales, `presentation_id` FK→product_presentations, `quantity` int, `conversion_factor` int, `sale_enable` bool, `subtotal` decimal(10,1), timestamps. CHECK `>= 0` en `quantity`, `conversion_factor`, `subtotal`.

> Nota: `sales.status` en el código se llama `status` (no `estatus`). Valores válidos: `pagada`, `fiada`.

---

## 4. Convenciones de código

### 4.1 Modelos (`app/Models/`)

- Clase en **singular** y PascalCase (`Unit`, `Product`, `ProductPresentation`, `Sale`, `SaleDetail`).
- **No** declarar `$table` salvo que la convención falle (aquí la convención ya funciona).
- `$fillable` incluye **siempre las claves foráneas** (necesario para `create()` y seeders).
- `casts()` (método, estilo Laravel 12) con:
  - `active`, `purchase_enable`, `sale_enable` → `'boolean'`
  - `decimal` con la **misma precisión que la migración** (`'decimal:2'`, `'decimal:1'`)
  - `sold_at` → `'datetime'`
- Relaciones con import explícito del tipo (`BelongsTo`, `HasMany`) y FK explícita cuando no sigue la convención (p. ej. `base_unit_id`, `presentation_id`).

### 4.2 Migraciones (`database/migrations/`)

- Orden cronológico con prefijo de fecha; las de negocio usan `2026_10_08_00000X_...`.
- `$table->id()` para el PK autoincremental.
- `$table->foreignId('x_id')->constrained('tabla')` para FKs.
- **"money" se implementa como `decimal(10, 2)` o `decimal(10, 1)`** (Laravel no tiene tipo `money` portátil). Precisión según lo pedido por el usuario.
- `$table->timestamps()` presente en todas las tablas de negocio.
- `down()` usa `Schema::dropIfExists(...)`.

### 4.3 Controllers (`app/Http/Controllers/`)

- Generados con `php artisan make:controller <Name> --resource` (esqueleto resource de 7 métodos).
- **Route-model binding** en `show/edit/update/destroy` (`Unit $unit`) en lugar de `string $id`.
- **Validación** con los límites reales de las columnas, p. ej. `name` `max:10`, `symbol` `max:3`.
- Mientras **no existan vistas**, las respuestas son **JSON** (`JsonResponse`):
  - `index` → 200 lista
  - `store` → 201 recurso creado
  - `show`/`edit` → 200 recurso
  - `update` → 200 recurso
  - `destroy` → baja lógica (ver §5)
- Cuando se creen vistas, migrar a `view()` / `redirect()->route(...)`.

### 4.4 Seeders (`database/seeders/`)

- Un seeder por tabla + `DatabaseSeeder` que los encadena en orden de dependencias:
  `Unit → Product → ProductPresentation → Sale → SaleDetail`.
- **Idempotentes**: usar `updateOrCreate` con clave natural (`symbol`, `name`, `barcode`, `customer_name`).
- Creación de usuarios de prueba también idempotente (`firstOrCreate`), **nunca** `factory()->create()` en `DatabaseSeeder`
  (falla por email duplicado al re-sembrar).
- Respetar límites de columnas y los `CHECK >= 0`.

### 4.5 Estilo

- Usar **Laravel Pint** para formato: `vendor/bin/pint`.
- Seguir el estilo del código existente (PSR-12, docblocks descriptivos en español).

---

## 5. Regla clave: eliminación lógica mediante `active`

Las entidades con columna `active` **NO se eliminan físicamente jamás**.

- `destroy()` hace **baja lógica**: `$unit->update(['active' => false])`.
- Alta lógica vía método **`restore()`**: `$unit->update(['active' => true])`.
- `restore()` **no** es un método resource → requiere ruta propia:
  ```php
  Route::patch('units/{unit}/restore', [UnitController::class, 'restore'])->name('units.restore');
  ```
- **No** se usa `SoftDeletes` / `deleted_at` en este proyecto; el flag de baja es `active`.

---

## 6. Reglas y gotchas (leer antes de tocar)

### 6.1 `CHECK` constraints: usar `DB::statement`, NO `$table->check()`

`$table->check()` **no existe** en Laravel 12.69.3 (verificado sobre el vendor). Usar SQL crudo tras `Schema::create`:

```php
DB::statement('ALTER TABLE products ADD CONSTRAINT products_reference_purchase_cost_check CHECK (reference_purchase_cost >= 0)');
```

Convención de nombre: `<tabla>_<columna>_check`. En PostgreSQL es válido. El `down()` no necesita eliminarlos (el drop de tabla los elimina).

### 6.2 Enums

`sales.status` es `enum('pagada','fiada')` en DB. Hoy se trata como string en el modelo; no hay validación de app ni PHP enum. Valor inválido = error de DB.

### 6.3 Sin factories (salvo `User`)

Los modelos de negocio no usan `HasFactory` y no existen factories. Por eso los seeders usan `updateOrCreate`.
Si se necesita volumen aleatorio, crear factories primero.

### 6.4 Pendientes conocidos

- Rutas de recursos **no registradas** → los controllers no son alcanzables por HTTP.
- Vistas **no creadas**.
- `PresentationController` existe pero **no hay modelo `Presentation`** (se creó a petición explícita). Debe decidirse: crear el modelo o eliminar el controller.
- No existe `routes/api.php` (Laravel 12 no lo crea por defecto).
- No hay Form Requests (validación inline en controllers).

---

## 7. Comandos útiles

```bash
# Base de datos
php artisan migrate:fresh --seed   # recrea el esquema y siembra
php artisan db:seed                # solo siembra (idempotente)
php artisan migrate:status

# Calidad
php -l <archivo.php>               # lint de sintaxis
vendor/bin/pint                    # formato
php artisan test                   # pruebas Pest

# Pruebas manuales de lógica (tinker, con rollback para no dejar datos)
php artisan tinker --execute="..."
```

Credenciales de prueba (tras `db:seed`): contraseña `password`.
- `test@example.com`
- `cajero@example.com`

---

## 8. Flujo de trabajo recomendado (Git)

1. **AUDITAR** el estado actual antes de cambiar.
2. **VERIFICAR CONSUMIDORES** (¿quién usa lo que vas a tocar?).
3. **CLASIFICAR RIESGO** de la modificación.
4. **DECIDIR** si está justificada.
5. **MODIFICAR SOLO SI ESTÁ JUSTIFICADO.**
6. **PROBAR** (`php -l`, `migrate`, `tinker` con rollback, `pest`).
7. **REVISAR DIFF** (incluir `git diff --check`).
8. **COMMIT.**
9. **PUSH.**

Reglas:
- Las fases marcadas como **READ-ONLY** no deben modificar archivos.
- No hacer commit/push si una fase lo prohíbe expresamente.
- Los artefactos de auditoría en `/tmp/opencode/` son temporales y **no** forman parte del repositorio.
- `clasp push` **no aplica** a este proyecto (es Laravel, no Google Apps Script).

---

## 9. Forma de trabajar con el usuario

- El usuario da instrucciones en español, a menudo concisas; ejecutar exactamente lo pedido, sin ampliar el alcance.
- Si una instrucción es ambigua o implica una decisión de diseño con impacto, **preguntar antes**.
- Al terminar una tarea: explicar qué se hizo, decisiones tomadas, y cómo se probó.
- No modificar ni eliminar trabajo previo del usuario sin confirmar.
