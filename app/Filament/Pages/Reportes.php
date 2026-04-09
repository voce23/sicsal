<?php

namespace App\Filament\Pages;

use App\Exports\CausasConsultaExternaExport;
use App\Exports\ComunidadesPoblacionExport;
use App\Exports\InformeCAIExport;
use App\Exports\PadronComunidadesExport;
use App\Exports\PresentacionCAI;
use App\Helpers\CaiHelper;
use App\Helpers\CausasConsultaHelper;
use App\Models\CentroSalud;
use App\Models\Comunidad;
use App\Models\MetaIne;
use App\Models\Persona;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;

class Reportes extends Page
{
    protected string $view = 'filament.pages.reportes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|\UnitEnum|null $navigationGroup = 'Informes';

    protected static ?string $navigationLabel = 'Descargas';

    protected static ?string $title = 'Reportes y Descargas';

    protected static ?int $navigationSort = 1;

    public int $centroSaludId = 0;

    public int $anio;

    public string $periodo = 'cai1';

    // ── Filtros para Causas de Consulta Externa ──
    public int $mesCausas = 0; // 0 = todo el año

    public function mount(): void
    {
        $this->anio = (int) date('Y');

        $centros = $this->getCentros();
        if (count($centros) > 0) {
            $this->centroSaludId = (int) array_key_first($centros);
        }
    }

    public function getCentros(): array
    {
        $user = auth()->user();

        if ($user?->hasRole('super_admin')) {
            return CentroSalud::orderBy('nombre')->pluck('nombre', 'id')->toArray();
        }

        if ($user?->centro_salud_id) {
            $centro = CentroSalud::find($user->centro_salud_id);

            return $centro ? [$centro->id => $centro->nombre] : [];
        }

        return CentroSalud::orderBy('nombre')->pluck('nombre', 'id')->toArray();
    }

    // ── Acciones Filament: Informe CAI ──

    public function descargarCaiExcelAction(): Action
    {
        return Action::make('descargarCaiExcel')
            ->label('Excel')
            ->icon('heroicon-o-table-cells')
            ->color('success')
            ->disabled(fn () => ! $this->centroSaludId)
            ->action(function () {
                $datos = CaiHelper::getDatosInforme($this->centroSaludId, $this->periodo, $this->anio);
                $nombre = 'InformeCAI_'.str_replace(' ', '_', $datos['encabezado']['centro_nombre'] ?? 'Centro')
                    .'_'.$this->periodo.'_'.$this->anio.'.xlsx';

                return Excel::download(new InformeCAIExport($datos), $nombre);
            });
    }

    public function descargarCaiPdfAction(): Action
    {
        return Action::make('descargarCaiPdf')
            ->label('PDF')
            ->icon('heroicon-o-document-text')
            ->color('danger')
            ->disabled(fn () => ! $this->centroSaludId)
            ->action(function () {
                $datos = CaiHelper::getDatosInforme($this->centroSaludId, $this->periodo, $this->anio);
                $nombre = 'InformeCAI_'.str_replace(' ', '_', $datos['encabezado']['centro_nombre'] ?? 'Centro')
                    .'_'.$this->periodo.'_'.$this->anio.'.pdf';
                $pdf = Pdf::loadView('pdf.informe-cai', ['datos' => $datos])
                    ->setPaper('a4', 'portrait')
                    ->setOption('margin-top', 15)->setOption('margin-bottom', 15)
                    ->setOption('margin-left', 15)->setOption('margin-right', 15);

                return response()->streamDownload(fn () => print ($pdf->output()), $nombre);
            });
    }

