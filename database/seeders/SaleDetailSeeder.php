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

        $details = [
            ['customer' => 'Juan Pérez', 'barcode' => '1000000001', 'quantity' => 2],
            ['customer' => 'Juan Pérez', 'barcode' => '1000000004', 'quantity' => 1],
            ['customer' => 'María López', 'barcode' => '1000000006', 'quantity' => 6],
            ['customer' => 'María López', 'barcode' => '1000000007', 'quantity' => 2],
            ['customer' => 'Carlos Ruiz', 'barcode' => '1000000005', 'quantity' => 1],
        ];

        foreach ($details as $detail) {
            $presentation = ProductPresentation::where('barcode', $detail['barcode'])->firstOrFail();

            SaleDetail::updateOrCreate(
                [
                    'sale_id' => $sales[$detail['customer']],
                    'presentation_id' => $presentation->id,
                ],
                [
                    'quantity' => $detail['quantity'],
                    'conversion_factor' => $presentation->conversion_factor,
                    'sale_enable' => $presentation->sale_enable,
                    'subtotal' => round($detail['quantity'] * (float) $presentation->sale_price, 1),
                ],
            );
        }
    }
}
