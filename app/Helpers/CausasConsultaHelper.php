<?php

namespace App\Helpers;

use App\Models\CausaConsultaExterna;
use App\Models\CentroSalud;

class CausasConsultaHelper
{
    public const GRUPOS_LABEL = [
        'menor_6m'   => '<6m',
        '6m_menor_1' => '6m-1a',
        '1_4'        => '1-4a',
        '5_9'        => '5-9a',
        '10_14'      => '10-14a',
        '15_19'      => '15-19a',
        '20_39'      => '20-39a',
        '40_49'      => '40-49a',
        '50_59'      => '50-59a',
        'mayor_60'   => '≥60a',
    ];

    /**
     * Devuelve las 10 causas de consulta externa ingresadas en el período.
     * Ordena por total (suma M+F) descendente.
     *
     * @param int $centroSaludId  0 = todos los centros
     * @param int $anio
     * @param int $mes            0 = todo el año (acumula meses)
     */
    public static function getTop10(int $centroSaludId, int $anio, int $mes = 0): array
    {
        $query = CausaConsultaExterna::query()->where('anio', $anio);

        if ($centroSaludId > 0) {
            $query->where('centro_salud_id', $centroSaludId);
        }
        if ($mes > 0) {
            $query->where('mes', $mes);
        }

        $registros = $query->get();

        // Agrupar por diagnóstico (puede venir de varias posiciones/meses)
        // Usar diagnostico como clave de agrupación (case-insensitive trim)
        $porDiag = [];
        foreach ($registros as $reg) {
            $key = mb_strtolower(trim($reg->diagnostico));
            if ($key === '' || $key === 'causa ' . $reg->posicion) {
                continue; // ignorar placeholders vacíos
            }
            if (! isset($porDiag[$key])) {
                $porDiag[$key] = [
                    'diagnostico' => trim($reg->diagnostico),
                    'grupos'      => array_fill_keys(
                        array_keys(self::GRUPOS_LABEL),
                        ['m' => 0, 'f' => 0]
                    ),
                    'total_m' => 0,
                    'total_f' => 0,
                    'total'   => 0,
                ];
            }
            $g = $reg->grupo_etareo;
            $porDiag[$key]['grupos'][$g]['m'] += $reg->masculino;
            $porDiag[$key]['grupos'][$g]['f'] += $reg->femenino;
            $porDiag[$key]['total_m'] += $reg->masculino;
            $porDiag[$key]['total_f'] += $reg->femenino;
        }

        foreach ($porDiag as &$c) {
            $c['total'] = $c['total_m'] + $c['total_f'];
        }
        unset($c);

        // Ordenar por total desc → top 10
        usort($porDiag, fn ($a, $b) => $b['total'] <=> $a['total']);
        $top10 = array_slice(array_values($porDiag), 0, 10);

        $grandTotal  = array_sum(array_column($top10, 'total'));
        $grandTotalM = array_sum(array_column($top10, 'total_m'));
        $grandTotalF = array_sum(array_column($top10, 'total_f'));

        foreach ($top10 as $i => &$causa) {
            $causa['rank']       = $i + 1;
            $causa['porcentaje'] = $grandTotal > 0
                ? round($causa['total'] / $grandTotal * 100, 1)
                : 0;
        }
        unset($causa);

        $centro = $centroSaludId > 0
            ? CentroSalud::find($centroSaludId)?->nombre ?? 'Todos los centros'
            : 'Todos los centros';

        $periodoLabel = $mes > 0
            ? (CausaConsultaExterna::$meses[$mes] ?? "Mes $mes") . " $anio"
            : "Gestión $anio";

        return [
            'causas'        => $top10,
            'grand_total'   => $grandTotal,
            'grand_total_m' => $grandTotalM,
            'grand_total_f' => $grandTotalF,
            'anio'          => $anio,
            'mes'           => $mes,
            'centro'        => $centro,
            'periodo_label' => $periodoLabel,
        ];
    }
}
