<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * "picked_quantity" es el contador de alistamiento (picking) por línea de
     * nota de venta: se incrementa con cada escaneo del código de barras de la
     * presentación y sirve para saber cuánto se ha recogido de lo pedido
     * ("quantity" = paquetes pedidos). NO mueve stock: el stock se descuenta
     * recién al entregar la nota.
     *
     * No se guarda un "estado" aparte: se deriva de comparar "picked_quantity"
     * contra "quantity" (0 = pendiente, menor = parcial, igual o mayor =
     * completo).
     */
    public function up(): void
    {
        Schema::table('sale_note_details', function (Blueprint $table) {
            $table->integer('picked_quantity')->default(0);
        });

        DB::statement('ALTER TABLE sale_note_details ADD CONSTRAINT sale_note_details_picked_quantity_check CHECK (picked_quantity >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_note_details', function (Blueprint $table) {
            $table->dropColumn('picked_quantity');
        });
    }
};
