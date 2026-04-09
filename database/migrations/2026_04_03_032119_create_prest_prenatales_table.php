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
        Schema::create('prest_prenatales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->enum('tipo_control', [
                'nueva_1er_trim', 'nueva_2do_trim', 'nueva_3er_trim', 'repetida', 'con_4to_control',
            ]);
            $table->enum('grupo_etareo', ['menor_10', '10_14', '15_19', '20_34', '35_49', '50_mas']);
            $table->integer('dentro')->default(0);
            $table->integer('fuera')->default(0);
            $table->timestamps();
            $table->unique(['centro_salud_id', 'mes', 'anio', 'tipo_control', 'grupo_etareo'], 'ppre_centro_mes_anio_control_grupo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prest_prenatales');
    }
};
