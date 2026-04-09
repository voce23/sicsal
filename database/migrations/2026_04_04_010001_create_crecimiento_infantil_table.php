<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crecimiento_infantil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas');
            $table->date('fecha');
            $table->decimal('peso_kg', 5, 2)->nullable();
            $table->decimal('talla_cm', 5, 2)->nullable();
            $table->decimal('perimetro_cefalico_cm', 5, 2)->nullable();
            $table->enum('clasificacion', [
                'normal', 'desnutricion_aguda', 'desnutricion_cronica',
                'desnutricion_global', 'sobrepeso', 'obesidad',
            ]);
            $table->enum('tipo_control', ['nuevo', 'repetido'])->default('nuevo');
            $table->enum('dentro_fuera', ['dentro', 'fuera'])->default('dentro');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crecimiento_infantil');
    }
};
