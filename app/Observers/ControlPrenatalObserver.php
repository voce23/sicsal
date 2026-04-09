<?php

namespace App\Observers;

use App\Models\ControlPrenatal;
use App\Models\PrestPrenatal;
use App\Observers\Concerns\SincronizaPrestacion;
use Carbon\Carbon;

class ControlPrenatalObserver
{
    use SincronizaPrestacion;

    public function created(ControlPrenatal $control): void
    {
        $this->sincronizar($control, +1);
    }

    public function deleted(ControlPrenatal $control): void
    {
        $this->sincronizar($control, -1);
    }

    private function sincronizar(ControlPrenatal $control, int $delta): void
    {
        $embarazo = $control->embarazo()->with('persona')->first();
        if (! $embarazo?->persona) {
            return;
        }

        $persona = $embarazo->persona;
        $fechaRef = $control->fecha instanceof Carbon
            ? $control->fecha
            : Carbon::parse($control->fecha);

        $mes = (int) $fechaRef->format('n');
        $anio = (int) $fechaRef->format('Y');

        $tipoControl = $this->tipoPrenatal(
            (int) $control->numero_control,
            (int) ($control->semanas_gestacion ?? 0)
        );

        // grupo_etareo viene almacenado en el propio registro ControlPrenatal
        $grupoEtareo = $control->grupo_etareo
            ?: $this->grupoEtareoMaterno(Carbon::parse($persona->fecha_nacimiento), $fechaRef);

        $campo = $control->dentro_fuera === 'dentro' ? 'dentro' : 'fuera';

        $row = PrestPrenatal::firstOrCreate(
            [
                'centro_salud_id' => $persona->centro_salud_id,
                'mes' => $mes,
                'anio' => $anio,
                'tipo_control' => $tipoControl,
                'grupo_etareo' => $grupoEtareo,
            ],
            ['dentro' => 0, 'fuera' => 0]
        );

        if ($delta > 0) {
            $row->increment($campo);
        } elseif ($row->$campo > 0) {
            $row->decrement($campo);
        }
    }
}
