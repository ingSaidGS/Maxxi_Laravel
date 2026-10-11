---
name: Maxxi UI
description: Sistema de diseño de Maxxi (paleta, tipografía, componentes y reglas de UI). Úsalo siempre que crees o refactorices vistas Blade del sistema (index, create, edit, show) para mantener consistencia visual, buenas prácticas responsive y accesibilidad.
---

# Sistema de diseño — Maxxi UI

Base única de colores, tipografía, componentes y reglas para **crear o refactorizar
vistas Blade**. Aplícalo en todas las vistas del sistema (`unit`, `product`,
`product-presentation`, `sale`, `dashboard`, etc.).

## Principios

1. **Idioma:** toda la interfaz y los mensajes en **español**.
2. **Mobile-first:** diseña primero para móvil y mejora con `sm:`, `md:`, `lg:`.
3. **Tailwind v3.4** (PostCSS). No uses estilos en línea salvo excepción justificada.
4. **Vistas del sistema** siempre dentro de `<x-layouts.sistema title="...">`.
5. **Consistencia:** reutiliza las clases de este documento en vez de inventar otras.
6. **Accesible:** foco visible, `aria-*` cuando aplique y áreas táctiles ≥ 40px.
7. Tras agregar clases nuevas: `npm run build` (los assets en `public/build` no se versionan).

## Paleta de color

Definida en `tailwind.config.js` como la familia `maxxi` (extraída del isotipo):

| Token | Hex | Uso |
|---|---|---|
| `maxxi-navy` | `#1A2E45` | Marca, cabeceras, texto fuerte, botón secundario |
| `maxxi-navy-dark` | `#14233A` | Hover del marino |
| `maxxi-navy-light` | `#25405C` | Detalles sobre marino |
| `maxxi-orange` | `#F87613` | **Acción principal**, acentos, enlaces |
| `maxxi-orange-dark` | `#D36B15` | Hover del naranja |
| `maxxi-slate` | `#485767` | Texto secundario |
| `maxxi-cream` | `#FFFAE8` | Fondos suaves / hover de filas |

Neutros: usar la escala `gray` de Tailwind (`gray-50` … `gray-900`).
Semánticos: `green` (éxito), `red` (peligro/error), `amber` (advertencia), `blue` (info).

## Tipografía

- Fuente: **Figtree** (`font-sans`, ya cargada por el layout).
- `h1` de página: `text-2xl font-semibold text-gray-800`
- Subtítulo/ayuda: `text-sm text-gray-500`
- Encabezados de tabla: `bg-maxxi-navy text-white` con `text-xs font-semibold uppercase tracking-wide`
- Texto normal: `text-sm text-gray-700`

## Layout de página (contenedor)

El layout `<x-layouts.sistema>` ya aporta la barra superior y el contenedor
`max-w-7xl` con padding. Dentro de la vista **no** repitas contenedores de ancho.

Estructura estándar de una vista de recurso:

```blade
<x-layouts.sistema title="Unidades">
    {{-- 1. Encabezado de página + acción principal --}}
    {{-- 2. Mensajes (éxito / error) --}}
    {{-- 3. Contenido: tabla (escritorio) + tarjetas (móvil) o formulario --}}
</x-layouts.sistema>
```

## Encabezado de página + acción principal

Título a la izquierda y **botón principal a la derecha**; en móvil se apila y el
botón ocupa todo el ancho.

```blade
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="mb-0 text-2xl font-semibold text-gray-800">Unidades</h1>
        <p class="mb-0 mt-1 text-sm text-gray-500">Unidades de medida del sistema.</p>
    </div>

    <a href="{{ route('units.create') }}" class="{{ $botones['primario'] }} w-full sm:w-auto">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
        </svg>
        Nueva unidad
    </a>
</div>
```

## Botones

Declara las variantes una vez al inicio de la vista y reutilízalas:

```blade
@php
    $botones = [
        'primario'   => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-maxxi-orange px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-maxxi-orange-dark focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2',
        'secundario' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'marino'     => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-maxxi-navy/20 bg-maxxi-navy/5 px-3 py-1.5 text-sm font-medium text-maxxi-navy transition hover:bg-maxxi-navy hover:text-white focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 focus:ring-offset-1',
        'peligro'    => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-1',
        'exito'      => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-green-200 bg-white px-3 py-1.5 text-sm font-medium text-green-700 transition hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-300 focus:ring-offset-1',
        // Sólidas: solo para el botón de confirmar en un modal.
        'peligro-solido' => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
        'exito-solido'   => 'inline-flex items-center justify-center gap-1.5 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2',
    ];
@endphp
```

