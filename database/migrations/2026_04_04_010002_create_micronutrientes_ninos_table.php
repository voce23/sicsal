<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('micronutrientes_ninos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas');
            $table->enum('tipo', [
                'hierro_menor_6m', 'hierro_6m_1anio', 'hierro_1anio', 'hierro_2_5anios',
                'vitamina_a_menor_1', 'vitamina_a_1anio_1ra', 'vitamina_a_1anio_2da',
                'vitamina_a_2_5_1ra', 'vitamina_a_2_5_2da',
                'zinc_menor_1', 'zinc_1anio',
                'chispitas_6_23m', 'nutribebe_menor_1', 'nutribebe_1anio',
            ]);
            $table->date('fecha');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('micronutrientes_ninos');
    }
};
