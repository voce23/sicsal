<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controles_prenatales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('embarazo_id')->constrained('embarazos');
            $table->tinyInteger('numero_control');
            $table->date('fecha');
            $table->tinyInteger('semanas_gestacion')->nullable();
            $table->enum('dentro_fuera', ['dentro', 'fuera'])->default('dentro');
            $table->string('grupo_etareo', 20)->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controles_prenatales');
    }
};
