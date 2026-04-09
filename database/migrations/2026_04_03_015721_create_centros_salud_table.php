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
        Schema::create('centros_salud', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipio_id')->constrained('municipios');
            $table->string('nombre', 150);
            $table->string('codigo_snis', 20)->nullable();
            $table->enum('subsector', ['Público', 'Seguro Social', 'Privado', 'ONG'])->default('Público');
            $table->string('red_salud', 100)->nullable();
            $table->integer('poblacion_ine')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centros_salud');
    }
};
