<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Amplía el nombre del producto de 20 a 30 caracteres.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('name', 30)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Falla si ya existen nombres de más de 20 caracteres.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('name', 20)->change();
        });
    }
};
