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
        Schema::create('prest_recien_nacidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->enum('indicador', [
                'nacidos_vivos_servicio', 'nacidos_vivos_domicilio',
                'nacidos_vivos_4cpn', 'nacidos_vivos_peso_menor_2500',
                'nacidos_muertos', 'rn_lactancia_inmediata',
                'rn_alojamiento_conjunto', 'rn_corte_tardio_cordon',
                'rn_malformacion_congenita', 'rn_control_48h',
            ]);
            $table->integer('cantidad')->default(0);
            $table->timestamps();
            $table->unique(['centro_salud_id', 'mes', 'anio', 'indicador'], 'prn_centro_mes_anio_indicador_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prest_recien_nacidos');
    }
};
