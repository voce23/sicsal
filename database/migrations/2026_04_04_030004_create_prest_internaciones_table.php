<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prest_internaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->enum('indicador', [
                'egresos',
                'fallecidos',
                'dias_estancia_egresos',
                'dias_cama_disponible',
                'dias_cama_ocupada',
                'infecciones_intrahospitalarias',
            ]);
            $table->integer('cantidad')->default(0);
            $table->timestamps();

            $table->unique(
                ['centro_salud_id', 'mes', 'anio', 'indicador'],
                'pint_centro_mes_anio_indicador_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prest_internaciones');
    }
};
