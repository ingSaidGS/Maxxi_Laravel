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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('supplier', 15);
            $table->dateTime('purchased_at');
            $table->string('voucher_number', 8);
            $table->decimal('total', 10, 2);
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE purchases ADD CONSTRAINT purchases_total_check CHECK (total >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