Convención de uso: **Ver** = `secundario`, **Editar** → `marino`,
**Dar de baja / Eliminar** → `peligro`, **Reactivar/restaurar** → `exito`,
acción principal de la página → `primario`. Las variantes **sólidas**
(`peligro-solido`, `exito-solido`) se reservan para el botón de confirmar dentro
de un modal.

## Mensajes (alertas)

**Éxito** (verde) — usa `session('status')`:

```blade
@if (session('status'))
    <div role="status" class="mb-4 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ session('status') }}</span>
    </div>
@endif
```

**Error** (rojo) — usa `$errors`:

```blade
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
```

Variantes de color cambiando `green`↔`red`↔`amber`↔`blue` y `role`/icono.

## Listados: tabla (escritorio) + tarjetas (móvil)

Nunca dejes una tabla apretada en móvil. Muestra **tabla en `md+`** y
**tarjetas en móvil**. No incluyas la columna `ID` salvo que se pida.

```blade
{{-- Tabla (escritorio) --}}
<div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
    <table class="mb-0 w-full border-collapse text-sm">
        <thead>
            <tr class="text-left text-xs uppercase tracking-wide">
                <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Nombre</th>
                <th class="bg-maxxi-navy px-4 py-3 font-semibold text-white">Estado</th>
                <th class="bg-maxxi-navy px-4 py-3 text-right font-semibold text-white">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr class="border-t border-gray-100 transition hover:bg-maxxi-cream/60">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $item->name }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activa</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            {{-- botones de acción --}}
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Tarjetas (móvil) --}}
<div class="space-y-3 md:hidden">
    @foreach ($items as $item)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="mb-0 font-semibold text-gray-800">{{ $item->name }}</p>
                    <p class="mb-0 mt-0.5 text-sm text-gray-500">Símbolo: {{ $item->symbol }}</p>
                </div>
                <span class="inline-flex shrink-0 items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activa</span>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                {{-- botones de acción --}}
            </div>
        </div>
    @endforeach
</div>
```

> **Cabecera azul:** usa `bg-maxxi-navy text-white`. Como la capa base de
> `app.css` pinta los `th` con `bg-gray-100`, aplica `bg-maxxi-navy text-white`
> **en cada `th`** (no en el `tr`) para que la utilidad gane. El contenedor
> `overflow-hidden rounded-xl` redondea las esquinas superiores.

## Filas y tarjetas clicables

Para que al hacer clic en una fila (escritorio) o tarjeta (móvil) se abra su
detalle, agrega `data-href` con la URL y `cursor-pointer`. Un **manejador global**
en `resources/js/app.js` se encarga de navegar; **no** agregues scripts en cada
vista. Marca la zona de acciones con `data-href-ignore` para que pulsar cerca de
los botones no navegue.

```blade
{{-- Tabla (escritorio) --}}
<tr data-href="{{ route('units.show', $unit) }}"
    class="cursor-pointer border-t border-gray-100 transition hover:bg-maxxi-cream/60">
    <td class="px-4 py-3 font-medium text-gray-800">{{ $unit->name }}</td>
    <td data-href-ignore class="px-4 py-3">
        {{-- acciones --}}
    </td>
</tr>

{{-- Tarjeta (móvil) --}}
<div data-href="{{ route('units.show', $unit) }}"
     class="cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-maxxi-orange/40 hover:shadow">
    ...
    <div data-href-ignore class="mt-4 flex flex-wrap gap-2">
        {{-- acciones --}}
    </div>
</div>
```

- El manejador ignora clics sobre enlaces, botones, inputs, selects, textareas y
  labels, además de `Ctrl`/`Cmd`/`Shift`/`Alt` y el clic central.
- Conserva un enlace real **"Ver"** para navegación por teclado.

## Montos y formato de moneda

La moneda del sistema es el **boliviano** y se muestra con el prefijo **`Bs.`**.

