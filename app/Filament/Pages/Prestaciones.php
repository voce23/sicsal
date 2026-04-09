<?php

namespace App\Filament\Pages;

use App\Models\CausaConsultaExterna;
use App\Models\MesCerrado;
use App\Models\PrestAnticoncepcion;
use App\Models\PrestCancerPrevencion;
use App\Models\PrestConsultaExterna;
use App\Models\PrestCrecimiento;
use App\Models\PrestEnfermeria;
use App\Models\PrestIle;
use App\Models\PrestInternacion;
use App\Models\PrestMicronutriente;
use App\Models\PrestOdontologia;
use App\Models\PrestParto;
use App\Models\PrestPrenatal;
use App\Models\PrestPuerperio;
use App\Models\PrestRecienNacido;
use App\Models\PrestReferencia;
use App\Models\PrestVacuna;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Prestaciones extends Page
{
    protected string $view = 'filament.pages.prestaciones';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Prestaciones Mensuales';

    protected static ?string $navigationLabel = 'Prestaciones';

    protected static ?string $title = 'Prestaciones Mensuales';

    protected static ?int $navigationSort = 1;

    public int $anio;

    public function mount(): void
    {
        $this->anio = (int) date('Y');
    }

    public function getViewData(): array
    {
        $user = auth()->user();
        $centroId = $user->centro_salud_id;

        $meses = [];
        $nombresMeses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $mesesCerrados = MesCerrado::where('centro_salud_id', $centroId)
            ->where('anio', $this->anio)->pluck('mes')->toArray();

        $mesesConDato = collect();
        foreach ([
            PrestConsultaExterna::class, PrestVacuna::class, PrestPrenatal::class,
            PrestParto::class, PrestPuerperio::class, PrestRecienNacido::class,
            PrestMicronutriente::class, PrestReferencia::class, PrestOdontologia::class,
            PrestEnfermeria::class, PrestInternacion::class, PrestCancerPrevencion::class,
            PrestAnticoncepcion::class, PrestCrecimiento::class, PrestIle::class,
            CausaConsultaExterna::class,
        ] as $model) {
            $mesesConDato = $mesesConDato->merge(
                $model::where('centro_salud_id', $centroId)->where('anio', $this->anio)
                    ->distinct()->pluck('mes')
            );
        }
        $mesesConDato = $mesesConDato->unique()->toArray();

        for ($m = 1; $m <= 12; $m++) {
            $cerrado = in_array($m, $mesesCerrados);
            $tieneAlgunDato = in_array($m, $mesesConDato);

            if ($cerrado) {
                $estado = 'cerrado';
            } elseif ($tieneAlgunDato) {
                $estado = 'parcial';
            } else {
                $estado = 'vacio';
            }

            $params = ['mes' => $m, 'anio' => $this->anio];

            $meses[$m] = [
                'numero' => $m,
                'nombre' => $nombresMeses[$m],
                'estado' => $estado,
                'cerrado' => $cerrado,
                'urls' => [
                    ['label' => 'PAI', 'url' => FormularioPai::getUrl($params), 'icon' => 'heroicon-o-shield-check'],
                    ['label' => 'Materna', 'url' => FormularioMaterna::getUrl($params), 'icon' => 'heroicon-o-heart'],
                    ['label' => 'Servicios', 'url' => FormularioServicios::getUrl($params), 'icon' => 'heroicon-o-building-office-2'],
                    ['label' => 'Causas Consulta', 'url' => FormularioCausasConsulta::getUrl($params), 'icon' => 'heroicon-o-document-magnifying-glass'],
                ],
            ];
        }

        return ['meses' => $meses, 'anio' => $this->anio];
    }
}
