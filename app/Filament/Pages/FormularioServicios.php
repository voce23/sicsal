<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\FormularioMensualTrait;
use App\Models\CausaConsultaExterna;
use App\Models\ObservacionMensual;
use App\Models\PrestActividadComunidad;
use App\Models\PrestConsultaExterna;
use App\Models\PrestEnfermeria;
use App\Models\PrestInternacion;
use App\Models\PrestOdontologia;
use App\Models\PrestReferencia;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class FormularioServicios extends Page
{
    use FormularioMensualTrait;

    protected string $view = 'filament.pages.formulario-servicios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Prestaciones Mensuales';

    protected static ?string $navigationLabel = 'Servicios Generales';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    const SECCION = 'Servicios Generales';

    public array $consultaExterna = [];

    public array $referencias = [];

    public array $odontologia = [];

    public array $enfermeria = [];

    public array $internaciones = [];

    public array $actividades = [];

    public string $observaciones = '';

    public array $causasConsulta = []; // posicion 1-10 => ['diagnostico'=>'', 'grupos'=>[...]]

    // Form 301 F21-F27
    public static array $tiposReferencia = [
        'ref_recibida_establecimiento' => 'Pac. referidos recibidos por el establecimiento',  // F21
        'ref_enviada' => 'Pac. referidos a otros establecimientos',           // F22
        'pcd_atendida_comunidad' => 'PCD referidos a Unidades de Calificación',         // F23
        'pcd_atendida_establecimiento' => 'PCD referidos a Servicios de Rehabilitación',      // F24
        'contraref_recibida' => 'Pac. contrarreferidos al establecimiento',          // F25
        'ref_recibida_comunidad' => 'Pac. referidos de comunidad/medicina tradicional', // F26
        'contraref_enviada' => 'Pac. referidos a la medicina tradicional',         // F27
    ];

    // Procedimientos según Form 301 F30-F42 (coincide con importador .ves G03)
    public static array $procedimientosOdonto = [
        'primera_consulta' => 'Primera consulta',                      // F30
        'consulta_nueva' => 'Consulta nueva',                         // F31
        'consulta_repetida' => 'Consulta repetida',                      // F32
        'medidas_preventivas' => 'Medidas preventivas',                    // F33
        'restauraciones' => 'Restauraciones',                         // F34
        'endodoncias' => 'Endodoncia',                             // F35
        'periodoncia' => 'Periodoncia',                            // F36
        'cirugia_menor' => 'Cirugía bucal menor',                   // F37
        'cirugia_mediana' => 'Cirugía bucomaxilofacial mediana',      // F38-39
        'fracturas_dentoalveolares' => 'Trat. fracturas dentoalveolares',       // F40
        'TOIT' => 'Trat. Odontológico Integral (TOIT)',     // F41
        'exodoncias' => 'Exodoncia',                              // F(≈F37 sub)
        'rayos_x' => 'Rayos X dental',                        // F42
    ];

    // Grupos según Form 301 F28 (5 grupos, coincide con .ves G03)
    public static array $gruposOdonto = [
        'menor_5' => '< 5 años',
        '5_9' => '5-13 años',   // Form 301: "5 a 13 años"
        '15_19' => '14-19 años',  // Form 301: "14 a 19 años"
        '20_39' => '20-59 años',  // Form 301: "20 a 59 años"
        'mayor_60' => '≥ 60 años',
    ];

    public static array $tiposEnfermeria = [
        'sueros_administrados' => 'Sueros administrados',
        'inyecciones_administradas' => 'Inyecciones administradas',
        'curaciones' => 'Curaciones',
        'nebulizaciones' => 'Nebulizaciones',
        'cirugia_menor' => 'Cirugía menor',
        'cirugia_mayor' => 'Cirugía mayor',
        'atencion_emergencia' => 'Atención de emergencia',
    ];

    public static array $indicadoresInternacion = [
        'egresos' => 'Nro. de egresos',
        'fallecidos' => 'Nro. de fallecidos',
        'dias_estancia_egresos' => 'Días de estancia de egresos',
        'dias_cama_disponible' => 'Días cama disponible',
        'dias_cama_ocupada' => 'Días cama ocupada',
        'infecciones_intrahospitalarias' => 'Infecciones intrahospitalarias',
    ];

    public function mount(): void
    {
        $this->mountFormulario();
        $this->cargarDatos(auth()->user()->centro_salud_id);
    }

    private function cargarDatos(int $centroId): void
    {
        // Consulta Externa
        $gruposConsulta = ['menor_6m', '6m_menor_1', '1_4', '5_9', '10_14', '15_19', '20_39', '40_49', '50_59', 'mayor_60'];
        $rowsConsulta = PrestConsultaExterna::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy('grupo_etareo');
        foreach ($gruposConsulta as $grupo) {
            $row = $rowsConsulta->get($grupo);
            $this->consultaExterna[$grupo] = [
                'primera_m' => $row->primera_m ?? 0, 'primera_f' => $row->primera_f ?? 0,
                'nueva_m' => $row->nueva_m ?? 0, 'nueva_f' => $row->nueva_f ?? 0,
                'repetida_m' => $row->repetida_m ?? 0, 'repetida_f' => $row->repetida_f ?? 0,
            ];
        }

        // Referencias
        $rowsRef = PrestReferencia::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy('tipo');
        foreach (array_keys(self::$tiposReferencia) as $tipo) {
            $row = $rowsRef->get($tipo);
            $this->referencias[$tipo] = [
                'masculino' => $row->masculino ?? 0,
                'femenino' => $row->femenino ?? 0,
            ];
        }

        // Odontología
        $rowsOdonto = PrestOdontologia::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy(fn ($r) => "{$r->procedimiento}__{$r->grupo_etareo}");
        foreach (array_keys(self::$procedimientosOdonto) as $proc) {
            foreach (array_keys(self::$gruposOdonto) as $ge) {
                $key = "{$proc}__{$ge}";
                $row = $rowsOdonto->get($key);
                $this->odontologia[$key] = [
                    'masculino' => $row->masculino ?? 0,
                    'femenino' => $row->femenino ?? 0,
                ];
            }
        }

        // Enfermería
        $rowsEnf = PrestEnfermeria::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy('tipo');
        foreach (array_keys(self::$tiposEnfermeria) as $tipo) {
            $row = $rowsEnf->get($tipo);
            $this->enfermeria[$tipo] = $row->cantidad ?? 0;
        }

        // Internaciones
        $rowsInt = PrestInternacion::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy('indicador');
        foreach (array_keys(self::$indicadoresInternacion) as $ind) {
            $row = $rowsInt->get($ind);
            $this->internaciones[$ind] = $row->cantidad ?? 0;
        }

        // Actividades
        // Filas 126-136 Form 301 — PCD va en la sección de Referencias, NO aquí
        $tiposActividad = [
            'actividades_con_comunidad', 'cai_establecimiento', 'comunidades_en_cai',
            'familias_nuevas_carpetizadas', 'familias_seguimiento',
            'visitas_primeras', 'visitas_segundas', 'visitas_terceras',
            'reuniones_autoridades', 'reuniones_comites_salud',
            'actividades_educativas_salud',
        ];
        $rowsActividad = PrestActividadComunidad::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy('tipo_actividad');
        foreach ($tiposActividad as $ta) {
            $row = $rowsActividad->get($ta);
            $this->actividades[$ta] = $row->cantidad ?? 0;
        }

        // Observaciones
        $obs = ObservacionMensual::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)->first();
        $this->observaciones = $obs->texto ?? '';

        // Causas de Consulta Externa (10 posiciones × 10 grupos etáreos)
        $grupos = array_keys(CausaConsultaExterna::$grupos);
        $rowsCausas = CausaConsultaExterna::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()
            ->groupBy('posicion');

        for ($pos = 1; $pos <= 10; $pos++) {
            $registros = $rowsCausas->get($pos, collect());
            $diagnostico = $registros->first()?->diagnostico ?? '';
            $grupoData = [];
            foreach ($grupos as $g) {
                $reg = $registros->firstWhere('grupo_etareo', $g);
                $grupoData[$g] = ['m' => $reg->masculino ?? 0, 'f' => $reg->femenino ?? 0];
            }
            $this->causasConsulta[$pos] = [
                'diagnostico' => $diagnostico,
                'grupos' => $grupoData,
            ];
        }
    }

    public function guardarConsultaExterna(): void
    {
        if ($this->mesCerrado) {
            return;
        }
        $centroId = auth()->user()->centro_salud_id;

        foreach ($this->consultaExterna as $grupo => $datos) {
            PrestConsultaExterna::updateOrCreate(
                ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'grupo_etareo' => $grupo],
                array_map('intval', $datos)
            );
        }

        Notification::make()->title('Consulta externa guardada')->success()->send();
    }

    public function guardarReferencias(): void
    {
        if ($this->mesCerrado) {
            return;
        }
        $centroId = auth()->user()->centro_salud_id;

        foreach ($this->referencias as $tipo => $datos) {
            PrestReferencia::updateOrCreate(
                ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'tipo' => $tipo],
                ['masculino' => (int) $datos['masculino'], 'femenino' => (int) $datos['femenino']]
            );
        }

        Notification::make()->title('Referencias guardadas')->success()->send();
    }

    public function guardarOdontologia(): void
    {
        if ($this->mesCerrado) {
            return;
        }
        $centroId = auth()->user()->centro_salud_id;

        foreach (array_keys(self::$procedimientosOdonto) as $proc) {
            foreach (array_keys(self::$gruposOdonto) as $ge) {
                $key = "{$proc}__{$ge}";
                $datos = $this->odontologia[$key] ?? [];
                PrestOdontologia::updateOrCreate(
                    ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'procedimiento' => $proc, 'grupo_etareo' => $ge],
                    ['masculino' => (int) ($datos['masculino'] ?? 0), 'femenino' => (int) ($datos['femenino'] ?? 0)]
                );
            }
        }

        Notification::make()->title('Odontología guardada')->success()->send();
    }

    public function guardarEnfermeria(): void
    {
        if ($this->mesCerrado) {
            return;
        }
        $centroId = auth()->user()->centro_salud_id;

        foreach ($this->enfermeria as $tipo => $cantidad) {
            PrestEnfermeria::updateOrCreate(
                ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'tipo' => $tipo],
                ['cantidad' => (int) $cantidad]
            );
        }

        Notification::make()->title('Enfermería guardada')->success()->send();
    }

    public function guardarInternaciones(): void
    {
        if ($this->mesCerrado) {
            return;
        }
        $centroId = auth()->user()->centro_salud_id;

        foreach ($this->internaciones as $ind => $cantidad) {
            PrestInternacion::updateOrCreate(
                ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'indicador' => $ind],
                ['cantidad' => (int) $cantidad]
            );
        }

        Notification::make()->title('Internaciones guardadas')->success()->send();
    }

    public function guardarActividades(): void
    {
        if ($this->mesCerrado) {
            return;
        }
        $centroId = auth()->user()->centro_salud_id;

        foreach ($this->actividades as $ta => $cantidad) {
            PrestActividadComunidad::updateOrCreate(
                ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'tipo_actividad' => $ta],
                ['cantidad' => (int) $cantidad]
            );
        }

        Notification::make()->title('Actividades guardadas')->success()->send();
    }

    public function guardarObservaciones(): void
    {
        if ($this->mesCerrado) {
            return;
        }
        $centroId = auth()->user()->centro_salud_id;

        ObservacionMensual::updateOrCreate(
            ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio],
            ['texto' => $this->observaciones, 'registrado_por' => auth()->id()]
        );

        Notification::make()->title('Observaciones guardadas')->success()->send();
    }

    public function guardarCausasConsulta(): void
    {
        if ($this->mesCerrado) {
            return;
        }
        $centroId = auth()->user()->centro_salud_id;
        $grupos = array_keys(CausaConsultaExterna::$grupos);

        for ($pos = 1; $pos <= 10; $pos++) {
            $causa = $this->causasConsulta[$pos] ?? [];
            $diagnostico = trim($causa['diagnostico'] ?? '');

            foreach ($grupos as $g) {
                $m = (int) ($causa['grupos'][$g]['m'] ?? 0);
                $f = (int) ($causa['grupos'][$g]['f'] ?? 0);

                if ($diagnostico === '' && $m === 0 && $f === 0) {
                    // Eliminar registros vacíos
                    CausaConsultaExterna::where('centro_salud_id', $centroId)
                        ->where('mes', $this->mes)->where('anio', $this->anio)
                        ->where('posicion', $pos)->where('grupo_etareo', $g)
                        ->delete();

                    continue;
                }

                CausaConsultaExterna::updateOrCreate(
                    [
                        'centro_salud_id' => $centroId,
                        'mes' => $this->mes,
                        'anio' => $this->anio,
                        'posicion' => $pos,
                        'grupo_etareo' => $g,
                    ],
                    [
                        'diagnostico' => $diagnostico ?: "Causa $pos",
                        'masculino' => $m,
                        'femenino' => $f,
                    ]
                );
            }
        }

        Notification::make()->title('Causas de consulta guardadas')->success()->send();
    }
}
