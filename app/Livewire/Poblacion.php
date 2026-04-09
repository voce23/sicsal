<?php

namespace App\Livewire;

use App\Concerns\HasCentroSelector;
use App\Models\MetaIne;
use App\Models\Persona;
use App\Models\PrestCrecimiento;
use App\Models\PrestMicronutriente;
use App\Models\PrestParto;
use App\Models\PrestPrenatal;
use App\Models\PrestVacuna;
use Livewire\Attributes\Url;
use Livewire\Component;

class Poblacion extends Component
{
    use HasCentroSelector;

    #[Url]
    public string $tab = 'piramide';

    #[Url]
    public string $periodo = 'gestion';

    public int $anio;

    public array $piramideData = [];

    protected array $gruposConfig = [
        ['label' => '<1 año',  'min' => 0,  'max' => 0,  'ine' => ['menor_1']],
        ['label' => '1-4',     'min' => 1,  'max' => 4,  'ine' => ['1_anio', '2_anios', '3_anios', '4_anios']],
        ['label' => '5-9',     'min' => 5,  'max' => 9,  'ine' => ['5_9']],
        ['label' => '10-14',   'min' => 10, 'max' => 14, 'ine' => ['10_14']],
        ['label' => '15-19',   'min' => 15, 'max' => 19, 'ine' => ['15_19']],
        ['label' => '20-39',   'min' => 20, 'max' => 39, 'ine' => ['20_39']],
        ['label' => '40-49',   'min' => 40, 'max' => 49, 'ine' => ['40_49']],
        ['label' => '50-59',   'min' => 50, 'max' => 59, 'ine' => ['50_59']],
        ['label' => '60+',     'min' => 60, 'max' => 200, 'ine' => ['mayor_60']],
    ];

    public function mount(): void
    {
        $this->anio = (int) date('Y');
        $this->mountHasCentroSelector();
        $this->piramideData = $this->buildPiramide($this->getCentroId());
    }

    public function updatedCentroSaludId(): void
    {
        $this->piramideData = $this->buildPiramide($this->getCentroId());
    }

    // ── Pirámide ──

    private function buildPiramide(int $centroId): array
    {
        if ($centroId === 0) {
            return ['labels' => [], 'ineM' => [], 'ineF' => [], 'realM' => [], 'realF' => [], 'tabla' => []];
        }

        $labels = [];
        $ineM = [];
        $ineF = [];
        $realM = [];
        $realF = [];
        $tablaFilas = [];

        $metasIne = MetaIne::where('centro_salud_id', $centroId)
            ->where('anio', $this->anio)->get();

        foreach ($this->gruposConfig as $grupo) {
            $labels[] = $grupo['label'];

            $grupoIne = $metasIne->whereIn('grupo_etareo', $grupo['ine']);
            $imExplicito = $grupoIne->where('sexo', 'M')->sum('cantidad');
            $ifExplicito = $grupoIne->where('sexo', 'F')->sum('cantidad');
            $ambos = $grupoIne->where('sexo', 'ambos')->sum('cantidad');

            $im = $imExplicito > 0 ? $imExplicito : (int) ceil($ambos / 2);
            $if = $ifExplicito > 0 ? $ifExplicito : (int) floor($ambos / 2);
            $ineM[] = $im;
            $ineF[] = $if;

            $queryBase = Persona::where('centro_salud_id', $centroId)
                ->where('activo', true)->where('estado', '!=', 'migrado');

            if ($grupo['max'] === 0) {
                $rm = (clone $queryBase)->where('sexo', 'M')
                    ->where('fecha_nacimiento', '>', now()->subYear())->count();
                $rf = (clone $queryBase)->where('sexo', 'F')
                    ->where('fecha_nacimiento', '>', now()->subYear())->count();
            } else {
                $rm = (clone $queryBase)->where('sexo', 'M')
                    ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN ? AND ?', [$grupo['min'], $grupo['max']])->count();
                $rf = (clone $queryBase)->where('sexo', 'F')
                    ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN ? AND ?', [$grupo['min'], $grupo['max']])->count();
            }

            $realM[] = $rm;
            $realF[] = $rf;

            $tablaFilas[] = [
                'grupo' => $grupo['label'],
                'ine_m' => $im, 'ine_f' => $if,
                'real_m' => $rm, 'real_f' => $rf,
                'dif_m' => $rm - $im, 'dif_f' => $rf - $if,
            ];
        }

        return [
            'labels' => $labels,
            'ineM' => $ineM, 'ineF' => $ineF,
            'realM' => $realM, 'realF' => $realF,
            'tabla' => $tablaFilas,
        ];
    }

