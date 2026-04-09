<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paso 1: agregar columna
        Schema::table('causas_consulta_externa', function (Blueprint $table) {
            $table->tinyInteger('posicion')->default(1)->after('anio');
        });

        // Paso 2: crear nuevo índice único con posicion (MySQL lo acepta si ya hay otro cubriendo FK)
        Schema::table('causas_consulta_externa', function (Blueprint $table) {
            $table->unique(
                ['centro_salud_id', 'mes', 'anio', 'posicion', 'grupo_etareo'],
                'cce_pos_unico'
            );
        });

        // Paso 3: eliminar el índice viejo (ahora MySQL tiene el nuevo para cubrir la FK)
        Schema::table('causas_consulta_externa', function (Blueprint $table) {
            $table->dropUnique('cce_unico');
        });
    }

    public function down(): void
    {
        Schema::table('causas_consulta_externa', function (Blueprint $table) {
            $table->unique(
                ['centro_salud_id', 'mes', 'anio', 'diagnostico', 'grupo_etareo'],
                'cce_unico'
            );
        });

        Schema::table('causas_consulta_externa', function (Blueprint $table) {
            $table->dropUnique('cce_pos_unico');
            $table->dropColumn('posicion');
        });
    }
};
