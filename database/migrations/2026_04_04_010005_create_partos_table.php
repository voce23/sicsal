<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('embarazo_id')->constrained('embarazos');
            $table->date('fecha_parto');
            $table->enum('tipo', ['vaginal', 'cesarea']);
            $table->enum('lugar', ['servicio', 'domicilio']);
            $table->enum('atendido_por', [
                'personal_calificado', 'partera_empirica',
                'partera_capacitada', 'articulacion', 'otros',
            ]);
            $table->string('grupo_etareo', 20)->nullable();
            $table->enum('resultado', ['nacido_vivo', 'nacido_muerto'])->default('nacido_vivo');
            $table->decimal('peso_rn_kg', 4, 3)->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partos');
    }
};
