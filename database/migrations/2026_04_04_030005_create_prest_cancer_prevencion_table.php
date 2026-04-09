<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prest_cancer_prevencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->enum('indicador', [
                'pap_nueva',
                'pap_repetida',
                'ivaa_nueva',
                'ivaa_repetida',
                'ivaa_positivo',
                'crioterapia',
                'examen_mama_nueva',
                'examen_mama_repetida',
                'mamografia_referida',
            ]);
            $table->integer('cantidad')->default(0);
            $table->timestamps();

            $table->unique(
                ['centro_salud_id', 'mes', 'anio', 'indicador'],
                'pcancer_centro_mes_anio_indicador_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prest_cancer_prevencion');
    }
};
