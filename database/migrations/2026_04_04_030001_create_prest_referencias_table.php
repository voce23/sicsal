<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prest_referencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->enum('tipo', [
                'ref_recibida_comunidad',
                'ref_recibida_establecimiento',
                'ref_enviada',
                'contraref_recibida',
                'contraref_enviada',
                'pcd_atendida_establecimiento',
                'pcd_atendida_comunidad',
            ]);
            $table->integer('masculino')->default(0);
            $table->integer('femenino')->default(0);
            $table->timestamps();

            $table->unique(
                ['centro_salud_id', 'mes', 'anio', 'tipo'],
                'pref_centro_mes_anio_tipo_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prest_referencias');
    }
};
