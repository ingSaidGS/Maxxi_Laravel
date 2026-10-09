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
        Schema::create('sale_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales');
            $table->foreignId('presentation_id')->constrained('product_presentations');
            $table->integer('quantity');
            $table->integer('conversion_factor');
            $table->boolean('sale_enable');
            $table->decimal('subtotal', 10, 1);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE sale_details ADD CONSTRAINT sale_details_quantity_check CHECK (quantity >= 0)');
        DB::statement('ALTER TABLE sale_details ADD CONSTRAINT sale_details_conversion_factor_check CHECK (conversion_factor >= 0)');
        DB::statement('ALTER TABLE sale_details ADD CONSTRAINT sale_details_subtotal_check CHECK (subtotal >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_details');
    }
};
