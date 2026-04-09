<?php

namespace App\Observers\Concerns;

use Carbon\Carbon;

/**
 * Métodos compartidos de cálculo para sincronización de Prest* desde registros individuales.
 */
trait SincronizaPrestacion
{
    /**
     * Grupo etáreo para PrestVacuna, basado en edad en meses al momento de la vacuna.
     * Cubre tanto menores de 5 (gruposMenores5) como otras vacunas (gruposOtrasVac).
     */
    protected function grupoEtareoVacuna(int $edadMeses): string
    {
        if ($edadMeses < 12) return 'menor_1';
        if ($edadMeses < 24) return '12_23m';

        $anios = (int) floor($edadMeses / 12);

        if ($anios === 2) return '2_anios';
        if ($anios === 3) return '3_anios';
        if ($anios === 4) return '4_anios';
        if ($anios < 10) return '5_9';
        if ($anios === 10) return '10_anios';
        if ($anios === 11) return '11_anios';
        if ($anios <= 20) return '12_20';
        if ($anios < 60) return '21_59';

        return '60_mas';
    }

    /**
     * Grupo etáreo materno (prenatal, parto, anticoncepción).
     * Grupos: menor_10, 10_14, 15_19, 20_34, 35_49, 50_mas
     */
    protected function grupoEtareoMaterno(Carbon $fechaNac, Carbon $referencia): string
    {
        $anios = $fechaNac->diffInYears($referencia);

        if ($anios < 10) return 'menor_10';
        if ($anios <= 14) return '10_14';
        if ($anios <= 19) return '15_19';
        if ($anios <= 34) return '20_34';
        if ($anios <= 49) return '35_49';

        return '50_mas';
    }

    /**
     * Grupo etáreo para PrestCrecimiento (combina edad + dentro/fuera).
     * Grupos: menor_1_dentro, menor_1_fuera, 1_menor_2_dentro, etc.
     */
    protected function grupoCrecimiento(int $edadMeses, string $dentroDuera): string
    {
        $base = match (true) {
            $edadMeses < 12 => 'menor_1',
            $edadMeses < 24 => '1_menor_2',
            default         => '2_menor_5',
        };

        return $base . '_' . $dentroDuera;
    }

    /**
     * Tipo de control prenatal para PrestPrenatal, desde numero_control y semanas de gestación.
     */
    protected function tipoPrenatal(int $numeroControl, int $semanasGestacion): string
    {
        if ($numeroControl >= 4) return 'con_4to_control';
        if ($numeroControl > 1) return 'repetida';

        // Primer control → según trimestre
        if ($semanasGestacion <= 13) return 'nueva_1er_trim';
        if ($semanasGestacion <= 27) return 'nueva_2do_trim';

        return 'nueva_3er_trim';
    }

    /**
     * Campo de PrestVacuna / PrestCrecimiento según dentro_fuera y sexo (M/F).
     * Ejemplo: dentro + M → dentro_m
     */
    protected function campoDentroFueraSexo(string $dentroDuera, string $sexo): string
    {
        return $dentroDuera . '_' . strtolower($sexo);
    }
}
