<?php

namespace App\Livewire;

use App\Concerns\HasCentroSelector;
use App\Exports\ComunidadesPoblacionExport;
use App\Models\CentroSalud;
use App\Models\Comunidad;
use App\Models\MetaIne;
use App\Models\Persona;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ComunidadesPoblacion extends Component
{
    use HasCentroSelector;

    #[Url]
    public string $tab = 'resumen';

    private const PLANILLA_GRUPOS = [
        'menor_1' => ['label' => '<1 año',  'filter' => 'fecha_nacimiento > DATE_SUB(CURDATE(), INTERVAL 1 YEAR)'],
        '1_2' => ['label' => '1-2',     'filter' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 1 AND 2'],
        '3_5' => ['label' => '3-5',     'filter' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 3 AND 5'],
        '6_9' => ['label' => '6-9',     'filter' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 6 AND 9'],
        '10_14' => ['label' => '10-14',   'filter' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 10 AND 14'],
        '15_19' => ['label' => '15-19',   'filter' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 15 AND 19'],
        '20_29' => ['label' => '20-29',   'filter' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 20 AND 29'],
        '30_39' => ['label' => '30-39',   'filter' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 30 AND 39'],
        '40_49' => ['label' => '40-49',   'filter' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 40 AND 49'],
        '50_59' => ['label' => '50-59',   'filter' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 50 AND 59'],
        'mayor_60' => ['label' => '60+',     'filter' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) >= 60'],
    ];

    private const RATIO_MASCULINIDAD = [
        'menor_1' => 1.05, '1_4' => 1.05, '5_9' => 1.04, '10_14' => 1.04,
        '15_19' => 1.02, '20_39' => 0.96, '40_49' => 0.94, '50_59' => 0.92,
        'mayor_60' => 0.88,
    ];

    private const GRUPOS = [
        'menor_1' => '<1', '1_4' => '1-4', '5_9' => '5-9', '10_14' => '10-14',
        '15_19' => '15-19', '20_39' => '20-39', '40_49' => '40-49', '50_59' => '50-59',
        'mayor_60' => '60+',
    ];

    public function mount(): void
    {
        $this->mountHasCentroSelector();
    }

    public function getDatosProperty(): array
    {
        $centroId = $this->getCentroId();
        $grupos = array_keys(self::GRUPOS);
        $emptyGrupos = array_fill_keys($grupos, 0);
        $emptyTotales = ['total' => 0, 'hombres' => 0, 'mujeres' => 0] + $emptyGrupos + ['migrantes' => 0];

        if ($centroId === 0) {
            $emptyDetalleTotales = array_fill_keys($grupos, ['M' => 0, 'F' => 0]);
            $emptyDetalleTotales['migrantes'] = ['M' => 0, 'F' => 0];
            $emptyDetalleTotales['total'] = ['M' => 0, 'F' => 0];

            return [
                'filas' => [], 'totales' => $emptyTotales,
                'detalle' => [], 'detalleTotales' => $emptyDetalleTotales,
                'consolidado' => [],
                'metaIne' => 0, 'diferencia' => 0,
                'centro' => null, 'grupos' => self::GRUPOS,
            ];
        }

        $anio = (int) date('Y');
        $centro = CentroSalud::with('municipio')->find($centroId);
        $comunidades = Comunidad::where('centro_salud_id', $centroId)->orderBy('nombre')->get();

        $filas = [];
        $totales = $emptyTotales;

        $detalle = [];
        $detalleTotales = [];
        foreach ($grupos as $g) {
            $detalleTotales[$g] = ['M' => 0, 'F' => 0];
        }
        $detalleTotales['migrantes'] = ['M' => 0, 'F' => 0];
        $detalleTotales['total'] = ['M' => 0, 'F' => 0];

        foreach ($comunidades as $com) {
            $base = Persona::where('centro_salud_id', $centroId)
                ->where('comunidad_id', $com->id)
                ->where('activo', true);

            $baseNoMig = (clone $base)->where('estado', '!=', 'migrado');

            $total = (clone $baseNoMig)->count();
            $hombres = (clone $baseNoMig)->where('sexo', 'M')->count();
            $mujeres = (clone $baseNoMig)->where('sexo', 'F')->count();

            $grupoVals = [];
            $grupoVals['menor_1'] = (clone $baseNoMig)->where('fecha_nacimiento', '>', now()->subYear())->count();
            $grupoVals['1_4'] = (clone $baseNoMig)->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 1 AND 4')->count();
            $grupoVals['5_9'] = (clone $baseNoMig)->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 5 AND 9')->count();
            $grupoVals['10_14'] = (clone $baseNoMig)->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 10 AND 14')->count();
            $grupoVals['15_19'] = (clone $baseNoMig)->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 15 AND 19')->count();
            $grupoVals['20_39'] = (clone $baseNoMig)->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 20 AND 39')->count();
            $grupoVals['40_49'] = (clone $baseNoMig)->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 40 AND 49')->count();
            $grupoVals['50_59'] = (clone $baseNoMig)->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 50 AND 59')->count();
            $grupoVals['mayor_60'] = (clone $baseNoMig)->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) >= 60')->count();
            $migrantes = (clone $base)->where('estado', 'migrado')->count();

            $comDetalle = [];
            foreach ($grupos as $g) {
                $filtro = $this->getAgeFilter($g);
                $m = (clone $baseNoMig)->where('sexo', 'M')->whereRaw($filtro)->count();
                $f = (clone $baseNoMig)->where('sexo', 'F')->whereRaw($filtro)->count();
                $comDetalle[$g] = ['M' => $m, 'F' => $f];
                $detalleTotales[$g]['M'] += $m;
                $detalleTotales[$g]['F'] += $f;
            }
            $migM = (clone $base)->where('estado', 'migrado')->where('sexo', 'M')->count();
            $migF = (clone $base)->where('estado', 'migrado')->where('sexo', 'F')->count();
            $comDetalle['migrantes'] = ['M' => $migM, 'F' => $migF];
            $comDetalle['total'] = ['M' => $hombres, 'F' => $mujeres];
            $detalleTotales['migrantes']['M'] += $migM;
            $detalleTotales['migrantes']['F'] += $migF;
            $detalleTotales['total']['M'] += $hombres;
            $detalleTotales['total']['F'] += $mujeres;

            $detalle[] = ['comunidad' => $com->nombre, 'datos' => $comDetalle];

            $fila = [
                'comunidad' => $com->nombre, 'km' => $com->distancia_km ?? '-',
                'total' => $total, 'hombres' => $hombres, 'mujeres' => $mujeres,
                'migrantes' => $migrantes,
            ] + $grupoVals;

            $filas[] = $fila;

            $totales['total'] += $total;
            $totales['hombres'] += $hombres;
            $totales['mujeres'] += $mujeres;
            $totales['migrantes'] += $migrantes;
            foreach ($grupos as $g) {
                $totales[$g] += $grupoVals[$g];
            }
        }

        $metaIneRows = MetaIne::where('centro_salud_id', $centroId)
            ->where('anio', $anio)
            ->whereIn('grupo_etareo', $grupos)
            ->get();

        $consolidado = [];
        $metaIneTotal = 0;

        foreach ($grupos as $g) {
            $meta = $metaIneRows->where('grupo_etareo', $g);
            $metaM = $meta->where('sexo', 'M')->first();
            $metaF = $meta->where('sexo', 'F')->first();
            $metaAmbos = $meta->where('sexo', 'ambos')->first();

            if ($metaM && $metaF) {
                $ineM = $metaM->cantidad;
                $ineF = $metaF->cantidad;
            } elseif ($metaAmbos) {
                $ratio = self::RATIO_MASCULINIDAD[$g] ?? 1.0;
                $totalIne = $metaAmbos->cantidad;
                $ineM = (int) round($totalIne * $ratio / (1 + $ratio));
                $ineF = $totalIne - $ineM;
            } else {
                $ineM = 0;
                $ineF = 0;
            }

            $realM = $detalleTotales[$g]['M'];
            $realF = $detalleTotales[$g]['F'];
            $ineTotal = $ineM + $ineF;
            $realTotal = $realM + $realF;
            $dif = $realTotal - $ineTotal;
            $pct = $ineTotal > 0 ? round($realTotal / $ineTotal * 100, 1) : 0;
            $metaIneTotal += $ineTotal;

            $consolidado[] = [
                'grupo' => $g, 'label' => self::GRUPOS[$g],
                'ine_m' => $ineM, 'ine_f' => $ineF, 'ine_total' => $ineTotal,
                'real_m' => $realM, 'real_f' => $realF, 'real_total' => $realTotal,
                'diferencia' => $dif, 'cobertura' => $pct,
            ];
        }

        return [
            'filas' => $filas,
            'totales' => $totales,
            'detalle' => $detalle,
            'detalleTotales' => $detalleTotales,
            'consolidado' => $consolidado,
            'metaIne' => $metaIneTotal,
            'diferencia' => $totales['total'] - $metaIneTotal,
            'centro' => $centro,
            'grupos' => self::GRUPOS,
        ];
    }

    public function generarExcel()
    {
        if ($this->centroSaludId === 0) {
            return;
        }
        $datos = $this->datos;
        $nombre = 'ComunidadesPoblacion_'.str_replace(' ', '_', $datos['centro']->nombre ?? 'Centro').'.xlsx';

        return Excel::download(new ComunidadesPoblacionExport($datos), $nombre);
    }

    public function generarPdf()
    {
        if ($this->centroSaludId === 0) {
            return;
        }
        $datos = $this->datos;
        $nombre = 'ComunidadesPoblacion_'.str_replace(' ', '_', $datos['centro']->nombre ?? 'Centro').'.pdf';

        $pdf = Pdf::loadView('pdf.comunidades-poblacion', ['datos' => $datos])
            ->setPaper('legal', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        return response()->streamDownload(fn () => print ($pdf->output()), $nombre);
    }

    private function getAgeFilter(string $grupo): string
    {
        return match ($grupo) {
            'menor_1' => 'fecha_nacimiento > DATE_SUB(CURDATE(), INTERVAL 1 YEAR)',
            '1_4' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 1 AND 4',
            '5_9' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 5 AND 9',
            '10_14' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 10 AND 14',
            '15_19' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 15 AND 19',
            '20_39' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 20 AND 39',
            '40_49' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 40 AND 49',
            '50_59' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 50 AND 59',
            'mayor_60' => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) >= 60',
        };
    }

    public function getPlanillaProperty(): array
    {
        $centroId = $this->getCentroId();
        if ($centroId === 0) {
            return ['filas' => [], 'totales' => [], 'centro' => null, 'grupos' => self::PLANILLA_GRUPOS];
        }

        $centro = CentroSalud::find($centroId);
        $comunidades = Comunidad::where('centro_salud_id', $centroId)->orderBy('nombre')->get();

        $totales = [];
        foreach (self::PLANILLA_GRUPOS as $key => $cfg) {
            $totales[$key] = ['M' => 0, 'F' => 0];
        }
        $totales['total'] = ['M' => 0, 'F' => 0];
        $totales['embarazadas'] = 0;

        $filas = [];
        foreach ($comunidades as $com) {
            $base = Persona::where('centro_salud_id', $centroId)
                ->where('comunidad_id', $com->id)
                ->where('activo', true)
                ->where('estado', '!=', 'migrado');

            $fila = ['comunidad' => $com->nombre];
            $totalM = 0;
            $totalF = 0;

            foreach (self::PLANILLA_GRUPOS as $key => $cfg) {
                $m = (clone $base)->where('sexo', 'M')->whereRaw($cfg['filter'])->count();
                $f = (clone $base)->where('sexo', 'F')->whereRaw($cfg['filter'])->count();
                $fila[$key] = ['M' => $m, 'F' => $f];
                $totalM += $m;
                $totalF += $f;
                $totales[$key]['M'] += $m;
                $totales[$key]['F'] += $f;
            }

            $fila['total'] = ['M' => $totalM, 'F' => $totalF];
            $totales['total']['M'] += $totalM;
            $totales['total']['F'] += $totalF;

            $embarazadas = (clone $base)->where('sexo', 'F')
                ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 15 AND 49')
                ->where('estado', 'embarazada')->count();
            $fila['embarazadas'] = $embarazadas;
            $totales['embarazadas'] += $embarazadas;

            $filas[] = $fila;
        }

        return ['filas' => $filas, 'totales' => $totales, 'centro' => $centro, 'grupos' => self::PLANILLA_GRUPOS];
    }

    public function generarPlanillaExcel()
    {
        if ($this->centroSaludId === 0) {
            return;
        }
        $planilla = $this->planilla;
        $nombre = 'PlanillaCenso_'.str_replace(' ', '_', $planilla['centro']->nombre ?? 'Centro').'_'.date('Y').'.xlsx';

        return Excel::download(new ComunidadesPoblacionExport($this->datos, $planilla), $nombre);
    }

    public function generarPlanillaPdf()
    {
        if ($this->centroSaludId === 0) {
            return;
        }
        $planilla = $this->planilla;
        $nombre = 'PlanillaCenso_'.str_replace(' ', '_', $planilla['centro']->nombre ?? 'Centro').'_'.date('Y').'.pdf';

        $pdf = Pdf::loadView('pdf.planilla-censo', ['planilla' => $planilla])
            ->setPaper('legal', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 5)
            ->setOption('margin-right', 5);

        return response()->streamDownload(fn () => print ($pdf->output()), $nombre);
    }

    public function render()
    {
        return view('livewire.comunidades-poblacion')
            ->layout('layouts.public', ['title' => 'Comunidades — SIMUES']);
    }
}
