<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'Kilogramo', 'symbol' => 'kg'],
            ['name' => 'Gramo', 'symbol' => 'g'],
            ['name' => 'Litro', 'symbol' => 'l'],
            ['name' => 'Mililitro', 'symbol' => 'ml'],
            ['name' => 'Unidad', 'symbol' => 'und'],
            ['name' => 'Caja', 'symbol' => 'cja'],
            ['name' => 'Paquete', 'symbol' => 'pqt'],
            ['name' => 'Docena', 'symbol' => 'doc'],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['symbol' => $unit['symbol']],
                $unit + ['active' => true],
            );
        }
    }
}