- En Blade: `Bs. {{ $registro->total }}`.
- En JS (totales calculados): usa un helper y antepón el prefijo,
  `return 'Bs. ' + (Math.round(n * 10) / 10).toFixed(1);`.
- Deja los `data-*` monetarios **numéricos** (`data-total="{{ (float)$registro->total }}"`)
  para poder sumarlos en cliente; el prefijo se agrega solo al **mostrar**.
- Añade `tabular-nums` (y `whitespace-nowrap` en tablas) a todo valor monetario.

Colorea cada monto según su tipo, de forma **consistente** entre tabla y tarjetas:

| Concepto | Clase de texto |
|---|---|
| Total | `text-maxxi-orange-dark` |
| Efectivo | `text-green-600` |
| QR | `text-blue-600` |
| Fiado | `text-amber-600` |

En la tabla, un `<td>` por monto:

```blade
<td class="whitespace-nowrap px-4 py-3 font-semibold tabular-nums text-maxxi-orange-dark">Bs. {{ $sale->total }}</td>
<td class="whitespace-nowrap px-4 py-3 font-medium tabular-nums text-green-600">Bs. {{ $sale->cash }}</td>
<td class="whitespace-nowrap px-4 py-3 font-medium tabular-nums text-blue-600">Bs. {{ $sale->qr }}</td>
<td class="whitespace-nowrap px-4 py-3 font-medium tabular-nums text-amber-600">Bs. {{ $sale->debt }}</td>
```

En la tarjeta móvil, usa **chips** de color con etiqueta y valor:

```blade
<div class="mt-3 grid grid-cols-2 gap-2">
    <div class="rounded-lg bg-maxxi-orange/10 px-3 py-2">
        <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-maxxi-orange-dark">Total</p>
        <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">Bs. {{ $sale->total }}</p>
    </div>
    <div class="rounded-lg bg-green-50 px-3 py-2">
        <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-green-600">Efectivo</p>
        <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">Bs. {{ $sale->cash }}</p>
    </div>
    <div class="rounded-lg bg-blue-50 px-3 py-2">
        <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-blue-600">QR</p>
        <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">Bs. {{ $sale->qr }}</p>
    </div>
    <div class="rounded-lg bg-amber-50 px-3 py-2">
        <p class="mb-0 text-[11px] font-semibold uppercase tracking-wide text-amber-600">Fiado</p>
        <p class="mb-0 mt-0.5 text-sm font-bold tabular-nums text-gray-800">Bs. {{ $sale->debt }}</p>
    </div>
</div>
```

## Tarjetas de resumen (KPIs)

Cuando un listado tenga totales, muestra arriba una fila de **tarjetas KPI**: cada
una con etiqueta, icono en un cuadro de color y valor grande. Mantén el mismo color
por concepto (ver §Montos) y el icono a la derecha de la etiqueta para que el valor
quede a ancho completo (evita que se corte en móvil).

```blade
<div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between gap-2">
            <p class="mb-0 text-xs font-medium uppercase tracking-wide text-gray-400">Ventas</p>
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-navy/10 text-maxxi-navy" aria-hidden="true">
                {{-- icono h-4 w-4 --}}
            </span>
        </div>
        <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-gray-800" id="sum-count">0</p>
    </div>

    <div class="rounded-xl border border-maxxi-orange/30 bg-maxxi-orange/5 p-4 shadow-sm">
        <div class="flex items-center justify-between gap-2">
            <p class="mb-0 text-xs font-medium uppercase tracking-wide text-maxxi-orange-dark">Total</p>
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-maxxi-orange/15 text-maxxi-orange-dark" aria-hidden="true">
                {{-- icono h-4 w-4 --}}
            </span>
        </div>
        <p class="mb-0 mt-2 text-xl font-bold tabular-nums text-maxxi-orange-dark" id="sum-total">Bs. 0.0</p>
    </div>

    {{-- Repite para Efectivo (verde), QR (azul) y Fiado (ámbar) --}}
</div>
```

Convención de color por tarjeta: contenedor `border-<color>-200 bg-<color>-50/60` y
cuadro del icono `bg-<color>-100 text-<color>-600` (para `maxxi-orange` usa las
variantes con opacidad `bg-maxxi-orange/5`, `border-maxxi-orange/30`,
`bg-maxxi-orange/15`). El valor inicial incluye el prefijo (`Bs. 0.0`) y el JS lo
actualiza con el helper `fmt`.

