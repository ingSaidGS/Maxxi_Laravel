<?php

use App\Models\User;

test('un usuario inactivo no puede autenticarse', function () {
    $user = User::factory()->create(['active' => false]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('un usuario inactivo con sesión abierta es expulsado en la siguiente petición', function () {
    $user = User::factory()->create(['active' => false]);

    $response = $this->actingAs($user)->get('/dashboard');

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
});

test('un usuario desactivado durante la sesión es expulsado y no puede volver a entrar', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $user->update(['active' => false]);

    $this->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('un usuario reactivado puede volver a autenticarse', function () {
    $user = User::factory()->create(['active' => false]);

    $user->update(['active' => true]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('un usuario activo conserva el acceso a las rutas protegidas', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
});
