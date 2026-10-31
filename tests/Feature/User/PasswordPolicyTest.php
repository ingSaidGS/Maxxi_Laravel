<?php

use App\Enums\UserType;
use App\Models\User;

test('la politica de contraseñas exige letras y numeros', function () {
    $encargado = User::factory()->create(['tipo' => UserType::Encargado]);

    $this->actingAs($encargado)
        ->post(route('users.store'), [
            'name' => 'Usuario Prueba',
            'email' => 'prueba@example.com',
            'tipo' => UserType::Empleado->value,
            'password' => 'sololetras',
            'password_confirmation' => 'sololetras',
        ])
        ->assertSessionHasErrors('password');

    $this->assertDatabaseMissing('users', ['email' => 'prueba@example.com']);
});

test('la politica de contraseñas acepta una contraseña con letras y numeros', function () {
    $encargado = User::factory()->create(['tipo' => UserType::Encargado]);

    $this->actingAs($encargado)
        ->post(route('users.store'), [
            'name' => 'Usuario Prueba',
            'email' => 'prueba@example.com',
            'tipo' => UserType::Empleado->value,
            'password' => 'Clave1234',
            'password_confirmation' => 'Clave1234',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', ['email' => 'prueba@example.com']);
});
