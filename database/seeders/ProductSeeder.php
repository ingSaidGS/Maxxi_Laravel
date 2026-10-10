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
            ['name' => 'Arroz', 'base_unit_id' => $units['kg'], 'stock' => 50],
            ['name' => 'Azúcar', 'base_unit_id' => $units['kg'], 'stock' => 40],
            ['name' => 'Leche', 'base_unit_id' => $units['l'], 'stock' => 30],
            ['name' => 'Aceite', 'base_unit_id' => $units['l'], 'stock' => 25],
            ['name' => 'Refresco', 'base_unit_id' => $units['und'], 'stock' => 100],
            ['name' => 'Galletas', 'base_unit_id' => $units['pqt'], 'stock' => 60],
            ['name' => 'Huevos', 'base_unit_id' => $units['doc'], 'stock' => 20],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['name' => $product['name']],
                $product + ['active' => true],
            );
        }
    }
}
