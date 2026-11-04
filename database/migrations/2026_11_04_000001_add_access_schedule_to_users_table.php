<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * El horario es opcional: si ambas columnas son null, el usuario no tiene
     * restricción horaria (comportamiento previo a esta funcionalidad).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->time('access_start_time')->nullable()->after('active');
            $table->time('access_end_time')->nullable()->after('access_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['access_start_time', 'access_end_time']);
        });
    }
};
