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
        // Es el único usuario de prueba que crea el seeder.
        User::where('email', 'test@example.com')->update([
            'tipo' => UserType::Encargado->value,
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
