<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prest_odontologia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->string('procedimiento', 60);
            $table->enum('grupo_etareo', [
                'menor_5', '5_9', '10_14', '15_19',
                '20_39', '40_49', '50_59', 'mayor_60',
            ]);
            $table->integer('masculino')->default(0);
            $table->integer('femenino')->default(0);
            $table->timestamps();

            $table->unique(
                ['centro_salud_id', 'mes', 'anio', 'procedimiento', 'grupo_etareo'],
                'podon_centro_mes_anio_proc_grupo_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prest_odontologia');
    }
};
