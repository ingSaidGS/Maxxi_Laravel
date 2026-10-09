<?php

namespace Database\Seeders;

use App\Models\ProductPresentation;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Database\Seeder;

class SaleDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sales = Sale::pluck('id', 'customer_name');
        $presentations = ProductPresentation::pluck('id', 'barcode');

        $details = [
            ['customer' => 'Juan Pérez', 'barcode' => '1000000001', 'quantity' => 2, 'conversion_factor' => 1, 'subtotal' => 25.0],
            ['customer' => 'Juan Pérez', 'barcode' => '1000000004', 'quantity' => 1, 'conversion_factor' => 1, 'subtotal' => 8.0],
            ['customer' => 'María López', 'barcode' => '1000000006', 'quantity' => 6, 'conversion_factor' => 1, 'subtotal' => 18.0],
            ['customer' => 'María López', 'barcode' => '1000000007', 'quantity' => 2, 'conversion_factor' => 1, 'subtotal' => 11.0],
            ['customer' => 'Carlos Ruiz', 'barcode' => '1000000005', 'quantity' => 1, 'conversion_factor' => 1, 'subtotal' => 15.0],
        ];

        foreach ($details as $detail) {
            SaleDetail::updateOrCreate(
                [
                    'sale_id' => $sales[$detail['customer']],
                    'presentation_id' => $presentations[$detail['barcode']],
                ],
                [
                    'quantity' => $detail['quantity'],
                    'conversion_factor' => $detail['conversion_factor'],
                    'sale_enable' => true,
                    'subtotal' => $detail['subtotal'],
                ],
            );
        }
    }
}
