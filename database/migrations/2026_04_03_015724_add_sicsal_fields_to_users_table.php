<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('apellidos', 100)->after('name');
            $table->string('usuario', 50)->unique()->after('apellidos');
            $table->foreignId('centro_salud_id')->nullable()->after('usuario')->constrained('centros_salud');
            $table->boolean('activo')->default(true)->after('centro_salud_id');
            $table->timestamp('ultimo_acceso')->nullable()->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['centro_salud_id']);
            $table->dropColumn(['apellidos', 'usuario', 'centro_salud_id', 'activo', 'ultimo_acceso']);
        });
    }
};
