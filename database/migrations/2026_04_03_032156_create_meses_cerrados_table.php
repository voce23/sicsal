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
        Schema::create('meses_cerrados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->foreignId('cerrado_por')->constrained('users');
            $table->timestamp('fecha_cierre')->useCurrent();
            $table->foreignId('reabierto_por')->nullable()->constrained('users');
            $table->timestamp('fecha_reapertura')->nullable();
            $table->timestamps();
            $table->unique(['centro_salud_id', 'mes', 'anio'], 'mc_centro_mes_anio_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meses_cerrados');
    }
};
