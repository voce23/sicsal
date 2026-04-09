<?php

namespace App\Filament\Widgets;

use App\Models\MesCerrado;
use App\Models\Persona;
use Filament\Widgets\Widget;

class AlertasWidget extends Widget
{
    protected string $view = 'filament.widgets.alertas-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getDatosProperty(): array
    {
        $centroId = auth()->user()->centro_salud_id;
        if (! $centroId) {
            return ['mesesSinCerrar' => [], 'totalPersonas' => 0, 'anio' => date('Y')];
        }

        $anio = (int) date('Y');
        $mesActual = (int) date('n');

        $mesesCerrados = MesCerrado::where('centro_salud_id', $centroId)
            ->where('anio', $anio)->pluck('mes')->toArray();

        $nombresMeses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
        $mesesSinCerrar = [];
        for ($m = 1; $m < $mesActual; $m++) {
            if (! in_array($m, $mesesCerrados)) {
                $mesesSinCerrar[] = $nombresMeses[$m];
            }
        }

        $totalPersonas = Persona::where('centro_salud_id', $centroId)->where('activo', true)->count();

        return [
            'mesesSinCerrar' => $mesesSinCerrar,
            'totalPersonas' => $totalPersonas,
            'anio' => $anio,
        ];
    }
}
