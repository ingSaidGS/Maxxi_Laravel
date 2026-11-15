<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Solo se crea el usuario encargado de prueba: el resto de las tablas
     * (unidades, productos, presentaciones, ventas, compras y notas) quedan
     * vacías y se cargan desde el sistema.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password', 'tipo' => UserType::Encargado, 'active' => true, 'email_verified_at' => now()],
        );

        // Idempotente: si ya existía, se fuerzan el rol y el acceso esperados.
        User::where('email', 'test@example.com')->update([
            'tipo' => UserType::Encargado->value,
            'active' => true,
        ]);
    }
}
