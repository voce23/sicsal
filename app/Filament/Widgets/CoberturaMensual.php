<?php

namespace App\Filament\Widgets;

use App\Models\MetaIne;
use App\Models\PrestCrecimiento;
use App\Models\PrestPrenatal;
use App\Models\PrestVacuna;
use Filament\Widgets\Widget;

class CoberturaMensual extends Widget
{
    protected string $view = 'filament.widgets.cobertura-mensual';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function getDatosProperty(): array
    {
        $centroId = auth()->user()->centro_salud_id;
        if (! $centroId) {
            return [];
        }

        $mes = (int) date('n');
        $anio = (int) date('Y');

        $metasIne = MetaIne::where('centro_salud_id', $centroId)->where('anio', $anio)->get();

        $metaBcg = $metasIne->where('grupo_etareo', 'nacimientos_esperados')->sum('cantidad') / 12;
        $metaPrenatal = $metasIne->where('grupo_etareo', 'embarazos_esperados')->sum('cantidad') / 12;
        $metaCrecimiento = $metasIne->whereIn('grupo_etareo', ['menor_1', '1_anio', '2_anios', '3_anios', '4_anios'])->sum('cantidad') / 12;

        $vacunasBcg = PrestVacuna::where('centro_salud_id', $centroId)
            ->where('mes', $mes)->where('anio', $anio)->where('tipo_vacuna', 'BCG')
            ->selectRaw('SUM(dentro_m + dentro_f + fuera_m + fuera_f) as total')->value('total') ?? 0;

        $prenatales = PrestPrenatal::where('centro_salud_id', $centroId)
            ->where('mes', $mes)->where('anio', $anio)
            ->selectRaw('SUM(dentro + fuera) as total')->value('total') ?? 0;

        $crecimiento = PrestCrecimiento::where('centro_salud_id', $centroId)
            ->where('mes', $mes)->where('anio', $anio)
            ->selectRaw('SUM(nuevos_m + nuevos_f + repetidos_m + repetidos_f) as total')->value('total') ?? 0;

        return [
            ['nombre' => 'BCG', 'meta' => $metaBcg, 'atendidos' => $vacunasBcg],
            ['nombre' => 'Prenatal', 'meta' => $metaPrenatal, 'atendidos' => $prenatales],
            ['nombre' => 'Crecimiento', 'meta' => $metaCrecimiento, 'atendidos' => $crecimiento],
        ];
    }
}
