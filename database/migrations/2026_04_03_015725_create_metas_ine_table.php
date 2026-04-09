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
        Schema::create('metas_ine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->year('anio');
            $table->enum('grupo_etareo', [
                'menor_1', '1_anio', '2_anios', '3_anios', '4_anios',
                '1_4', '5_anios', '6_anios', 'menor_5', 'mayor_5',
                'menor_2', '2_3', '2_4', '5_9', '7_9', '10',
                '10_14', '15_19', '20_39', '40_49', '50_59', 'mayor_60',
                'mef_15_40', '7_49', 'adolescentes_10_19', 'mujeres_menor_20',
                'embarazos_esperados', 'partos_esperados', 'nacimientos_esperados', 'dt_7_49',
            ]);
            $table->enum('sexo', ['M', 'F', 'ambos']);
            $table->integer('cantidad')->default(0);
            $table->timestamps();
            $table->unique(['centro_salud_id', 'anio', 'grupo_etareo', 'sexo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metas_ine');
    }
};
