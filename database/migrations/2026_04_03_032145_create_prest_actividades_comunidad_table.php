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
        Schema::create('prest_actividades_comunidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->enum('tipo_actividad', [
                'actividades_con_comunidad', 'cai_establecimiento',
                'comunidades_en_cai', 'familias_nuevas_carpetizadas',
                'familias_seguimiento', 'visitas_primeras',
                'visitas_segundas', 'visitas_terceras',
                'reuniones_autoridades', 'reuniones_comites_salud',
                'actividades_educativas_salud',
                'pcd_atendidas_establecimiento', 'pcd_atendidas_comunidad',
            ]);
            $table->integer('cantidad')->default(0);
            $table->timestamps();
            $table->unique(['centro_salud_id', 'mes', 'anio', 'tipo_actividad'], 'pac_centro_mes_anio_actividad_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prest_actividades_comunidad');
    }
};
