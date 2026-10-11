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
            $presentation = ProductPresentation::with('product')->where('barcode', $detail['barcode'])->firstOrFail();

            $quantity = (int) $detail['quantity'];
            $conversionFactor = (int) $presentation->conversion_factor;
            $unitPrice = (float) $presentation->sale_price;

            SaleDetail::updateOrCreate(
                [
                    'sale_id' => $sales[$detail['customer']],
                    'presentation_id' => $presentation->id,
                ],
                [
                    'quantity' => $quantity,
                    'conversion_factor' => $conversionFactor,
                    'base_quantity' => $conversionFactor * $quantity,
                    'unit_price' => round($unitPrice, 1),
                    'base_unit_cost_at_sale' => round((float) ($presentation->product?->base_unit_cost ?? 0), 2),
                    'subtotal' => round($quantity * $unitPrice, 1),
                ],
            );
        }
    }
}
