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
        Schema::create('prest_vacunas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->string('tipo_vacuna', 60);
            $table->string('grupo_etareo', 30);
            $table->integer('dentro_m')->default(0);
            $table->integer('dentro_f')->default(0);
            $table->integer('fuera_m')->default(0);
            $table->integer('fuera_f')->default(0);
            $table->timestamps();
            $table->unique(['centro_salud_id', 'mes', 'anio', 'tipo_vacuna', 'grupo_etareo'], 'pv_centro_mes_anio_vacuna_grupo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prest_vacunas');
    }
};
