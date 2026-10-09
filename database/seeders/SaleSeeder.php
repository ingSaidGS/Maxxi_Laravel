<?php

namespace Database\Seeders;

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
        $cashier = User::firstOrCreate(
            ['email' => 'cajero@example.com'],
            ['name' => 'Cajero', 'password' => 'password', 'email_verified_at' => now()],
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
            Sale::updateOrCreate(
                ['customer_name' => $sale['customer_name']],
                $sale + ['user_id' => $cashier->id],
            );
        }
    }
}
