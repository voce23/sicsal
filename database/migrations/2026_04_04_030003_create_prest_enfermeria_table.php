<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prest_enfermeria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->enum('tipo', [
                'sueros_administrados',
                'inyecciones_administradas',
                'curaciones',
                'nebulizaciones',
                'cirugia_menor',
                'cirugia_mayor',
                'atencion_emergencia',
            ]);
            $table->integer('cantidad')->default(0);
            $table->timestamps();

            $table->unique(
                ['centro_salud_id', 'mes', 'anio', 'tipo'],
                'penf_centro_mes_anio_tipo_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prest_enfermeria');
    }
};
