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
        Schema::create('prest_partos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->enum('tipo', ['vaginal', 'cesarea']);
            $table->enum('lugar', ['servicio', 'domicilio']);
            $table->enum('atendido_por', [
                'personal_calificado', 'partera_empirica', 'partera_capacitada', 'articulacion', 'otros',
            ]);
            $table->enum('grupo_etareo', ['menor_10', '10_14', '15_19', '20_34', '35_49', '50_mas']);
            $table->integer('cantidad')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prest_partos');
    }
};
