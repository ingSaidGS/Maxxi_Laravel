<section>
    <header class="mb-4">
        <h2 class="mb-1 text-lg font-semibold text-gray-800">Información del perfil</h2>
        <p class="mb-0 text-sm text-gray-500">Actualiza tu nombre y tu correo electrónico.</p>
    </header>

    {{-- Reenvío del correo de verificación (solo si el modelo exige verificación). --}}
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        @method('patch')

        {{-- Nombre --}}
        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nombre</label>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $user->name) }}"
                required
                autofocus
                autocomplete="name"
                placeholder="Ej.: Ana Pérez"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange"
            />
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Correo electrónico --}}
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Correo electrónico</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $user->email) }}"
                required
                autocomplete="username"
                placeholder="correo@ejemplo.com"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-maxxi-orange focus:ring-maxxi-orange"
            />
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="mb-0 text-sm text-amber-700">Tu correo electrónico no está verificado.</p>

                    <button
                        form="send-verification"
                        class="mt-1 rounded text-sm font-medium text-maxxi-orange transition hover:text-maxxi-orange-dark hover:underline focus:outline-none focus:ring-2 focus:ring-maxxi-orange focus:ring-offset-2"
                    >
                        Haz clic aquí para reenviar el correo de verificación.
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mb-0 mt-2 text-sm font-medium text-green-600">
                            Se ha enviado un nuevo enlace de verificación a tu correo electrónico.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="{{ $botones['primario'] }}">Guardar</button>

            @if (session('status') === 'profile-updated')
                <span role="status" class="inline-flex items-center gap-1.5 text-sm font-medium text-green-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Guardado.
                </span>
            @endif
        </div>
    </form>
</section>
