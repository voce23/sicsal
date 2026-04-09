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
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_salud_id')->constrained('centros_salud');
            $table->foreignId('comunidad_id')->constrained('comunidades');
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->date('fecha_nacimiento');
            $table->enum('sexo', ['M', 'F']);
            $table->string('ci', 20)->nullable();
            $table->enum('tipo_seguro', ['SUS', 'privado', 'ninguno'])->default('ninguno');
            $table->enum('estado', ['residente', 'temporal', 'migrado'])->default('residente');
            $table->string('destino_migracion', 150)->nullable();
            $table->date('fecha_migracion')->nullable();
            $table->string('grupo_etareo', 30)->nullable();
            $table->date('fecha_registro');
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
