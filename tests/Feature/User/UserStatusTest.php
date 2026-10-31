<?php

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('un encargado puede desactivar a un empleado y se revocan sus sesiones', function () {
    $encargado = User::factory()->create(['tipo' => UserType::Encargado]);
    $empleado = User::factory()->create(['tipo' => UserType::Empleado]);

    DB::table('sessions')->insert([
        'id' => 'sesion-del-empleado',
        'user_id' => $empleado->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);

    $response = $this->actingAs($encargado)->patch(route('users.deactivate', $empleado));

    $response->assertRedirect(route('users.show', $empleado));
    $response->assertSessionHas('status');

    $empleado->refresh();

    expect($empleado->active)->toBeFalse()
        ->and($empleado->remember_token)->toBeNull();

    $this->assertDatabaseMissing('sessions', ['user_id' => $empleado->id]);
});

test('un encargado no puede desactivar su propia cuenta', function () {
    $encargado = User::factory()->create(['tipo' => UserType::Encargado]);

    $this->actingAs($encargado)
        ->patch(route('users.deactivate', $encargado))
        ->assertForbidden();

    expect($encargado->refresh()->active)->toBeTrue();
});

test('un encargado puede reactivar a un usuario inactivo', function () {
    $encargado = User::factory()->create(['tipo' => UserType::Encargado]);
    $empleado = User::factory()->create(['tipo' => UserType::Empleado, 'active' => false]);

    $response = $this->actingAs($encargado)->patch(route('users.activate', $empleado));

    $response->assertRedirect(route('users.show', $empleado));
    $response->assertSessionHas('status');

    expect($empleado->refresh()->active)->toBeTrue();
});

test('un empleado no puede activar ni desactivar usuarios', function () {
    $empleado = User::factory()->create(['tipo' => UserType::Empleado]);
    $otro = User::factory()->create(['tipo' => UserType::Empleado]);

    $this->actingAs($empleado)
        ->patch(route('users.deactivate', $otro))
        ->assertForbidden();

    $this->actingAs($empleado)
        ->patch(route('users.activate', $otro))
        ->assertForbidden();
});

test('no se puede degradar al último encargado activo', function () {
    $encargado = User::factory()->create(['tipo' => UserType::Encargado]);

    $response = $this->actingAs($encargado)
        ->from(route('users.edit', $encargado))
        ->put(route('users.update', $encargado), [
            'name' => $encargado->name,
            'email' => $encargado->email,
            'tipo' => UserType::Empleado->value,
        ]);

    $response->assertSessionHasErrors('tipo');

    expect($encargado->refresh()->tipo)->toBe(UserType::Encargado);
});

test('se puede degradar a un encargado cuando queda otro activo', function () {
    $encargado = User::factory()->create(['tipo' => UserType::Encargado]);
    $otro = User::factory()->create(['tipo' => UserType::Encargado]);

    $response = $this->actingAs($encargado)
        ->from(route('users.edit', $otro))
        ->put(route('users.update', $otro), [
            'name' => $otro->name,
            'email' => $otro->email,
            'tipo' => UserType::Empleado->value,
        ]);

    $response->assertSessionHasNoErrors();

    expect($otro->refresh()->tipo)->toBe(UserType::Empleado);
});
