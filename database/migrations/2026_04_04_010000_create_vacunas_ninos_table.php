<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacunas_ninos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas');
            $table->enum('tipo_vacuna', [
                'BCG', 'HepB_neonatal',
                'Pentavalente_1', 'Pentavalente_2', 'Pentavalente_3',
                'Pentavalente_4_refuerzo', 'Pentavalente_5_refuerzo',
                'IPV_1', 'bOPV_2', 'IPV_3', 'bOPV_4', 'bOPV_5',
                'Antirotavirica_1', 'Antirotavirica_2',
                'Antineumococica_1', 'Antineumococica_2', 'Antineumococica_3',
                'Influenza_1', 'Influenza_2', 'Influenza_unica',
                'SRP_1', 'SRP_2', 'Antiamarilica',
                'VPH_1', 'VPH_2',
                'dT_1', 'dT_2', 'dT_3', 'dT_4', 'dT_5', 'SR',
            ]);
            $table->date('fecha_aplicacion');
            $table->enum('dentro_fuera', ['dentro', 'fuera'])->default('dentro');
            $table->string('lote', 50)->nullable();
            $table->foreignId('aplicado_por')->nullable()->constrained('users');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacunas_ninos');
    }
};
