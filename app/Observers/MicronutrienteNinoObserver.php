<?php

namespace App\Observers;

use App\Models\MicronutrienteNino;
use App\Models\PrestMicronutriente;

class MicronutrienteNinoObserver
{
    public function created(MicronutrienteNino $micro): void
    {
        $this->sincronizar($micro, +1);
    }

    public function deleted(MicronutrienteNino $micro): void
    {
        $this->sincronizar($micro, -1);
    }

    private function sincronizar(MicronutrienteNino $micro, int $delta): void
    {
        $persona = $micro->persona;
        if (!$persona) return;

        $row = PrestMicronutriente::firstOrCreate(
            [
                'centro_salud_id' => $persona->centro_salud_id,
                'mes'             => $micro->mes,
                'anio'            => $micro->anio,
                'tipo'            => $micro->tipo,
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
