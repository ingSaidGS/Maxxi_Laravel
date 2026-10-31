<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $users = User::orderByDesc('active')->orderBy('name')->get();
        $userTypes = UserType::cases();

        return view('user.index', compact('users', 'userTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $userTypes = UserType::cases();

        return view('user.create', compact('userTypes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * La contraseña se guarda cifrada: el modelo "User" castea "password" a
     * "hashed", por lo que se envía en texto plano y Eloquent la hashea.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        User::create($validated);

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): View
    {
        return view('user.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        $userTypes = UserType::cases();

        return view('user.edit', compact('user', 'userTypes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * La contraseña es opcional al editar: si llega vacía, se conserva la actual.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate($this->rules($user));

        // Un encargado activo no puede degradarse si es el último que queda:
        // dejaría el sistema sin quien gestione usuarios y catálogo.
        if ($this->isLastActiveEncargado($user)
            && ($validated['tipo'] ?? null) !== UserType::Encargado->value) {
            throw ValidationException::withMessages([
                'tipo' => 'Debe quedar al menos un encargado activo en el sistema.',
            ]);
        }

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()
            ->route('users.show', $user)
            ->with('status', 'Usuario actualizado correctamente.');
    }

    /**
     * Activa el acceso de un usuario (alta lógica).
     */
    public function activate(User $user): RedirectResponse
    {
        $user->update(['active' => true]);

        return redirect()
            ->route('users.show', $user)
            ->with('status', 'Usuario activado correctamente.');
    }

    /**
     * Desactiva el acceso de un usuario (baja lógica).
     *
     * Además de marcar "active" en false, se invalida el "remember_token" y se
     * eliminan sus sesiones abiertas, para que la expulsión sea inmediata y no
     * dependa de que expire la sesión.
     */
    public function deactivate(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403, 'No puedes desactivar tu propia cuenta.');

        if ($this->isLastActiveEncargado($user)) {
            return redirect()
                ->route('users.show', $user)
                ->withErrors(['active' => 'Debe quedar al menos un encargado activo en el sistema.']);
        }

        $user->forceFill([
            'active' => false,
            'remember_token' => null,
        ])->save();

        DB::table('sessions')->where('user_id', $user->id)->delete();

        return redirect()
            ->route('users.show', $user)
            ->with('status', 'Usuario desactivado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * No aplica: la baja de usuarios es lógica mediante "active" y se realiza
     * con "deactivate()". La ruta de "destroy" se excluye en routes/web.php.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Indica si desactivar o degradar al usuario dejaría el sistema sin
     * encargados activos.
     */
    protected function isLastActiveEncargado(User $user): bool
    {
        return $user->active
            && $user->isEncargado()
            && User::where('active', true)
                ->where('tipo', UserType::Encargado->value)
                ->count() <= 1;
    }

    /**
     * Reglas de validación según las columnas de "users".
     *
     * "name" y "email" son varchar(255) y "email" es único. La contraseña es
     * obligatoria al crear y opcional al editar, debe venir confirmada y cumple
     * la política global definida con "Password::defaults()" en AppServiceProvider.
     * El correo se ignora a sí mismo al editar.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(?User $user = null): array
    {
        $email = ['required', 'string', 'email', 'max:255'];

        $email[] = $user !== null
            ? Rule::unique('users', 'email')->ignore($user->id)
            : Rule::unique('users', 'email');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => $email,
            'tipo' => ['required', Rule::enum(UserType::class)],
            'password' => $user !== null
                ? ['nullable', 'string', Password::defaults(), 'confirmed']
                : ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }
}
