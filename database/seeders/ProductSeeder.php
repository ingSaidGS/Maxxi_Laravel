<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = Unit::pluck('id', 'symbol');

        $products = [
            ['name' => 'Arroz', 'base_unit_id' => $units['kg'], 'reference_purchase_cost' => 9.50],
            ['name' => 'Azúcar', 'base_unit_id' => $units['kg'], 'reference_purchase_cost' => 7.80],
            ['name' => 'Leche', 'base_unit_id' => $units['l'], 'reference_purchase_cost' => 6.00],
            ['name' => 'Aceite', 'base_unit_id' => $units['l'], 'reference_purchase_cost' => 12.00],
            ['name' => 'Refresco', 'base_unit_id' => $units['und'], 'reference_purchase_cost' => 2.10],
            ['name' => 'Galletas', 'base_unit_id' => $units['pqt'], 'reference_purchase_cost' => 4.00],
            ['name' => 'Huevos', 'base_unit_id' => $units['doc'], 'reference_purchase_cost' => 14.00],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['name' => $product['name']],
                $product + ['active' => true],
            );
        }
    }
}
