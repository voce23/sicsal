<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Rename IPV_3 → bOPV_3 in enum and existing data
        DB::statement("UPDATE vacunas_ninos SET tipo_vacuna = 'bOPV_3' WHERE tipo_vacuna = 'IPV_3'");

        // 2. Rename old dT doses to new scheme
        DB::statement("UPDATE vacunas_ninos SET tipo_vacuna = 'dT_7_9_1' WHERE tipo_vacuna = 'dT_1'");
        DB::statement("UPDATE vacunas_ninos SET tipo_vacuna = 'dT_7_9_2' WHERE tipo_vacuna = 'dT_2'");
        DB::statement("UPDATE vacunas_ninos SET tipo_vacuna = 'dT_10_49_1' WHERE tipo_vacuna = 'dT_3'");
        DB::statement("UPDATE vacunas_ninos SET tipo_vacuna = 'dT_10_49_2' WHERE tipo_vacuna = 'dT_4'");
        DB::statement("UPDATE vacunas_ninos SET tipo_vacuna = 'dT_10_49_3' WHERE tipo_vacuna = 'dT_5'");

        // 3. Alter the enum column with corrected values (MySQL/MariaDB only; SQLite doesn't support MODIFY COLUMN)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE vacunas_ninos MODIFY COLUMN tipo_vacuna ENUM(
                'BCG', 'HepB_neonatal',
                'Pentavalente_1', 'Pentavalente_2', 'Pentavalente_3',
                'Pentavalente_4_refuerzo', 'Pentavalente_5_refuerzo',
                'IPV_1', 'bOPV_2', 'bOPV_3', 'bOPV_4', 'bOPV_5',
                'Antirotavirica_1', 'Antirotavirica_2',
                'Antineumococica_1', 'Antineumococica_2', 'Antineumococica_3',
                'Influenza_1', 'Influenza_2', 'Influenza_unica',
                'SRP_1', 'SRP_2', 'Antiamarilica',
                'VPH_1', 'VPH_2',
                'dT_7_9_1', 'dT_7_9_2',
                'dT_10_49_1', 'dT_10_49_2', 'dT_10_49_3',
                'SR'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        // Revert data
        DB::statement("UPDATE vacunas_ninos SET tipo_vacuna = 'dT_5' WHERE tipo_vacuna = 'dT_10_49_3'");
        DB::statement("UPDATE vacunas_ninos SET tipo_vacuna = 'dT_4' WHERE tipo_vacuna = 'dT_10_49_2'");
        DB::statement("UPDATE vacunas_ninos SET tipo_vacuna = 'dT_3' WHERE tipo_vacuna = 'dT_10_49_1'");
        DB::statement("UPDATE vacunas_ninos SET tipo_vacuna = 'dT_2' WHERE tipo_vacuna = 'dT_7_9_2'");
        DB::statement("UPDATE vacunas_ninos SET tipo_vacuna = 'dT_1' WHERE tipo_vacuna = 'dT_7_9_1'");
        DB::statement("UPDATE vacunas_ninos SET tipo_vacuna = 'IPV_3' WHERE tipo_vacuna = 'bOPV_3'");

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE vacunas_ninos MODIFY COLUMN tipo_vacuna ENUM(
                'BCG', 'HepB_neonatal',
                'Pentavalente_1', 'Pentavalente_2', 'Pentavalente_3',
                'Pentavalente_4_refuerzo', 'Pentavalente_5_refuerzo',
                'IPV_1', 'bOPV_2', 'IPV_3', 'bOPV_4', 'bOPV_5',
                'Antirotavirica_1', 'Antirotavirica_2',
                'Antineumococica_1', 'Antineumococica_2', 'Antineumococica_3',
                'Influenza_1', 'Influenza_2', 'Influenza_unica',
                'SRP_1', 'SRP_2', 'Antiamarilica',
                'VPH_1', 'VPH_2',
                'dT_1', 'dT_2', 'dT_3', 'dT_4', 'dT_5',
                'SR'
            ) NOT NULL");
        }
    }
};
