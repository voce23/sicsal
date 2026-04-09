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
        Schema::create('prest_consulta_externa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->enum('grupo_etareo', [
                'menor_6m', '6m_menor_1', '1_4', '5_9', '10_14',
                '15_19', '20_39', '40_49', '50_59', 'mayor_60',
            ]);
            $table->integer('primera_m')->default(0);
            $table->integer('primera_f')->default(0);
            $table->integer('nueva_m')->default(0);
            $table->integer('nueva_f')->default(0);
            $table->integer('repetida_m')->default(0);
            $table->integer('repetida_f')->default(0);
            $table->timestamps();
            $table->unique(['centro_salud_id', 'mes', 'anio', 'grupo_etareo'], 'pce_centro_mes_anio_grupo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prest_consulta_externa');
    }
};
