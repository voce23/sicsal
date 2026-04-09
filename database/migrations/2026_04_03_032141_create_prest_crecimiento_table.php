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
        Schema::create('prest_crecimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->enum('grupo_etareo', [
                'menor_1_dentro', 'menor_1_fuera',
                '1_menor_2_dentro', '1_menor_2_fuera',
                '2_menor_5_dentro', '2_menor_5_fuera',
            ]);
            $table->integer('nuevos_m')->default(0);
            $table->integer('nuevos_f')->default(0);
            $table->integer('repetidos_m')->default(0);
            $table->integer('repetidos_f')->default(0);
            $table->timestamps();
            $table->unique(['centro_salud_id', 'mes', 'anio', 'grupo_etareo'], 'pcrec_centro_mes_anio_grupo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prest_crecimiento');
    }
};
