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
     * "sale_notes" es una nota de venta (preventa/pedido) que suele tomarse fuera
     * del local: NO mueve stock. Al entregarse se materializa como una venta
     * ("sale_id") y recién entonces se descuenta stock. El pago no vive aquí, se
     * captura al entregar y va a la venta generada.
     */
    public function up(): void
    {
        Schema::create('sale_notes', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 20)->nullable();
            $table->string('customer_phone', 10)->nullable();
            $table->dateTime('noted_at');
            $table->dateTime('scheduled_for')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->decimal('delivery_latitude', 10, 7)->nullable();
            $table->decimal('delivery_longitude', 10, 7)->nullable();
            $table->decimal('sale_discount', 10, 1);
            $table->decimal('total', 10, 1);
            $table->enum('status', ['pendiente', 'entregada', 'anulada'])->default('pendiente');
            $table->foreignId('sale_id')->nullable()->constrained('sales');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE sale_notes ADD CONSTRAINT sale_notes_sale_discount_check CHECK (sale_discount >= 0)');
        DB::statement('ALTER TABLE sale_notes ADD CONSTRAINT sale_notes_total_check CHECK (total >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_notes');
    }
};
