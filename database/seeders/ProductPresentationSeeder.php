<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductPresentationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::pluck('id', 'name');
        $units = Unit::pluck('id', 'symbol');

        $presentations = [
            ['product' => 'Arroz', 'unit' => 'kg', 'conversion_factor' => 1, 'reference_purchase_cost' => 9.5, 'desired_profit' => 3.0, 'sale_price' => 12.5, 'purchase_enable' => false, 'barcode' => '1000000001'],
            ['product' => 'Arroz', 'unit' => 'cja', 'conversion_factor' => 10, 'reference_purchase_cost' => 95.0, 'desired_profit' => 25.0, 'sale_price' => 120.0, 'purchase_enable' => true, 'barcode' => '1000000002'],
            ['product' => 'Azúcar', 'unit' => 'kg', 'conversion_factor' => 1, 'reference_purchase_cost' => 7.8, 'desired_profit' => 2.2, 'sale_price' => 10.0, 'purchase_enable' => true, 'barcode' => '1000000003'],
            ['product' => 'Leche', 'unit' => 'l', 'conversion_factor' => 1, 'reference_purchase_cost' => 6.0, 'desired_profit' => 2.0, 'sale_price' => 8.0, 'purchase_enable' => true, 'barcode' => '1000000004'],
            ['product' => 'Aceite', 'unit' => 'l', 'conversion_factor' => 1, 'reference_purchase_cost' => 12.0, 'desired_profit' => 3.0, 'sale_price' => 15.0, 'purchase_enable' => true, 'barcode' => '1000000005'],
            ['product' => 'Refresco', 'unit' => 'und', 'conversion_factor' => 1, 'reference_purchase_cost' => 2.1, 'desired_profit' => 0.9, 'sale_price' => 3.0, 'purchase_enable' => true, 'barcode' => '1000000006'],
            ['product' => 'Galletas', 'unit' => 'pqt', 'conversion_factor' => 1, 'reference_purchase_cost' => 4.0, 'desired_profit' => 1.5, 'sale_price' => 5.5, 'purchase_enable' => true, 'barcode' => '1000000007'],
            ['product' => 'Huevos', 'unit' => 'doc', 'conversion_factor' => 1, 'reference_purchase_cost' => 14.0, 'desired_profit' => 4.0, 'sale_price' => 18.0, 'purchase_enable' => true, 'barcode' => '1000000008'],
        ];

        foreach ($presentations as $presentation) {
            ProductPresentation::updateOrCreate(
                ['barcode' => $presentation['barcode']],
                [
                    'product_id' => $products[$presentation['product']],
                    'unit_id' => $units[$presentation['unit']],
                    'conversion_factor' => $presentation['conversion_factor'],
                    'reference_purchase_cost' => $presentation['reference_purchase_cost'],
                    'desired_profit' => $presentation['desired_profit'],
                    'sale_price' => $presentation['sale_price'],
                    'purchase_enable' => $presentation['purchase_enable'],
                    'sale_enable' => true,
                    'active' => true,
                ],
            );
        }
    }
}
