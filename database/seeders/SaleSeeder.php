<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Las ventas de ejemplo se registran a nombre del único usuario de prueba
        // (lo crea DatabaseSeeder; se crea aquí si este seeder se ejecuta solo).
        $cashier = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password', 'tipo' => UserType::Encargado, 'active' => true, 'email_verified_at' => now()],
        );

        $sales = [
            [
                'customer_name' => 'Juan Pérez',
                'customer_phone' => '71234567',
                'sold_at' => Carbon::parse('2026-10-01 10:30:00'),
                'total' => 33.0,
                'status' => 'pagada',
                'sale_discount' => 0.0,
                'cash' => 33.0,
                'qr' => 0.0,
                'debt' => 0.0,
            ],
            [
                'customer_name' => 'María López',
                'customer_phone' => '87654321',
                'sold_at' => Carbon::parse('2026-10-03 16:15:00'),
                'total' => 29.0,
                'status' => 'fiada',
                'sale_discount' => 0.0,
                'cash' => 10.0,
                'qr' => 0.0,
                'debt' => 19.0,
            ],
            [
                'customer_name' => 'Carlos Ruiz',
                'customer_phone' => '76543210',
                'sold_at' => Carbon::parse('2026-10-05 09:45:00'),
                'total' => 15.0,
                'status' => 'pagada',
                'sale_discount' => 0.0,
                'cash' => 0.0,
                'qr' => 15.0,
                'debt' => 0.0,
            ],
        ];

        foreach ($sales as $sale) {
            $record = Sale::firstOrNew(['customer_name' => $sale['customer_name']]);
            $record->fill($sale);

            // El cajero solo se asigna al crear la venta: al volver a sembrar no
            // se reasignan ventas que ya existían a nombre de otro usuario.
            if (! $record->exists) {
                $record->user_id = $cashier->id;
            }

            $record->save();
        }
    }
}
