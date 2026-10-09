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
        Schema::create('product_presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('unit_id')->constrained('units');
            $table->integer('conversion_factor');
            $table->decimal('sale_price', 10, 1);
            $table->boolean('purchase_enable');
            $table->boolean('sale_enable');
            $table->string('barcode', 20)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE product_presentations ADD CONSTRAINT product_presentations_conversion_factor_check CHECK (conversion_factor >= 0)');
        DB::statement('ALTER TABLE product_presentations ADD CONSTRAINT product_presentations_sale_price_check CHECK (sale_price >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_presentations');
    }
};
