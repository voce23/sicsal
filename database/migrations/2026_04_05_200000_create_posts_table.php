<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->text('extracto')->nullable();
            $table->longText('contenido')->nullable();
            $table->string('imagen_portada')->nullable();
            $table->string('categoria', 50)->default('general')->index();
            $table->json('etiquetas')->nullable();
            $table->boolean('publicado')->default(false)->index();
            $table->timestamp('publicado_at')->nullable();
            $table->string('autor_nombre', 100)->default('SICSAL');
            $table->unsignedBigInteger('vistas')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