## Buscador (filtro de texto en cliente)

Para permitir búsqueda libre sobre el listado ya cargado, añade un **input de
búsqueda** con icono de lupa y un **placeholder intuitivo** que describa los campos
buscables. Va al inicio del bloque de filtros, a ancho completo.

```blade
<div class="mb-4">
    <label for="unit-filter-search" class="mb-1 block text-sm font-medium text-gray-700">Buscar</label>
    <div class="relative">
        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
        </span>
        <input type="search" id="unit-filter-search" autocomplete="off"
               placeholder="Buscar por nombre, símbolo o estado…"
               class="block w-full rounded-lg border-gray-300 pl-9 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
    </div>
</div>
```

Reglas:

- El `pl-9` deja espacio para la lupa; la lupa lleva `pointer-events-none`.
- Marca **fila y tarjeta** con `data-search` conteniendo el **texto buscable
  concatenado** (incluye todos los campos relevantes). No lo pongas en minúsculas en
  Blade: normalízalo en JS para que la comparación sea insensible a mayúsculas:
  ```blade
  data-search="{{ trim(($item->name ?? '') . ' ' . ($item->symbol ?? '') . ' ' . ($item->user?->name ?? '')) }}"
  ```
- La búsqueda se **combina** con los demás filtros (fecha/estado): un ítem se muestra
  solo si cumple **todos**.
- Reutiliza el mismo `applyFilters()` y el mismo contenedor/estado vacío; agrega
  `searchInput?.addEventListener('input', applyFilters)` y limpia el input en el
  botón "Limpiar".
- Usa `type="search"` (permite la "x" nativa) y `autocomplete="off"`.

Fragmento JS:

```js
const searchInput = document.getElementById('unit-filter-search');
// ...
const query = (searchInput?.value || '').trim().toLowerCase();
// dentro del forEach:
let okSearch = true;
if (query && !(item.dataset.search || '').toLowerCase().includes(query)) okSearch = false;
const show = okDate && okStatus && okSearch;
```

## Filtro por estado (píldora segmentada)

Cuando un listado tenga una columna `active` (u otro booleano de baja lógica),
añade una **píldora segmentada** que filtre **en cliente**. Por defecto se muestra
el estado "positivo" (activas / activos).

```blade
{{-- Filtro por estado --}}
<div class="mb-4 flex items-center justify-end">
    <div class="inline-flex items-center rounded-full border border-gray-200 bg-gray-100 p-1" role="group" aria-label="Filtrar registros por estado">
        <button type="button" data-item-filter="active" aria-pressed="true"
                class="inline-flex items-center gap-1.5 rounded-full bg-transparent px-3.5 py-1.5 text-sm font-medium text-gray-500 transition hover:bg-white/70 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 aria-pressed:bg-white aria-pressed:text-maxxi-navy aria-pressed:shadow-sm">
            Activos
            <span class="rounded-full bg-green-100 px-1.5 text-xs font-semibold text-green-700">{{ $items->where('active', true)->count() }}</span>
        </button>
        <button type="button" data-item-filter="inactive" aria-pressed="false"
                class="inline-flex items-center gap-1.5 rounded-full bg-transparent px-3.5 py-1.5 text-sm font-medium text-gray-500 transition hover:bg-white/70 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-maxxi-navy/30 aria-pressed:bg-white aria-pressed:text-maxxi-navy aria-pressed:shadow-sm">
            Inactivos
            <span class="rounded-full bg-gray-200 px-1.5 text-xs font-semibold text-gray-600">{{ $items->where('active', false)->count() }}</span>
        </button>
    </div>
</div>
```

Reglas:

- El estado seleccionado se resalta con la **variante `aria-pressed:`** de Tailwind
  (`aria-pressed:bg-white aria-pressed:text-maxxi-navy aria-pressed:shadow-sm`); el
  JS solo actualiza `aria-pressed`, no manipula clases.
- Usa un nombre **por recurso** en los atributos: `data-<recurso>-filter` en los
  botones y `data-<recurso>-item` + `data-active="1|0"` en **fila y tarjeta**
  (p. ej. `data-unit-filter` / `data-unit-item`).
- Envuelve **tabla + tarjetas** en un contenedor con `id` (p. ej. `#unit-list`) para
  ocultarlo entero cuando el filtro no arroje resultados, y muestra un
  **estado vacío de filtro** (borde punteado) con un `id`.
