<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('causas_consulta_externa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->string('diagnostico', 255);
            $table->enum('grupo_etareo', [
                'menor_6m', '6m_menor_1', '1_4', '5_9', '10_14',
                '15_19', '20_39', '40_49', '50_59', 'mayor_60',
            ]);
            $table->integer('masculino')->default(0);
            $table->integer('femenino')->default(0);
            $table->timestamps();
            $table->unique(
                ['centro_salud_id', 'mes', 'anio', 'diagnostico', 'grupo_etareo'],
                'cce_unico'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('causas_consulta_externa');
    }
};
