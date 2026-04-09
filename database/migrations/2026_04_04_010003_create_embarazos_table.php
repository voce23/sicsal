<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embarazos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_probable_parto')->nullable();
            $table->tinyInteger('semanas_gestacion_ingreso')->nullable();
            $table->enum('estado', [
                'activa', 'migrada_temporal', 'atendida_otro_centro', 'culminada', 'perdida',
            ])->default('activa');
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embarazos');
    }
};