- Es filtrado **en cliente**: solo si el `index` carga **todas** las filas sin
  paginar. Si hay paginación, muévelo a **server-side** (query param).
- Por defecto se aplica el filtro **activo** al cargar; los ítems removidos no se
  pueden clicar (no navegan).
- El mismo patrón sirve para filtrar un **estado tipo enum** (p. ej. `pagada`/`fiada`):
  marca fila y tarjeta con `data-<recurso>-item` + `data-status="…"` y compara
  `item.dataset.status === filter`. Ajusta etiquetas y color del contador (verde
  para el estado positivo, ámbar para el de advertencia).

Script vanilla (va al final de la vista; el layout no carga Alpine):

```blade
<script>
    const filterButtons = document.querySelectorAll('[data-unit-filter]');
    const items = document.querySelectorAll('[data-unit-item]');
    const list = document.getElementById('unit-list');
    const filterEmpty = document.getElementById('unit-filter-empty');

    const applyFilter = (filter) => {
        let visible = 0;

        items.forEach((item) => {
            const isActive = item.dataset.active === '1';
            const show = filter === 'active' ? isActive : !isActive;

            item.classList.toggle('hidden', !show);
            if (show) {
                visible++;
            }
        });

        // Oculta la lista completa (tabla + tarjetas) si el filtro no arroja resultados.
        list?.classList.toggle('hidden', visible === 0);
        filterEmpty?.classList.toggle('hidden', visible > 0);

        filterButtons.forEach((button) => {
            button.setAttribute('aria-pressed', button.dataset.unitFilter === filter ? 'true' : 'false');
        });
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => applyFilter(button.dataset.unitFilter));
    });

    applyFilter('active');
</script>
```

> Renombra las variables por recurso (`unit…`, `product…`) para evitar colisiones si
> algún día conviven dos listados en la misma página. El `hidden` de JS funciona
> porque el contenedor de la lista no lleva utilidades de `display` (que ganarían).

## Badges de estado

```blade
{{-- Activo --}}
<span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activa</span>
{{-- Inactivo --}}
<span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactiva</span>
```

## Estado vacío

```blade
<div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
    <p class="mb-1 font-medium text-gray-700">Aún no hay registros</p>
    <p class="mb-0 text-sm text-gray-500">Crea el primero para empezar.</p>
</div>
```

## Formularios

El plugin `@tailwindcss/forms` ya estiliza los controles. Reglas:

```blade
<div class="mb-4">
    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
    <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Ej.: Kilogramo"
           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
    @error('name')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
```

- Botón guardar: `primario`. Cancelar: `secundario` (o enlace).
- Agrupa campos en una tarjeta: `rounded-xl border border-gray-200 bg-white p-6 shadow-sm`.
- **Placeholders intuitivos**: usa un ejemplo real y concreto del dato
  (`Ej.: Kilogramo`, `Ej.: kg`, `Ej.: 71234567`), nunca el nombre del campo ni
  texto genérico. El `placeholder` es una ayuda: **no** sustituye a la `label`.
- Campos de códigos/nombres libres: agrega `autocomplete="off"` para evitar las
  sugerencias del navegador.

## Modales de confirmación (crear/editar)

Para pedir confirmación antes de enviar un formulario, usa el componente
`x-modal` (Alpine) y ábrelo/ciérralo con los eventos `open-modal` / `close-modal`.
La vista intercepta el `submit`, muestra un resumen y, al confirmar, reenvía el
formulario. Requisitos:

- El `<form>` lleva `id` (p. ej. `unit-form`).
- El modal se llama `confirm-<recurso>` (`confirm-unit`, `confirm-product`, …) y
  su resumen va en `<p id="confirm-<recurso>-summary">`, que el JS rellena.
- Icono en círculo naranja: `flex h-10 w-10 ... rounded-full bg-maxxi-orange/10 text-maxxi-orange-dark`.
- Cancelar: `secundario` con `x-on:click="$dispatch('close-modal', '<nombre>')"`.
- Confirmar: `primario`, `type="button"`, que dispara el envío real.
- Usa una bandera `bypassConfirm` para que el `submit` programático no reabra el
  modal, y deja un respaldo con `window.confirm` por si Alpine no está disponible.

