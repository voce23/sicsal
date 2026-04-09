<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anticoncepcion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas');
            $table->enum('metodo', [
                'DIU', 'inyectable_mensual', 'inyectable_trimestral', 'pildora',
                'condon_masculino', 'condon_femenino', 'implante_subdermic',
                'metodos_naturales', 'AQV_femenino', 'AQV_masculino', 'pildora_emergencia',
            ]);
            $table->enum('tipo_usuaria', ['nueva', 'continua']);
            $table->date('fecha');
            $table->tinyInteger('mes');
            $table->year('anio');
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anticoncepcion');
    }
};
