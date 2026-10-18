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
            ['name' => 'Test User', 'password' => 'password', 'tipo' => UserType::Encargado, 'email_verified_at' => now()],
        );

        // Idempotente: si ya existía, se fuerza el rol esperado.
        User::where('email', 'test@example.com')->update(['tipo' => UserType::Encargado->value]);

        User::firstOrCreate(
            ['email' => 'cajero@example.com'],
            ['name' => 'Cajero Maxxi', 'password' => 'password', 'tipo' => UserType::Empleado, 'email_verified_at' => now()],
        );

        User::where('email', 'cajero@example.com')->update(['tipo' => UserType::Empleado->value]);

        $this->call([
            UnitSeeder::class,
            ProductSeeder::class,
            ProductPresentationSeeder::class,
            SaleSeeder::class,
            SaleDetailSeeder::class,
        ]);
    }
}
