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
        Schema::create('justificaciones_cero', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->enum('indicador', [
                'control_prenatal', 'partos', 'puerperio',
                'vacunacion_menor_5', 'control_crecimiento', 'micronutrientes_menor_5',
            ]);
            $table->enum('motivo', [
                'no_hay_poblacion_activa_padron',
                'poblacion_migrada_temporal',
                'atendida_otro_centro',
                'no_se_presento_razon_desconocida',
                'otro',
            ]);
            $table->string('detalle', 300)->nullable();
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();
            $table->unique(['centro_salud_id', 'mes', 'anio', 'indicador'], 'jc_centro_mes_anio_indicador_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('justificaciones_cero');
    }
};
