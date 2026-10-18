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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('tipo', ['encargado', 'empleado'])->default('empleado')->after('email');
        });

        // Los usuarios ya existentes pasan a "encargado" para no perder acceso a
        // las secciones restringidas cuando se activen los permisos (fase 4).
        DB::table('users')->update(['tipo' => 'encargado']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
