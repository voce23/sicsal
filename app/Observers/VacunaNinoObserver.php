<?php

namespace App\Observers;

use App\Models\PrestVacuna;
use App\Models\VacunaNino;
use App\Observers\Concerns\SincronizaPrestacion;
use Carbon\Carbon;

class VacunaNinoObserver
{
    use SincronizaPrestacion;

    public function created(VacunaNino $vacuna): void
    {
        $this->sincronizar($vacuna, +1);
    }

    public function deleted(VacunaNino $vacuna): void
    {
        $this->sincronizar($vacuna, -1);
    }

    private function sincronizar(VacunaNino $vacuna, int $delta): void
    {
        $persona = $vacuna->persona;
        if (! $persona) {
            return;
        }

        $fechaRef = $vacuna->fecha_aplicacion instanceof Carbon
            ? $vacuna->fecha_aplicacion
            : Carbon::parse($vacuna->fecha_aplicacion);

        $edadMeses = Carbon::parse($persona->fecha_nacimiento)->diffInMonths($fechaRef);
        $grupoEtareo = $this->grupoEtareoVacuna((int) $edadMeses);
        $campo = $this->campoDentroFueraSexo($vacuna->dentro_fuera, $persona->sexo);

        $row = PrestVacuna::firstOrCreate(
            [
                'centro_salud_id' => $persona->centro_salud_id,
                'mes' => $vacuna->mes,
                'anio' => $vacuna->anio,
                'tipo_vacuna' => $vacuna->tipo_vacuna,
                'grupo_etareo' => $grupoEtareo,
            ],
            ['dentro_m' => 0, 'dentro_f' => 0, 'fuera_m' => 0, 'fuera_f' => 0]
        );

        if ($delta > 0) {
            $row->increment($campo);
        } elseif ($row->$campo > 0) {
            $row->decrement($campo);
        }
    }
}
