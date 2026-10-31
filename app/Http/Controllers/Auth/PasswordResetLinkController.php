<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * Se responde siempre con el mismo mensaje de éxito, exista o no la cuenta y
     * esté activa o no, para no revelar qué correos están registrados. A las
     * cuentas desactivadas no se les envía el enlace.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if ($user !== null && $user->active) {
            Password::sendResetLink($request->only('email'));
        }

        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
