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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 20)->nullable();
            $table->string('customer_phone', 10)->nullable();
            $table->dateTime('sold_at');
            $table->decimal('total', 10, 1);
            $table->enum('status', ['pagada', 'fiada']);
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('sale_discount', 10, 1);
            $table->decimal('cash', 10, 1);
            $table->decimal('qr', 10, 1);
            $table->decimal('debt', 10, 1);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_total_check CHECK (total >= 0)');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_sale_discount_check CHECK (sale_discount >= 0)');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_cash_check CHECK (cash >= 0)');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_qr_check CHECK (qr >= 0)');
        DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_debt_check CHECK (debt >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
