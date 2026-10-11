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
     * "active" permite quitar/reactivar una línea de la nota (p. ej. el cliente
     * cancela un producto antes de la entrega) sin perder el histórico: el total
     * de la nota y la venta generada al entregar consideran solo las líneas
     * activas.
     */
    public function up(): void
    {
        Schema::create('sale_note_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_note_id')->constrained('sale_notes');
            $table->foreignId('presentation_id')->constrained('product_presentations');
            $table->integer('quantity');
            $table->integer('conversion_factor');
            $table->integer('base_quantity');
            $table->decimal('unit_price', 10, 1);
            $table->decimal('base_unit_cost_at_note', 10, 2);
            $table->decimal('subtotal', 10, 1);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE sale_note_details ADD CONSTRAINT sale_note_details_quantity_check CHECK (quantity >= 0)');
        DB::statement('ALTER TABLE sale_note_details ADD CONSTRAINT sale_note_details_conversion_factor_check CHECK (conversion_factor >= 0)');
        DB::statement('ALTER TABLE sale_note_details ADD CONSTRAINT sale_note_details_base_quantity_check CHECK (base_quantity >= 0)');
        DB::statement('ALTER TABLE sale_note_details ADD CONSTRAINT sale_note_details_unit_price_check CHECK (unit_price >= 0)');
        DB::statement('ALTER TABLE sale_note_details ADD CONSTRAINT sale_note_details_base_unit_cost_at_note_check CHECK (base_unit_cost_at_note >= 0)');
        DB::statement('ALTER TABLE sale_note_details ADD CONSTRAINT sale_note_details_subtotal_check CHECK (subtotal >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_note_details');
    }
};
