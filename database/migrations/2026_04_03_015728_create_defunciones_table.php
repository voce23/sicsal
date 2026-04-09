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
        Schema::create('defunciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->nullable()->constrained('personas');
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('sexo', ['M', 'F']);
            $table->foreignId('comunidad_id')->nullable()->constrained('comunidades');
            $table->date('fecha_defuncion');
            $table->text('causa_defuncion')->nullable();
            $table->enum('lugar', ['establecimiento', 'domicilio', 'referido', 'en_transito']);
            $table->enum('grupo_etareo_defuncion', ['neonatal', 'infantil_menor_1', 'menor_5', '5_a_59', 'adulto_mayor']);
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('defunciones');
    }
};
