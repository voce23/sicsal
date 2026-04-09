<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Prevenir duplicados de vacuna (misma persona, tipo y fecha)
        Schema::table('vacunas_ninos', function (Blueprint $table) {
            $table->unique(['persona_id', 'tipo_vacuna', 'fecha_aplicacion'], 'vacunas_persona_tipo_fecha_unique');
        });

        // Prevenir duplicados de crecimiento (misma persona y fecha)
        Schema::table('crecimiento_infantil', function (Blueprint $table) {
            $table->unique(['persona_id', 'fecha'], 'crecimiento_persona_fecha_unique');
        });

        // Prevenir duplicados de micronutriente (misma persona, tipo y fecha)
        Schema::table('micronutrientes_ninos', function (Blueprint $table) {
            $table->unique(['persona_id', 'tipo', 'fecha'], 'micronutrientes_persona_tipo_fecha_unique');
        });

        // Prevenir duplicados de anticoncepción (misma persona, método y fecha)
        Schema::table('anticoncepcion', function (Blueprint $table) {
            $table->unique(['persona_id', 'metodo', 'fecha'], 'anticoncepcion_persona_metodo_fecha_unique');
        });

        // Prevenir duplicados de control prenatal (mismo embarazo y número de control)
        Schema::table('controles_prenatales', function (Blueprint $table) {
            $table->unique(['embarazo_id', 'numero_control'], 'controles_embarazo_numero_unique');
        });

        // Un solo parto por embarazo
        Schema::table('partos', function (Blueprint $table) {
            $table->unique(['embarazo_id'], 'partos_embarazo_unique');
        });

        // Un solo puerperio por parto
        Schema::table('puerperios', function (Blueprint $table) {
            $table->unique(['parto_id'], 'puerperios_parto_unique');
        });
    }

    public function down(): void
    {
        Schema::table('vacunas_ninos', fn (Blueprint $t) => $t->dropUnique('vacunas_persona_tipo_fecha_unique'));
        Schema::table('crecimiento_infantil', fn (Blueprint $t) => $t->dropUnique('crecimiento_persona_fecha_unique'));
        Schema::table('micronutrientes_ninos', fn (Blueprint $t) => $t->dropUnique('micronutrientes_persona_tipo_fecha_unique'));
        Schema::table('anticoncepcion', fn (Blueprint $t) => $t->dropUnique('anticoncepcion_persona_metodo_fecha_unique'));
        Schema::table('controles_prenatales', fn (Blueprint $t) => $t->dropUnique('controles_embarazo_numero_unique'));
        Schema::table('partos', fn (Blueprint $t) => $t->dropUnique('partos_embarazo_unique'));
        Schema::table('puerperios', fn (Blueprint $t) => $t->dropUnique('puerperios_parto_unique'));
    }
};
