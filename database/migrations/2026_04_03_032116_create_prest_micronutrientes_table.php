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
        Schema::create('prest_micronutrientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->enum('tipo', [
                'hierro_embarazadas_completo', 'hierro_puerperas_completo',
                'hierro_menor_6m', 'hierro_menor_1', 'hierro_1anio', 'hierro_2_5',
                'vitA_puerpera_unica', 'vitA_menor_1_unica',
                'vitA_1anio_1ra', 'vitA_1anio_2da',
                'vitA_2_5_1ra', 'vitA_2_5_2da',
                'zinc_menor_1', 'zinc_1anio',
                'nutribebe_menor_1', 'nutribebe_1anio',
                'nutrimama_embarazada', 'nutrimama_lactancia',
                'carmelo_mayor_60', 'chispitas_6_23m',
            ]);
            $table->integer('cantidad')->default(0);
            $table->timestamps();
            $table->unique(['centro_salud_id', 'mes', 'anio', 'tipo'], 'pm_centro_mes_anio_tipo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prest_micronutrientes');
    }
};
