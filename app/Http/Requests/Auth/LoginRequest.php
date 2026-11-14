<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza el correo antes de validar: sin espacios y en minúscula.
     *
     * Los correos se guardan en minúscula (ver UserController::normalizeInput()),
     * y en PostgreSQL la comparación distingue mayúsculas, así que sin esto
     * "Maria@Maxxi.com" no encontraría a "maria@maxxi.com".
     */
    protected function prepareForValidation(): void
    {
        if (is_string($email = $this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($email))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // "active" => true impide que una cuenta desactivada inicie sesión. Se
        // mantiene el mensaje genérico de Laravel para no revelar si el correo
        // existe ni si la contraseña era correcta.
        if (! Auth::attempt([
            ...$this->only('email', 'password'),
            'active' => true,
        ], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Credenciales válidas, pero el empleado está fuera de su horario de
        // acceso (o no tiene horario asignado): se cierra la sesión recién
        // iniciada y se informa el motivo. Los encargados pasan sin restricción.
        $user = Auth::user();

        if ($user !== null && ! $user->isWithinAccessSchedule()) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => $user->accessDeniedMessage(),
            ]);
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
