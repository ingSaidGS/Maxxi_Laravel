<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * "supplier" y "voucher_number" pasan a ser opcionales: una compra puede
     * registrarse sin proveedor y/o sin número de comprobante.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('supplier', 15)->nullable()->change();
            $table->string('voucher_number', 8)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('supplier', 15)->nullable(false)->change();
            $table->string('voucher_number', 8)->nullable(false)->change();
        });
    }
};
