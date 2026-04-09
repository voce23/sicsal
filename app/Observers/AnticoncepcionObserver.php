<?php

namespace App\Observers;

use App\Models\Anticoncepcion;
use App\Models\PrestAnticoncepcion;
use App\Observers\Concerns\SincronizaPrestacion;
use Carbon\Carbon;

class AnticoncepcionObserver
{
    use SincronizaPrestacion;

    public function created(Anticoncepcion $ac): void
    {
        $this->sincronizar($ac, +1);
    }

    public function deleted(Anticoncepcion $ac): void
    {
        $this->sincronizar($ac, -1);
    }

    private function sincronizar(Anticoncepcion $ac, int $delta): void
    {
        $persona = $ac->persona;
        if (! $persona) {
            return;
        }

        $fechaRef = $ac->fecha instanceof Carbon ? $ac->fecha : Carbon::parse($ac->fecha);
        $grupoEtareo = $this->grupoEtareoMaterno(
            Carbon::parse($persona->fecha_nacimiento),
            $fechaRef
        );

        $row = PrestAnticoncepcion::firstOrCreate(
            [
                'centro_salud_id' => $persona->centro_salud_id,
                'mes' => $ac->mes,
                'anio' => $ac->anio,
                'metodo' => $ac->metodo,
                'tipo_usuaria' => $ac->tipo_usuaria,
                'grupo_etareo' => $grupoEtareo,
            ],
            ['cantidad' => 0]
        );

        if ($delta > 0) {
            $row->increment('cantidad');
        } elseif ($row->cantidad > 0) {
            $row->decrement('cantidad');
        }
    }
}
