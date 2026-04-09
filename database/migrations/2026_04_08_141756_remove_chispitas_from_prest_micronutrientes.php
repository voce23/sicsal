<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar cualquier registro residual antes de modificar el ENUM
        DB::table('prest_micronutrientes')->where('tipo', 'chispitas_6_23m')->delete();

        // MySQL/MariaDB only; SQLite doesn't support MODIFY COLUMN
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE prest_micronutrientes MODIFY COLUMN tipo ENUM(
                'hierro_embarazadas_completo','hierro_puerperas_completo',
                'hierro_menor_6m','hierro_menor_1','hierro_1anio','hierro_2_5',
                'vitA_puerpera_unica','vitA_menor_1_unica',
                'vitA_1anio_1ra','vitA_1anio_2da','vitA_2_5_1ra','vitA_2_5_2da',
                'zinc_menor_1','zinc_1anio',
                'nutribebe_menor_1','nutribebe_1anio',
                'nutrimama_embarazada','nutrimama_lactancia',
                'carmelo_mayor_60'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE prest_micronutrientes MODIFY COLUMN tipo ENUM(
                'hierro_embarazadas_completo','hierro_puerperas_completo',
                'hierro_menor_6m','hierro_menor_1','hierro_1anio','hierro_2_5',
                'vitA_puerpera_unica','vitA_menor_1_unica',
                'vitA_1anio_1ra','vitA_1anio_2da','vitA_2_5_1ra','vitA_2_5_2da',
                'zinc_menor_1','zinc_1anio',
                'nutribebe_menor_1','nutribebe_1anio',
                'nutrimama_embarazada','nutrimama_lactancia',
                'carmelo_mayor_60','chispitas_6_23m'
            ) NOT NULL");
        }
    }
};
