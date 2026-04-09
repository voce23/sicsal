<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prest_ile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            // Form 301 F63-F65
            $table->enum('indicador', [
                'ile_1er_trimestre',   // F63
                'ile_2do_trimestre',   // F64
                'ile_3er_trimestre',   // F65
            ]);
            $table->integer('cantidad')->default(0);
            $table->timestamps();
            $table->unique(['centro_salud_id', 'mes', 'anio', 'indicador'], 'pile_centro_mes_anio_ind_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prest_ile');
    }
};
