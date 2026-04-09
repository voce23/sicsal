<?php

namespace App\Observers;

use App\Models\Parto;
use App\Models\PrestParto;
use App\Models\PrestRecienNacido;
use App\Models\PrestVacuna;
use App\Observers\Concerns\SincronizaPrestacion;
use Carbon\Carbon;

class PartoObserver
{
    use SincronizaPrestacion;

    public function created(Parto $parto): void
    {
        $this->sincronizar($parto, +1);
    }

    public function deleted(Parto $parto): void
    {
        $this->sincronizar($parto, -1);
    }

    private function sincronizar(Parto $parto, int $delta): void
    {
        $embarazo = $parto->embarazo()->with('persona')->first();
        if (!$embarazo?->persona) return;

        $persona      = $embarazo->persona;
        $centroId     = $persona->centro_salud_id;
        $fechaParto   = $parto->fecha_parto instanceof Carbon
            ? $parto->fecha_parto
            : Carbon::parse($parto->fecha_parto);
        $mes  = (int) $fechaParto->format('n');
        $anio = (int) $fechaParto->format('Y');

        // Grupo etáreo de la madre al momento del parto
        $grupoEtareo = $parto->grupo_etareo
            ?: $this->grupoEtareoMaterno(Carbon::parse($persona->fecha_nacimiento), $fechaParto);

        // ── PrestParto ──
        $rowParto = PrestParto::firstOrCreate(
            [
                'centro_salud_id' => $centroId,
                'mes'             => $mes,
                'anio'            => $anio,
                'tipo'            => $parto->tipo,
                'lugar'           => $parto->lugar,
                'atendido_por'    => $parto->atendido_por,
                'grupo_etareo'    => $grupoEtareo,
            ],
            ['cantidad' => 0]
        );

        if ($delta > 0) {
            $rowParto->increment('cantidad');
        } elseif ($rowParto->cantidad > 0) {
            $rowParto->decrement('cantidad');
        }

        // ── Nacido vivo: PrestRecienNacido + BCG automático ──
        if ($parto->resultado === 'nacido_vivo') {
            $indicadorRN = ($parto->lugar === 'servicio')
                ? 'nacidos_vivos_servicio'
                : 'nacidos_vivos_domicilio';

            $rowRN = PrestRecienNacido::firstOrCreate(
                ['centro_salud_id' => $centroId, 'mes' => $mes, 'anio' => $anio, 'indicador' => $indicadorRN],
                ['cantidad' => 0]
            );

            if ($delta > 0) {
                $rowRN->increment('cantidad');
            } elseif ($rowRN->cantidad > 0) {
                $rowRN->decrement('cantidad');
            }

            // BCG automático en PrestVacuna (dentro_m por convención al no conocer sexo del RN)
            $rowBcg = PrestVacuna::firstOrCreate(
                [
                    'centro_salud_id' => $centroId,
                    'mes'             => $mes,
                    'anio'            => $anio,
                    'tipo_vacuna'     => 'BCG',
                    'grupo_etareo'    => 'menor_1',
                ],
                ['dentro_m' => 0, 'dentro_f' => 0, 'fuera_m' => 0, 'fuera_f' => 0]
            );

            if ($delta > 0) {
                $rowBcg->increment('dentro_m');
            } elseif ($rowBcg->dentro_m > 0) {
                $rowBcg->decrement('dentro_m');
            }
        }
    }
}