    // ── INE vs Real ──

    protected function getMeses(): array
    {
        return match ($this->periodo) {
            'cai1' => range(1, 4),
            'cai2' => range(1, 8),
            'gestion' => range(1, 12),
            default => [(int) $this->periodo],
        };
    }

    protected function getFactorMeta(): float
    {
        return match ($this->periodo) {
            'cai1' => 4 / 12,
            'cai2' => 8 / 12,
            'gestion' => 1.0,
            default => 1 / 12,
        };
    }

    public function getNombrePeriodoProperty(): string
    {
        return match ($this->periodo) {
            'cai1' => 'CAI 1 (Ene-Abr)',
            'cai2' => 'CAI 2 (Ene-Ago)',
            'gestion' => 'Gestión '.$this->anio,
            default => $this->nombreMes((int) $this->periodo).' '.$this->anio,
        };
    }

    protected function nombreMes(int $mes): string
    {
        return ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'][$mes] ?? '';
    }

    public function getMigracionProperty(): array
    {
        $centroId = $this->getCentroId();
        if ($centroId === 0) {
            return ['total_padron' => 0, 'total_migrantes' => 0, 'pct_migrantes' => 0, 'mef_activas' => 0, 'mef_migradas' => 0, 'pct_mef' => 0, 'hombres_migrados' => 0];
        }

        $totalPadron = Persona::where('centro_salud_id', $centroId)->where('activo', true)->count();
        $totalMigrantes = Persona::where('centro_salud_id', $centroId)->where('activo', true)->where('estado', 'migrado')->count();

        $mefActivas = Persona::where('centro_salud_id', $centroId)
            ->where('activo', true)->where('sexo', 'F')->where('estado', '!=', 'migrado')
            ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 15 AND 49')->count();

        $mefMigradas = Persona::where('centro_salud_id', $centroId)
            ->where('activo', true)->where('sexo', 'F')->where('estado', 'migrado')
            ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 15 AND 49')->count();

        $hombresMigrados = Persona::where('centro_salud_id', $centroId)
            ->where('activo', true)->where('sexo', 'M')->where('estado', 'migrado')
            ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 15 AND 49')->count();

        return [
            'total_padron' => $totalPadron,
            'total_migrantes' => $totalMigrantes,
            'pct_migrantes' => $totalPadron > 0 ? round($totalMigrantes / $totalPadron * 100, 1) : 0,
            'mef_activas' => $mefActivas,
            'mef_migradas' => $mefMigradas,
            'pct_mef' => ($mefActivas + $mefMigradas) > 0 ? round($mefMigradas / ($mefActivas + $mefMigradas) * 100, 1) : 0,
            'hombres_migrados' => $hombresMigrados,
        ];
    }