```blade
<x-modal name="confirm-unit" max-width="md" focusable>
    <div class="p-6">
        <div class="flex items-start gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-maxxi-orange/10 text-maxxi-orange-dark">
                {{-- icono h-5 w-5 --}}
            </span>
            <div>
                <h2 class="mb-1 text-lg font-semibold text-gray-800">¿Registrar esta unidad?</h2>
                <p id="confirm-unit-summary" class="mb-0 text-sm text-gray-600"></p>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="{{ $botones['secundario'] }}" x-on:click="$dispatch('close-modal', 'confirm-unit')">Cancelar</button>
            <button type="button" id="confirm-unit-button" class="{{ $botones['primario'] }}">Sí, registrar</button>
        </div>
    </div>
</x-modal>
```

```blade
<script>
    (function () {
        var form = document.getElementById('unit-form');
        var summary = document.getElementById('confirm-unit-summary');
        var confirmButton = document.getElementById('confirm-unit-button');
        var bypassConfirm = false;

        form.addEventListener('submit', function (event) {
            if (bypassConfirm) { return; }
            event.preventDefault();
            summary.textContent = '…'; // resumen con los datos del formulario
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-unit' }));
        });

        confirmButton.addEventListener('click', function () {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirm-unit' }));
            bypassConfirm = true;
            if (typeof form.requestSubmit === 'function') { form.requestSubmit(); } else { form.submit(); }
            bypassConfirm = false;
        });
    })();
</script>
```

> El flujo de venta usa modales dedicados con el mismo componente: `confirm-fiada`
> (ámbar) cuando la venta queda fiada, `confirm-sale` (pagada) y `sale-created`
> (éxito) con acciones tras crear.

### Acciones por fila (baja / reactivación)

Cuando **cada fila** tiene su propio formulario (dar de baja, reactivar), no uses
`onsubmit="return confirm('…')"`. Marca el formulario con datos y reutiliza **un
solo modal por acción**:

```blade
<form action="{{ route('units.destroy', $unit) }}" method="POST"
      data-unit-action="deactivate" data-unit-name="{{ $unit->name }}">
    @csrf
    @method('DELETE')
    <button type="submit" class="{{ $botones['peligro'] }}">Dar de baja</button>
</form>
```

- Un modal por acción: `confirm-<recurso>-deactivate` y `confirm-<recurso>-restore`.
- Icono: rojo (`bg-red-100 text-red-600`) para baja/eliminar; verde
  (`bg-green-100 text-green-600`) para reactivar.
- Confirmar con variante sólida: `peligro-solido` o `exito-solido`.
- El JS escucha el `submit` de `[data-<recurso>-action]`, guarda el formulario
  pendiente, rellena el resumen con `data-<recurso>-name` y abre el modal; al
  confirmar, reenvía **ese** formulario usando la bandera `bypassConfirm`.

```blade
<script>
    (function () {
        var pendingForm = null;
        var bypassConfirm = false;

        document.querySelectorAll('[data-unit-action]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (bypassConfirm) { return; }
                event.preventDefault();
                pendingForm = form;
                var name = form.dataset.unitName || '';
                var detail = form.dataset.unitAction === 'deactivate'
                    ? 'confirm-unit-deactivate'
                    : 'confirm-unit-restore';
                document.getElementById(detail + '-summary').textContent = '…' + name + '…';
                window.dispatchEvent(new CustomEvent('open-modal', { detail: detail }));
            });
        });

        function submitPending(detail) {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: detail }));
            if (!pendingForm) { return; }
            bypassConfirm = true;
            if (typeof pendingForm.requestSubmit === 'function') { pendingForm.requestSubmit(); } else { pendingForm.submit(); }
            bypassConfirm = false;
            pendingForm = null;
        }

        document.getElementById('confirm-unit-deactivate-button')
            ?.addEventListener('click', function () { submitPending('confirm-unit-deactivate'); });
        document.getElementById('confirm-unit-restore-button')
            ?.addEventListener('click', function () { submitPending('confirm-unit-restore'); });
    })();
</script>
```

## Ficha de detalle (`show`)

Para mostrar los atributos de un registro, usa **una sola tarjeta** con una
**cuadrícula interna minimalista**. No pongas una fila por atributo (se ven
apilados) ni una tarjeta por atributo: todo va dentro del mismo contenedor, con
etiqueta en mayúsculas y valor debajo.

