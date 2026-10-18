<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $users = User::orderBy('name')->get();
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

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()
            ->route('users.show', $user)
            ->with('status', 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * La tabla "users" no tiene columna "active", así que la baja lógica no aplica
     * todavía. La ruta de "destroy" se excluye en routes/web.php.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Reglas de validación según las columnas de "users".
     *
     * "name" y "email" son varchar(255) y "email" es único. La contraseña es
     * obligatoria al crear y opcional al editar (mínimo 8 caracteres, igual que
     * el registro de Breeze) y debe venir confirmada. El correo se ignora a sí
     * mismo al editar.
     *
     * @return array<string, array<int, string|Rule>>
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
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
