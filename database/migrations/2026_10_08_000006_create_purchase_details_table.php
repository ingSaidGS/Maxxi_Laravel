<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases');
            $table->foreignId('presentation_id')->constrained('product_presentations');
            $table->integer('quantity');
            $table->integer('conversion_factor');
            $table->integer('base_quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE purchase_details ADD CONSTRAINT purchase_details_quantity_check CHECK (quantity >= 0)');
        DB::statement('ALTER TABLE purchase_details ADD CONSTRAINT purchase_details_conversion_factor_check CHECK (conversion_factor >= 0)');
        DB::statement('ALTER TABLE purchase_details ADD CONSTRAINT purchase_details_base_quantity_check CHECK (base_quantity >= 0)');
        DB::statement('ALTER TABLE purchase_details ADD CONSTRAINT purchase_details_unit_price_check CHECK (unit_price >= 0)');
        DB::statement('ALTER TABLE purchase_details ADD CONSTRAINT purchase_details_subtotal_check CHECK (subtotal >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_details');
    }
};