```blade
<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
    <dl class="mb-0 grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Nombre</dt>
            <dd class="mt-1 text-sm font-medium text-gray-800">{{ $record->name }}</dd>
        </div>

        <div>
            <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Símbolo</dt>
            <dd class="mt-1 text-sm text-gray-800">
                <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ $record->symbol }}</span>
            </dd>
        </div>

        <div>
            <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Estado</dt>
            <dd class="mt-1">
                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">Activa</span>
            </dd>
        </div>

        <div>
            <dt class="mt-0 text-xs font-medium uppercase tracking-wide text-gray-400">Creada</dt>
            <dd class="mt-1 text-sm text-gray-800">{{ $record->created_at?->format('d/m/Y H:i') }}</dd>
        </div>
    </dl>
</div>
```

- Usa `dl` con `div` intermedios (`dl > div > dt + dd`) para conservar la semántica.
- Encabezado del `show`: nombre del registro como `h1` + badge de estado, con las
  acciones **Volver** (`secundario`) y **Editar** (`marino`) a la derecha.
- Fechas: `?->format('d/m/Y H:i')`.

## Presentaciones, cantidades y unidad base

Cada producto tiene `base_unit_id`. Cada presentación tiene `unit_id` y
`conversion_factor` = cuántas **unidades base** contiene. Dos presentaciones del
mismo producto pueden compartir `unit` y diferir en el factor (p. ej. arroba de
24 lb vs arroba de 48 lb).

- **Etiqueta de presentación:** `Producto — Unidad (x{factor} {símbolo base})`,
  p. ej. `Arroz — Arroba (x24 lb)`. Incluye siempre el factor: sin él, dos
  presentaciones se ven idénticas en el listado y en el carrito.
- **Cantidad del detalle (`sale.show`), solo visual:** se muestra en la **unidad
  de la presentación** (no en la unidad base). El número es `base_quantity / ref`,
  donde `ref` es el **menor `conversion_factor` activo** del **catálogo de
  presentaciones de ese producto con la misma unidad** (`unit_id`) — no de las
  líneas vendidas — y hace de "1 unidad". Entero sin decimales; si el factor no es
  múltiplo, con `1` decimal (`36 / 24 → 1.5 Arrobas`). Con una sola presentación
  activa en esa unidad, `ref` es su propio factor y se ve la cantidad original.
  **Nunca toca la BD**: el `conversion_factor` y el `base_quantity` guardados no
  cambian.
- **Resumen por producto:** agrupa por producto y suma `cantidad × conversion_factor`;
  exprésalo en la **unidad base** pluralizada (vocal final → `+s`, consonante →
  `+es`). Así `1 arroba (x1) + 1 arroba (x2)` en un producto con base `Arroba`
  se muestra como `3 Arrobas`.
- Es genérico: no depende del nombre del producto ni de una unidad concreta.

### Cantidad normalizada del detalle

```blade
@php
    // Menor factor ACTIVO por (producto + unidad), tomado del CATÁLOGO del producto
    // (no de las líneas vendidas). Requiere eager load de product.presentations.
    $refFactorByGroup = $sale->saleDetails
        ->map(fn ($d) => $d->presentation?->product)
        ->filter()
        ->unique('id')
        ->flatMap(fn ($product) => $product->presentations)
        ->filter(fn ($p) => $p->active)
        ->groupBy(fn ($p) => $p->product_id.'|'.$p->unit_id)
        ->map(fn ($presentations) => (int) $presentations->min('conversion_factor'));

    // Entero sin decimales; si no es múltiplo, 1 decimal.
    $formatQuantity = fn (int|float $v): string => floor((float) $v) == (float) $v
        ? (string) (int) $v
        : number_format((float) $v, 1, '.', '');
@endphp

{{-- Por línea --}}
@php
    $presentation = $detail->presentation;
    $ref = $refFactorByGroup[$presentation->product_id.'|'.$presentation->unit_id]
        ?? $presentation->conversion_factor;
    $value = $detail->base_quantity / max((int) $ref, 1);
@endphp
{{ $formatQuantity($value) }} {{ $pluralize($presentation->unit->name, $value) }}
```

### Resumen por producto (unidad base)

