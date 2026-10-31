<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('no se envia el enlace de restablecimiento a una cuenta inactiva', function () {
    Notification::fake();

    $user = User::factory()->create(['active' => false]);

    $response = $this->post('/forgot-password', ['email' => $user->email]);

    $response->assertSessionHas('status');
    $response->assertSessionHasNoErrors();

    Notification::assertNotSentTo($user, ResetPassword::class);
});

test('la respuesta al solicitar el enlace no revela si la cuenta existe', function () {
    Notification::fake();

    $activo = User::factory()->create();
    $inactivo = User::factory()->create(['active' => false]);

    $conCuenta = $this->post('/forgot-password', ['email' => $activo->email]);
    $inactiva = $this->post('/forgot-password', ['email' => $inactivo->email]);
    $inexistente = $this->post('/forgot-password', ['email' => 'nadie@example.com']);

    $mensaje = $conCuenta->getSession()->get('status');

    expect($mensaje)->not->toBeNull()
        ->and($inactiva->getSession()->get('status'))->toBe($mensaje)
        ->and($inexistente->getSession()->get('status'))->toBe($mensaje);
});

test('una cuenta inactiva no puede completar el restablecimiento con un token valido', function () {
    Notification::fake();

    $user = User::factory()->create(['active' => false]);
    $token = Password::broker()->createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'Clave1234',
        'password_confirmation' => 'Clave1234',
    ]);

    $response->assertSessionHasErrors('email');

    expect(Hash::check('Clave1234', $user->refresh()->password))->toBeFalse();
});
