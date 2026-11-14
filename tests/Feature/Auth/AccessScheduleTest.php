<?php

use App\Enums\UserType;
use App\Models\User;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Horario de acceso: autenticación y sesiones
|--------------------------------------------------------------------------
|
| El fin del rango es exclusivo: con 07:00–19:00 se permite hasta las
| 18:59:59 y se bloquea desde las 19:00:00. Los encargados nunca se
| bloquean; los empleados sin horario asignado no pueden ingresar.
|
*/

afterEach(function () {
    Carbon::setTestNow();
});

test('un empleado sin horario no puede autenticarse', function () {
    Carbon::setTestNow('2026-11-04 12:00:00');

    $user = User::factory()->create([
        'tipo' => UserType::Empleado,
        'access_start_time' => null,
        'access_end_time' => null,
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors([
        'email' => 'Tu cuenta no tiene un horario de acceso asignado. Contacta al encargado.',
    ]);

    $this->assertGuest();
});

test('un empleado con sesión abierta y sin horario es expulsado', function () {
    $user = User::factory()->create([
        'tipo' => UserType::Empleado,
        'access_start_time' => null,
        'access_end_time' => null,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('un encargado sin horario puede autenticarse a cualquier hora', function () {
    Carbon::setTestNow('2026-11-04 03:00:00');

    $user = User::factory()->create([
        'tipo' => UserType::Encargado,
        'access_start_time' => null,
        'access_end_time' => null,
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('un empleado dentro de su horario puede autenticarse', function () {
    Carbon::setTestNow('2026-11-04 12:00:00');

    $user = User::factory()->create([
        'tipo' => UserType::Empleado,
        'access_start_time' => '07:00',
        'access_end_time' => '19:00',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('un empleado fuera de su horario no puede autenticarse', function () {
    Carbon::setTestNow('2026-11-04 19:02:00');

    $user = User::factory()->create([
        'tipo' => UserType::Empleado,
        'access_start_time' => '07:00',
        'access_end_time' => '19:00',
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'email' => 'Estás fuera de tu horario de acceso. Tu horario es de 07:00 a 19:00.',
    ]);
});

test('el último segundo del horario está permitido y el límite exacto no', function () {
    $user = User::factory()->create([
        'tipo' => UserType::Empleado,
        'access_start_time' => '07:00',
        'access_end_time' => '19:00',
    ]);

    Carbon::setTestNow('2026-11-04 18:59:59');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);

    $this->post('/logout');
    $this->assertGuest();

    Carbon::setTestNow('2026-11-04 19:00:00');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('un empleado con sesión abierta es expulsado al pasar su hora de salida', function () {
    $user = User::factory()->create([
        'tipo' => UserType::Empleado,
        'access_start_time' => '07:00',
        'access_end_time' => '19:00',
    ]);

    Carbon::setTestNow('2026-11-04 18:50:00');

    $this->actingAs($user)->get('/dashboard')->assertOk();

    Carbon::setTestNow('2026-11-04 19:02:00');

    $this->get('/dashboard')->assertRedirect(route('login'));

    $this->assertGuest();
});

test('la cookie de recordarme no evita el bloqueo fuera de horario', function () {
    $user = User::factory()->create([
        'tipo' => UserType::Empleado,
        'access_start_time' => '07:00',
        'access_end_time' => '19:00',
    ]);

    Carbon::setTestNow('2026-11-04 18:00:00');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);

    Carbon::setTestNow('2026-11-04 19:02:00');

    $this->get('/dashboard')->assertRedirect(route('login'));

    $this->assertGuest();
});

test('un encargado no está sujeto al horario', function () {
    Carbon::setTestNow('2026-11-04 19:02:00');

    $user = User::factory()->create([
        'tipo' => UserType::Encargado,
        'access_start_time' => '07:00',
        'access_end_time' => '19:00',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('un rango con la entrada posterior a la salida no concede acceso', function () {
    $user = User::factory()->create([
        'tipo' => UserType::Empleado,
        'access_start_time' => '22:00',
        'access_end_time' => '06:00',
    ]);

    expect($user->isWithinAccessSchedule(Carbon::parse('2026-11-04 23:00:00')))->toBeFalse()
        ->and($user->isWithinAccessSchedule(Carbon::parse('2026-11-05 03:00:00')))->toBeFalse()
        ->and($user->isWithinAccessSchedule(Carbon::parse('2026-11-05 12:00:00')))->toBeFalse();
});

test('una petición json fuera de horario responde 403', function () {
    $user = User::factory()->create([
        'tipo' => UserType::Empleado,
        'access_start_time' => '07:00',
        'access_end_time' => '19:00',
    ]);

    Carbon::setTestNow('2026-11-04 19:02:00');

    $this->actingAs($user)
        ->getJson('/dashboard')
        ->assertStatus(403)
        ->assertJson([
            'message' => 'Estás fuera de tu horario de acceso. Tu horario es de 07:00 a 19:00.',
        ]);

    $this->assertGuest();
});
