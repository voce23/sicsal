<?php

namespace App\Observers;

use App\Models\CrecimientoInfantil;
use App\Models\PrestCrecimiento;
use App\Observers\Concerns\SincronizaPrestacion;
use Carbon\Carbon;

class CrecimientoInfantilObserver
{
    use SincronizaPrestacion;

    public function created(CrecimientoInfantil $registro): void
    {
        $this->sincronizar($registro, +1);
    }

    public function deleted(CrecimientoInfantil $registro): void
    {
        $this->sincronizar($registro, -1);
    }

    private function sincronizar(CrecimientoInfantil $registro, int $delta): void
    {
        $persona = $registro->persona;
        if (!$persona) return;

        $fechaRef  = $registro->fecha instanceof Carbon
            ? $registro->fecha
            : Carbon::parse($registro->fecha);

        $edadMeses  = (int) Carbon::parse($persona->fecha_nacimiento)->diffInMonths($fechaRef);
        $grupoEtareo = $this->grupoCrecimiento($edadMeses, $registro->dentro_fuera);

        // campo: nuevos_m/f o repetidos_m/f
        $prefijo = $registro->tipo_control === 'nuevo' ? 'nuevos' : 'repetidos';
        $campo   = $prefijo . '_' . strtolower($persona->sexo);

        $row = PrestCrecimiento::firstOrCreate(
            [
                'centro_salud_id' => $persona->centro_salud_id,
                'mes'             => $registro->mes,
                'anio'            => $registro->anio,
                'grupo_etareo'    => $grupoEtareo,
            ],
            ['nuevos_m' => 0, 'nuevos_f' => 0, 'repetidos_m' => 0, 'repetidos_f' => 0]
        );

        if ($delta > 0) {
            $row->increment($campo);
        } elseif ($row->$campo > 0) {
            $row->decrement($campo);
        }
    }
}