    public function getCoberturaProperty(): array
    {
        $centroId = $this->getCentroId();
        if ($centroId === 0) {
            return [];
        }
        $meses = $this->getMeses();
        $factor = $this->getFactorMeta();

        $metasIne = MetaIne::where('centro_salud_id', $centroId)
            ->where('anio', $this->anio)->get();

        $programas = [];

        $metaMenor1 = $metasIne->where('grupo_etareo', 'nacimientos_esperados')->sum('cantidad') * $factor;
        $menores1 = Persona::where('centro_salud_id', $centroId)
            ->where('activo', true)->where('estado', '!=', 'migrado')
            ->where('fecha_nacimiento', '>', now()->subYear())->count();

        $vacunasIndicadores = [
            'BCG' => 'BCG', 'Pentavalente 1ra' => 'Pentavalente_1', 'Pentavalente 2da' => 'Pentavalente_2',
            'Pentavalente 3ra' => 'Pentavalente_3', 'IPV 1ra' => 'IPV_1', 'bOPV 2da' => 'bOPV_2',
            'bOPV 3ra' => 'bOPV_3', 'Antirotavírica 1ra' => 'Antirotavirica_1',
            'Antirotavírica 2da' => 'Antirotavirica_2', 'Antineumocócica 1ra' => 'Antineumococica_1',
            'Antineumocócica 2da' => 'Antineumococica_2', 'Antineumocócica 3ra' => 'Antineumococica_3',
            'Influenza 1ra' => 'Influenza_1', 'Influenza 2da' => 'Influenza_2', 'SRP 1ra' => 'SRP_1',
        ];

        foreach ($vacunasIndicadores as $nombre => $tipo) {
            $atendidos = PrestVacuna::where('centro_salud_id', $centroId)
                ->where('anio', $this->anio)->whereIn('mes', $meses)
                ->where('tipo_vacuna', $tipo)
                ->selectRaw('SUM(dentro_m + dentro_f + fuera_m + fuera_f) as total')->value('total') ?? 0;
            $programas[] = $this->buildFila($nombre, $metaMenor1, $menores1, $atendidos);
        }

        $meta12m = $metasIne->where('grupo_etareo', '1_anio')->sum('cantidad') * $factor;
        $real12m = Persona::where('centro_salud_id', $centroId)->where('activo', true)
            ->where('estado', '!=', 'migrado')
            ->whereBetween('fecha_nacimiento', [now()->subYears(2), now()->subYear()])->count();
        $atAmarilica = PrestVacuna::where('centro_salud_id', $centroId)
            ->where('anio', $this->anio)->whereIn('mes', $meses)
            ->where('tipo_vacuna', 'Antiamarilica')
            ->selectRaw('SUM(dentro_m + dentro_f + fuera_m + fuera_f) as total')->value('total') ?? 0;
        $programas[] = $this->buildFila('Antiamarílica', $meta12m, $real12m, $atAmarilica);

        $metaPrenatal = $metasIne->where('grupo_etareo', 'embarazos_esperados')->sum('cantidad') * $factor;
        $mefActivas = Persona::where('centro_salud_id', $centroId)
            ->where('activo', true)->where('sexo', 'F')->where('estado', '!=', 'migrado')
            ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 15 AND 49')->count();
        $atendidosPrenatal = PrestPrenatal::where('centro_salud_id', $centroId)
            ->where('anio', $this->anio)->whereIn('mes', $meses)
            ->selectRaw('SUM(dentro + fuera) as total')->value('total') ?? 0;
        $programas[] = $this->buildFila('Control prenatal', $metaPrenatal, $mefActivas, $atendidosPrenatal);

        $metaParto = $metasIne->where('grupo_etareo', 'partos_esperados')->sum('cantidad') * $factor;
        $atendidosPartos = PrestParto::where('centro_salud_id', $centroId)
            ->where('anio', $this->anio)->whereIn('mes', $meses)->sum('cantidad');
        $programas[] = $this->buildFila('Partos calificados', $metaParto, $mefActivas, $atendidosPartos);

        $metaCrecim = $metasIne->whereIn('grupo_etareo', ['menor_1', '1_anio', '2_anios', '3_anios', '4_anios'])->sum('cantidad') * $factor;
        $menores5 = Persona::where('centro_salud_id', $centroId)
            ->where('activo', true)->where('estado', '!=', 'migrado')
            ->where('fecha_nacimiento', '>=', now()->subYears(5))->count();
        $atendidosCrecim = PrestCrecimiento::where('centro_salud_id', $centroId)
            ->where('anio', $this->anio)->whereIn('mes', $meses)
            ->selectRaw('SUM(nuevos_m + nuevos_f + repetidos_m + repetidos_f) as total')->value('total') ?? 0;
        $programas[] = $this->buildFila('Crecimiento infantil', $metaCrecim, $menores5, $atendidosCrecim);

        $atendidosMicro = PrestMicronutriente::where('centro_salud_id', $centroId)
            ->where('anio', $this->anio)->whereIn('mes', $meses)->sum('cantidad');
        $programas[] = $this->buildFila('Micronutrientes', $metaCrecim, $menores5, $atendidosMicro);

        return $programas;
    }

    protected function buildFila(string $nombre, float $metaIne, int $pobReal, int $atendidos): array
    {
        return [
            'nombre' => $nombre,
            'meta_ine' => round($metaIne),
            'pob_real' => $pobReal,
            'atendidos' => $atendidos,
            'cob_ine' => $metaIne > 0 ? round($atendidos / $metaIne * 100, 1) : 0,
            'cob_real' => $pobReal > 0 ? round($atendidos / $pobReal * 100, 1) : 0,
        ];
    }

    public function render()
    {
        return view('livewire.poblacion')
            ->layout('layouts.public', ['title' => 'Población — SIMUES']);
    }
}
