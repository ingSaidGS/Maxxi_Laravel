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
    ];
@endphp
```

Convención de uso: **Ver** = `secundario`, **Editar** → `marino`,
**Dar de baja / Eliminar** → `peligro`, **Reactivar/restaurar** → `exito`,
acción principal de la página → `primario`.

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
    <input id="name" name="name" type="text" value="{{ old('name') }}"
           class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange" />
    @error('name')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
```

- Botón guardar: `primario`. Cancelar: `secundario` (o enlace).
- Agrupa campos en una tarjeta: `rounded-xl border border-gray-200 bg-white p-6 shadow-sm`.

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

## Accesibilidad

- Botones solo-icono: incluye `aria-label`.
- Interacciones controladas por Alpine: `x-cloak` (ya definido en `app.css`).
- Confirma acciones destructivas con `onsubmit="return confirm('…')"`.
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
- [ ] `aria-*`/foco y confirmación en acciones destructivas.
- [ ] `npm run build` si agregaste clases nuevas.

## Nota sobre CSS heredado

`resources/css/app.css` define una capa base para las vistas antiguas dentro de
`.contenido` usando `:where(...)`, de modo que **las utilidades de Tailwind
siempre la sobrescriben**. En vistas nuevas usa clases utilitarias explícitas.
