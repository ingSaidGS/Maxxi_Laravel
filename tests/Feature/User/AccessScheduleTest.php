<?php

use App\Enums\UserType;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Horario de acceso: CRUD de usuarios
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    $this->encargado = User::factory()->create(['tipo' => UserType::Encargado]);
});

/**
 * Datos base para crear o actualizar un empleado con horario.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function accessSchedulePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Empleado Prueba',
        'email' => 'empleado-prueba@example.com',
        'tipo' => UserType::Empleado->value,
        'password' => 'Password1',
        'password_confirmation' => 'Password1',
        'access_start_time' => '07:00',
        'access_end_time' => '19:00',
    ], $overrides);
}

test('un encargado puede crear un empleado con horario de acceso', function () {
    $this->actingAs($this->encargado)
        ->post(route('users.store'), accessSchedulePayload())
        ->assertRedirect(route('users.index'));

    $empleado = User::where('email', 'empleado-prueba@example.com')->firstOrFail();

    expect(substr($empleado->access_start_time, 0, 5))->toBe('07:00')
        ->and(substr($empleado->access_end_time, 0, 5))->toBe('19:00');
});

test('un encargado puede actualizar el horario de un empleado', function () {
    $empleado = User::factory()->create([
        'tipo' => UserType::Empleado,
        'access_start_time' => '07:00',
        'access_end_time' => '19:00',
    ]);

    $payload = accessSchedulePayload([
        'email' => $empleado->email,
        'access_start_time' => '08:00',
        'access_end_time' => '18:00',
    ]);

    unset($payload['password'], $payload['password_confirmation']);

    $this->actingAs($this->encargado)
        ->put(route('users.update', $empleado), $payload)
        ->assertRedirect(route('users.show', $empleado));

    $empleado->refresh();

    expect(substr($empleado->access_start_time, 0, 5))->toBe('08:00')
        ->and(substr($empleado->access_end_time, 0, 5))->toBe('18:00');
});

test('un encargado puede quitar el horario de un empleado', function () {
    $empleado = User::factory()->create([
        'tipo' => UserType::Empleado,
        'access_start_time' => '07:00',
        'access_end_time' => '19:00',
    ]);

    $payload = accessSchedulePayload([
        'email' => $empleado->email,
        'access_start_time' => null,
        'access_end_time' => null,
    ]);

    unset($payload['password'], $payload['password_confirmation']);

    $this->actingAs($this->encargado)
        ->put(route('users.update', $empleado), $payload)
        ->assertRedirect(route('users.show', $empleado));

    $empleado->refresh();

    expect($empleado->access_start_time)->toBeNull()
        ->and($empleado->access_end_time)->toBeNull()
        ->and($empleado->accessScheduleLabel())->toBe('Sin restricción');
});

test('la ficha del usuario muestra el horario de acceso', function () {
    $empleado = User::factory()->create([
        'tipo' => UserType::Empleado,
        'access_start_time' => '07:00',
        'access_end_time' => '19:00',
    ]);

    $this->actingAs($this->encargado)
        ->get(route('users.show', $empleado))
        ->assertOk()
        ->assertSee('07:00 a 19:00');
});

test('el horario debe venir completo: no se acepta solo la hora de entrada', function () {
    $this->actingAs($this->encargado)
        ->post(route('users.store'), accessSchedulePayload([
            'access_end_time' => null,
        ]))
        ->assertSessionHasErrors('access_end_time');

    expect(User::where('email', 'empleado-prueba@example.com')->exists())->toBeFalse();
});

test('el horario debe venir completo: no se acepta solo la hora de salida', function () {
    $this->actingAs($this->encargado)
        ->post(route('users.store'), accessSchedulePayload([
            'access_start_time' => null,
        ]))
        ->assertSessionHasErrors('access_start_time');

    expect(User::where('email', 'empleado-prueba@example.com')->exists())->toBeFalse();
});

test('la hora de entrada no puede ser posterior a la hora de salida', function () {
    $this->actingAs($this->encargado)
        ->post(route('users.store'), accessSchedulePayload([
            'access_start_time' => '19:00',
            'access_end_time' => '07:00',
        ]))
        ->assertSessionHasErrors([
            'access_end_time' => 'La hora de salida debe ser posterior a la hora de entrada.',
        ]);

    expect(User::where('email', 'empleado-prueba@example.com')->exists())->toBeFalse();
});

test('la hora de entrada no puede ser posterior a la hora de salida al editar', function () {
    $empleado = User::factory()->create([
        'tipo' => UserType::Empleado,
        'access_start_time' => '07:00',
        'access_end_time' => '19:00',
    ]);

    $payload = accessSchedulePayload([
        'email' => $empleado->email,
        'access_start_time' => '19:00',
        'access_end_time' => '07:00',
    ]);

    unset($payload['password'], $payload['password_confirmation']);

    $this->actingAs($this->encargado)
        ->put(route('users.update', $empleado), $payload)
        ->assertSessionHasErrors('access_end_time');

    $empleado->refresh();

    expect(substr($empleado->access_start_time, 0, 5))->toBe('07:00')
        ->and(substr($empleado->access_end_time, 0, 5))->toBe('19:00');
});

test('las horas de entrada y salida no pueden ser iguales', function () {
    $this->actingAs($this->encargado)
        ->post(route('users.store'), accessSchedulePayload([
            'access_start_time' => '19:00',
            'access_end_time' => '19:00',
        ]))
        ->assertSessionHasErrors('access_end_time');

    expect(User::where('email', 'empleado-prueba@example.com')->exists())->toBeFalse();
});

test('las horas deben tener un formato válido', function () {
    $this->actingAs($this->encargado)
        ->post(route('users.store'), accessSchedulePayload([
            'access_start_time' => '25:00',
            'access_end_time' => '19:00',
        ]))
        ->assertSessionHasErrors('access_start_time');

    expect(User::where('email', 'empleado-prueba@example.com')->exists())->toBeFalse();
});
