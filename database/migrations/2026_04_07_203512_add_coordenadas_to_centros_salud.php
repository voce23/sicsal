<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centros_salud', function (Blueprint $table) {
            $table->decimal('latitud',  10, 6)->nullable()->after('poblacion_ine');
            $table->decimal('longitud', 10, 6)->nullable()->after('latitud');
        });
    }

    public function down(): void
    {
        Schema::table('centros_salud', function (Blueprint $table) {
            $table->dropColumn(['latitud', 'longitud']);
        });
    }
};
