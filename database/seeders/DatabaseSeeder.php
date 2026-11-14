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

        User::firstOrCreate(
            ['email' => 'cajero@example.com'],
            // Los empleados necesitan horario para ingresar; solo se asigna al
            // crearlo, para no pisar el horario de un cajero ya existente.
            ['name' => 'Cajero Maxxi', 'password' => 'password', 'tipo' => UserType::Empleado, 'active' => true, 'email_verified_at' => now(),
                'access_start_time' => '00:00', 'access_end_time' => '23:59'],
        );

        User::where('email', 'cajero@example.com')->update([
            'tipo' => UserType::Empleado->value,
            'active' => true,
        ]);

        $this->call([
            UnitSeeder::class,
            ProductSeeder::class,
            ProductPresentationSeeder::class,
            SaleSeeder::class,
            SaleDetailSeeder::class,
        ]);
    }
}
