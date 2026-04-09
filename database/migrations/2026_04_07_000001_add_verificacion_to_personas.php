<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ampliar el enum estado para incluir 'fallecido' (MySQL/MariaDB only; SQLite doesn't support MODIFY COLUMN)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE personas MODIFY COLUMN estado ENUM('residente','temporal','migrado','fallecido') NOT NULL DEFAULT 'residente'");
        }

        Schema::table('personas', function (Blueprint $table) {
            $table->boolean('verificado')->default(false)->after('observaciones');
            $table->date('fecha_verificacion')->nullable()->after('verificado');
            $table->foreignId('verificado_por')->nullable()->after('fecha_verificacion')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropForeign(['verificado_por']);
            $table->dropColumn(['verificado', 'fecha_verificacion', 'verificado_por']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE personas MODIFY COLUMN estado ENUM('residente','temporal','migrado') NOT NULL DEFAULT 'residente'");
        }
    }
};
