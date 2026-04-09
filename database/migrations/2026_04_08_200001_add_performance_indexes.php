<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->index('estado');
        });

        Schema::table('centros_salud', function (Blueprint $table) {
            $table->index('codigo_snis');
        });
    }

    public function down(): void
    {
        Schema::table('personas', fn (Blueprint $t) => $t->dropIndex(['estado']));
        Schema::table('centros_salud', fn (Blueprint $t) => $t->dropIndex(['codigo_snis']));
    }
};