    public function descargarCaiPptxAction(): Action
    {
        return Action::make('descargarCaiPptx')
            ->label('PowerPoint')
            ->icon('heroicon-o-presentation-chart-bar')
            ->color('info')
            ->disabled(fn () => ! $this->centroSaludId)
            ->action(function () {
                $centro = CentroSalud::find($this->centroSaludId);
                $pptx = new PresentacionCAI($centro->municipio_id, $this->periodo, $this->anio);
                $tmpFile = $pptx->generate();
                $nombre = 'InformeCAI_Municipal_'.$this->periodo.'_'.$this->anio.'.pptx';

                return response()->streamDownload(function () use ($tmpFile) {
                    readfile($tmpFile);
                    @unlink($tmpFile);
                }, $nombre, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                ]);
            });
    }

    // ── Acciones Filament: 10 Principales Causas de Consulta Externa ──

    public function descargarCausasExcelAction(): Action
    {
        return Action::make('descargarCausasExcel')
            ->label('Excel')
            ->icon('heroicon-o-table-cells')
            ->color('success')
            ->disabled(fn () => ! $this->centroSaludId)
            ->action(function () {
                $datos = CausasConsultaHelper::getTop10($this->centroSaludId, $this->anio, $this->mesCausas);
                $centro = str_replace(' ', '_', $datos['centro']);
                $nombre = "CausasConsulta_{$centro}_{$this->anio}.xlsx";

                return Excel::download(new CausasConsultaExternaExport($datos), $nombre);
            });
    }

    public function descargarCausasPdfAction(): Action
    {
        return Action::make('descargarCausasPdf')
            ->label('PDF')
            ->icon('heroicon-o-document-text')
            ->color('danger')
            ->disabled(fn () => ! $this->centroSaludId)
            ->action(function () {
                $datos = CausasConsultaHelper::getTop10($this->centroSaludId, $this->anio, $this->mesCausas);
                $centro = str_replace(' ', '_', $datos['centro']);
                $nombre = "CausasConsulta_{$centro}_{$this->anio}.pdf";

                $pdf = Pdf::loadView('pdf.causas-consulta-externa', ['datos' => $datos])
                    ->setPaper('a3', 'landscape')
                    ->setOption('margin-top', 8)
                    ->setOption('margin-bottom', 8)
                    ->setOption('margin-left', 8)
                    ->setOption('margin-right', 8);

                return response()->streamDownload(fn () => print ($pdf->output()), $nombre);
            });
    }

    // ── Acciones Filament: Padrón por Comunidad ──

    public function descargarPadronExcelAction(): Action
    {
        return Action::make('descargarPadronExcel')
            ->label('Excel por comunidad')
            ->icon('heroicon-o-table-cells')
            ->color('warning')
            ->disabled(fn () => ! $this->centroSaludId)
            ->action(function () {
                $centro = CentroSalud::find($this->centroSaludId);
                $nombre = 'PadronComunal_'.str_replace(' ', '_', $centro->nombre ?? 'Centro').'.xlsx';

                return Excel::download(new PadronComunidadesExport($this->centroSaludId), $nombre);
            });
    }

    // ── Acciones Filament: Comunidades Población ──

    public function descargarComunidadesExcelAction(): Action
    {
        return Action::make('descargarComunidadesExcel')
            ->label('Excel')
            ->icon('heroicon-o-table-cells')
            ->color('success')
            ->disabled(fn () => ! $this->centroSaludId)
            ->action(function () {
                $datos = $this->getDatosComunidades();
                $nombre = 'ComunidadesPoblacion_'.str_replace(' ', '_', $datos['centro']->nombre ?? 'Centro').'.xlsx';

                return Excel::download(new ComunidadesPoblacionExport($datos), $nombre);
            });
    }

    public function descargarComunidadesPdfAction(): Action
    {
        return Action::make('descargarComunidadesPdf')
            ->label('PDF')
            ->icon('heroicon-o-document-text')
            ->color('danger')
            ->disabled(fn () => ! $this->centroSaludId)
            ->action(function () {
                $datos = $this->getDatosComunidades();
                $nombre = 'ComunidadesPoblacion_'.str_replace(' ', '_', $datos['centro']->nombre ?? 'Centro').'.pdf';
                $pdf = Pdf::loadView('pdf.comunidades-poblacion', ['datos' => $datos])
                    ->setPaper('legal', 'landscape')
                    ->setOption('margin-top', 10)->setOption('margin-bottom', 10)
                    ->setOption('margin-left', 10)->setOption('margin-right', 10);

                return response()->streamDownload(fn () => print ($pdf->output()), $nombre);
            });
    }

    // ── Datos auxiliares ──

    // Mismos grupos y etiquetas que ComunidadesPoblacion (los Sheets dependen de estas claves)
    private const GRUPOS = [
        'menor_1' => '<1',    '1_4' => '1-4',   '5_9' => '5-9',
        '10_14' => '10-14', '15_19' => '15-19', '20_39' => '20-39',
        '40_49' => '40-49', '50_59' => '50-59', 'mayor_60' => '60+',
    ];

    private const RATIO_MASCULINIDAD = [
        'menor_1' => 1.05, '1_4' => 1.05, '5_9' => 1.04, '10_14' => 1.04,
        '15_19' => 1.02, '20_39' => 0.96, '40_49' => 0.94, '50_59' => 0.92,
        'mayor_60' => 0.88,
    ];

    protected function getDatosComunidades(): array
    {
        $centroId = $this->centroSaludId;
        $centro = CentroSalud::with('municipio')->find($centroId);

        $grupos = array_keys(self::GRUPOS);
        $emptyGrupos = array_fill_keys($grupos, 0);
        $emptyTotales = ['total' => 0, 'hombres' => 0, 'mujeres' => 0] + $emptyGrupos + ['migrantes' => 0];

        if (! $centro) {
            return [
                'filas' => [], 'totales' => $emptyTotales,
                'detalle' => [], 'detalleTotales' => [],
                'consolidado' => [],
                'metaIne' => 0, 'diferencia' => 0,
                'centro' => null, 'grupos' => self::GRUPOS,
            ];
        }

        $anio = (int) date('Y');
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

            // Detalle por sexo
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
                'comunidad' => $com->nombre,
                'km' => $com->distancia_km ?? '-',
                'total' => $total,
                'hombres' => $hombres,
                'mujeres' => $mujeres,
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

        // Consolidado INE vs Real
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
                $ineM = $ineF = 0;
            }

            $realM = $detalleTotales[$g]['M'];
            $realF = $detalleTotales[$g]['F'];
            $ineTotal = $ineM + $ineF;
            $realTotal = $realM + $realF;
            $metaIneTotal += $ineTotal;

            $consolidado[] = [
                'grupo' => $g,
                'label' => self::GRUPOS[$g],
                'ine_m' => $ineM,   'ine_f' => $ineF,   'ine_total' => $ineTotal,
                'real_m' => $realM,  'real_f' => $realF,  'real_total' => $realTotal,
                'diferencia' => $realTotal - $ineTotal,
                'cobertura' => $ineTotal > 0 ? round($realTotal / $ineTotal * 100, 1) : 0,
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
            default => 'TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) >= 60',
        };
    }
}