```blade
@php
    $pluralize = fn (string $name, int $count): string => $count === 1
        ? $name
        : (preg_match('/[aeiou]$/i', $name) ? $name.'s' : $name.'es');

    $totals = $sale->saleDetails
        ->groupBy(fn ($d) => $d->presentation?->product?->id)
        ->map(fn ($details) => [
            'name' => $details->first()->presentation?->product?->name,
            'unit' => $details->first()->presentation?->product?->baseUnit?->name,
            'quantity' => (int) $details->sum('base_quantity'),
        ])->values();
@endphp

<ul class="mb-0 list-none space-y-1 p-0">
    @foreach ($totals as $row)
        <li class="flex items-center justify-between gap-3 text-sm">
            <span class="text-gray-700">{{ $row['name'] }}</span>
            <span class="font-semibold tabular-nums text-gray-800">
                {{ $row['quantity'] }} {{ $pluralize($row['unit'], $row['quantity']) }}
            </span>
        </li>
    @endforeach
</ul>
```

- En listados construidos por JS (carrito de `sale.create`), replica la misma
  pluralización en JS (`vocal final → +s`, `consonante → +es`) para no romper la
  consistencia entre el render servidor y el cliente.

## Accesibilidad

- Botones solo-icono: incluye `aria-label`.
- Interacciones controladas por Alpine: `x-cloak` (ya definido en `app.css`).
- Confirma acciones destructivas con `onsubmit="return confirm('…')"` o, para una confirmación cuidada, con un modal `confirm-<recurso>` (ver «Modales de confirmación»).
- Contraste: texto claro sobre `maxxi-navy`, texto oscuro sobre fondos crema/blancos.

## Checklist antes de terminar una vista

- [ ] Envuelta en `<x-layouts.sistema title="…">` con título en español.
- [ ] Encabezado con título + acción principal a la derecha (responsiva).
- [ ] Alertas de éxito (verde) y error (rojo) presentes si aplica.
- [ ] Listados: tabla en `md+` y tarjetas en móvil, sin columna `ID`, con cabecera azul (`bg-maxxi-navy text-white`).
- [ ] `show`: ficha de detalle en **una sola tarjeta** con cuadrícula (`sm:grid-cols-2 lg:grid-cols-3`).
- [ ] Fila/tarjeta navega a su detalle con `data-href` (y `data-href-ignore` en acciones).
- [ ] Botones según variantes (`primario`, `secundario`, `marino`, `peligro`, `exito`).
- [ ] Estados con badges, y estado vacío cuando no hay datos.
- [ ] Montos con prefijo `Bs.`, `tabular-nums` y color por concepto (Total/Efectivo/QR/Fiado); `data-*` numéricos.
- [ ] Listados con totales: tarjetas KPI (etiqueta + icono de color + valor) arriba del listado.
- [ ] Listados con búsqueda libre: input con lupa + placeholder intuitivo, `data-search` en fila y tarjeta, y limpieza en "Limpiar".
- [ ] Listados con `active`: píldora de filtro (activos/inactivos) que marque filas/tarjetas con `data-*-item` y oculte la lista si no hay resultados.
- [ ] Presentaciones: etiqueta `Producto — Unidad (x{factor} {unidad base})`; en `sale.show` la cantidad se normaliza por el menor factor del grupo (producto + unidad) y, si no es múltiplo, con 1 decimal; resumen por producto en unidad base (`cantidad × conversion_factor`, pluralizado).
- [ ] Formularios de crear/editar: confirmación con modal `confirm-<recurso>` (resumen + `bypassConfirm` + respaldo `window.confirm`).
- [ ] Campos con `placeholder` de ejemplo (nunca repetir la etiqueta) y `autocomplete="off"` en códigos/nombres.
- [ ] Acciones por fila (baja/reactivar): modal `confirm-<recurso>-<acción>` (no `onsubmit="return confirm"`), con botón sólido (`peligro-solido`/`exito-solido`).
- [ ] `aria-*`/foco y confirmación en acciones destructivas.
- [ ] `npm run build` si agregaste clases nuevas.

## Nota sobre CSS heredado

`resources/css/app.css` define una capa base para las vistas antiguas dentro de
`.contenido` usando `:where(...)`, de modo que **las utilidades de Tailwind
siempre la sobrescriben**. En vistas nuevas usa clases utilitarias explícitas.
