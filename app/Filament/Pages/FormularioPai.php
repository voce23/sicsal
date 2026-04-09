<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\FormularioMensualTrait;
use App\Models\PrestCrecimiento;
use App\Models\PrestMicronutriente;
use App\Models\PrestVacuna;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class FormularioPai extends Page
{
    use FormularioMensualTrait;

    protected string $view = 'filament.pages.formulario-pai';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Prestaciones Mensuales';

    protected static ?string $navigationLabel = 'PAI';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;

    const SECCION = 'PAI — Salud Infantil';

    public array $vacunas = [];
    public array $micronutrientes = [];
    public array $crecimiento = [];

    /** Sección 1 RNVE: Vacunaciones en menores de 5 años. */
    public static array $vacunasMenores5 = [
        ['BCG', 'BCG'],
        ['Pentavalente_1', 'Pentavalente 1ra dosis'],
        ['Pentavalente_2', 'Pentavalente 2da dosis'],
        ['Pentavalente_3', 'Pentavalente 3ra dosis'],
        ['Pentavalente_4', 'Pentavalente 4ta dosis'],
        ['Pentavalente_5', 'Pentavalente 5ta dosis'],
        ['IPV_1', 'Antipoliomielítica 1ra (IPV)'],
        ['bOPV_2', 'Antipoliomielítica 2da (bOPV)'],
        ['IPV_3', 'Antipoliomielítica 3ra (IPV)'],
        ['bOPV_4', 'Antipoliomielítica 4ta (bOPV)'],
        ['bOPV_5', 'Antipoliomielítica 5ta (bOPV)'],
        ['Antirotavirica_1', 'Antirotavírica 1ra dosis'],
        ['Antirotavirica_2', 'Antirotavírica 2da dosis'],
        ['Antineumococica_1', 'Antineumocócica 1ra dosis'],
        ['Antineumococica_2', 'Antineumocócica 2da dosis'],
        ['Antineumococica_3', 'Antineumocócica 3ra dosis'],
        ['Influenza_6_11m_1', 'Influenza estacional (6m a 11m) 1ra'],
        ['Influenza_7_11m_2', 'Influenza estacional (7m a 11m) 2da'],
        ['SRP_1', 'SRP 1ra dosis'],
        ['SRP_2', 'SRP 2da dosis'],
        ['Antiamarilica', 'Antiamarílica dosis única'],
        ['Influenza_unica_ninos', 'Influenza estacional dosis única'],
        ['Influenza_enf_cronicas_ninos', 'Influenza a niños con enf. crónicas'],
    ];

    public static array $gruposMenores5 = [
        'menor_1' => 'Menor de 1 año',
        '12_23m' => '12 a 23 meses',
        '2_anios' => '2 años',
        '3_anios' => '3 años',
        '4_anios' => '4 años',
    ];

    /** Sección 2 RNVE: Otras vacunaciones. */
    public static array $vacunasOtras = [
        ['dT_1', 'dT 1ra dosis'],
        ['dT_2', 'dT 2da dosis'],
        ['dT_3', 'dT 3ra dosis'],
        ['dT_4', 'dT 4ta dosis'],
        ['dT_5', 'dT 5ta dosis'],
        ['VPH_1', 'VPH 1ra dosis (dosis única)'],
        ['VPH_2', 'VPH 2da dosis'],
        ['SR', 'SR'],
        ['Antiamarilica_adultos', 'Antiamarílica (dosis única)'],
        ['HepB_salud_1', 'Hepatitis B 1ra trabajadores de salud'],
        ['HepB_salud_2', 'Hepatitis B 2da trabajadores de salud'],
        ['HepB_salud_3', 'Hepatitis B 3ra trabajadores de salud'],
        ['HepB_VIH_1', 'Hepatitis B 1ra población vulnerable (VIH)'],
        ['HepB_VIH_2', 'Hepatitis B 2da población vulnerable (VIH)'],
        ['HepB_VIH_3', 'Hepatitis B 3ra población vulnerable (VIH)'],
        ['HepB_renal_1', 'Hepatitis B 1ra enf. renales'],
        ['HepB_renal_2', 'Hepatitis B 2da enf. renales'],
        ['HepB_renal_3', 'Hepatitis B 3ra enf. renales'],
        ['Influenza_estacional', 'Influenza estacional'],
        ['Influenza_enf_cronicas', 'Influenza a personas con enf. crónicas'],
        ['Influenza_embarazadas', 'Influenza a mujeres embarazadas'],
        ['Influenza_personal_salud', 'Influenza a personal de salud'],
        ['COVID_1', 'COVID-19 1ra dosis'],
        ['COVID_2', 'COVID-19 2da dosis'],
        ['COVID_3', 'COVID-19 3ra dosis'],
        ['COVID_anual', 'COVID-19 dosis anual'],
        ['COVID_unica', 'COVID-19 dosis única'],
        ['COVID_refuerzo', 'COVID-19 dosis de refuerzo'],
    ];

    public static array $gruposOtrasVac = [
        '5_9' => '5 a 9 años',
        '10_anios' => '10 años',
        '11_anios' => '11 años',
        '12_20' => '12 a 20 años',
        '21_59' => '21 a 59 años',
        '60_mas' => '60 y +',
    ];

    public static function allVacunasConfig(): array
    {
        $all = [];
        foreach (self::$vacunasMenores5 as [$tipo, $label]) {
            foreach (self::$gruposMenores5 as $ge => $geLabel) {
                $all[] = [$tipo, $ge, $label];
            }
        }
        foreach (self::$vacunasOtras as [$tipo, $label]) {
            foreach (self::$gruposOtrasVac as $ge => $geLabel) {
                $all[] = [$tipo, $ge, $label];
            }
        }

        return $all;
    }

    public function mount(): void
    {
        $this->mountFormulario();
        $this->cargarDatos(auth()->user()->centro_salud_id);
    }

    private function cargarDatos(int $centroId): void
    {
        // Vacunas
        $rowsVacunas = PrestVacuna::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy(fn ($r) => "{$r->tipo_vacuna}__{$r->grupo_etareo}");
        foreach (self::allVacunasConfig() as [$tipoVacuna, $grupoEtareo, $label]) {
            $key = "{$tipoVacuna}__{$grupoEtareo}";
            $row = $rowsVacunas->get($key);
            $this->vacunas[$key] = [
                'dentro_m' => $row->dentro_m ?? 0, 'dentro_f' => $row->dentro_f ?? 0,
                'fuera_m' => $row->fuera_m ?? 0, 'fuera_f' => $row->fuera_f ?? 0,
            ];
        }

        // Micronutrientes — orden exacto del formulario 301 (filas AE7-AE27)
        $tiposMicro = [
            // Hierro (filas AE7-AE12)
            'hierro_embarazadas_completo',  // AE7  - Mujeres embarazadas
            'hierro_puerperas_completo',    // AE8  - Puérperas
            'hierro_menor_6m',              // AE9  - <6 meses (desde 4m)
            'hierro_menor_1',               // AE10 - <1 año
            'hierro_1anio',                 // AE11 - 1 año
            'hierro_2_5',                   // AE12 - 2 a <5 años
            // Vitamina A (filas AE13-AE18)
            'vitA_puerpera_unica',          // AE13 - Puérperas dosis única
            'vitA_menor_1_unica',           // AE14 - <1 año dosis única
            'vitA_1anio_1ra',               // AE15 - 1 año 1ra dosis
            'vitA_1anio_2da',               // AE16 - 1 año 2da dosis
            'vitA_2_5_1ra',                 // AE17 - 2 a <5 años 1ra dosis
            'vitA_2_5_2da',                 // AE18 - 2 a <5 años 2da dosis
            // Zinc (filas AE19-AE20)
            'zinc_menor_1',                 // AE19 - <1 año (talla baja)
            'zinc_1anio',                   // AE20 - 1 año (talla baja)
            // Alimento complementario / Nutribebé (filas AE21-AE22)
            'nutribebe_menor_1',            // AE21 - <1 año (Nutribebé)
            'nutribebe_1anio',              // AE22 - 1 año (Nutribebé)
            // Lactancia materna (filas AE23-AE24)
            'lactancia_inmediata',          // AE23 - RN con lactancia inmediata
            'lactancia_exclusiva_6m',       // AE24 - 6 meses con lactancia exclusiva
            // Carmelo adultos mayores (fila AE25)
            'carmelo_mayor_60',             // AE25 - Adultos >60 años
            // Nutrimamá (filas AE26-AE27)
            'nutrimama_embarazada',         // AE26 - Embarazadas
            'nutrimama_lactancia',          // AE27 - Mujeres en lactancia
        ];
        $rowsMicro = PrestMicronutriente::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy('tipo');
        foreach ($tiposMicro as $tipo) {
            $row = $rowsMicro->get($tipo);
            $this->micronutrientes[$tipo] = $row->cantidad ?? 0;
        }

        // Crecimiento
        $gruposCrecimiento = ['menor_1_dentro', 'menor_1_fuera', '1_menor_2_dentro', '1_menor_2_fuera', '2_menor_5_dentro', '2_menor_5_fuera'];
        $rowsCrecimiento = PrestCrecimiento::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy('grupo_etareo');
        foreach ($gruposCrecimiento as $ge) {
            $row = $rowsCrecimiento->get($ge);
            $this->crecimiento[$ge] = [
                'nuevos_m' => $row->nuevos_m ?? 0, 'nuevos_f' => $row->nuevos_f ?? 0,
                'repetidos_m' => $row->repetidos_m ?? 0, 'repetidos_f' => $row->repetidos_f ?? 0,
            ];
        }
    }

    public function guardarVacunas(): void
    {
        if ($this->mesCerrado) return;
        $centroId = auth()->user()->centro_salud_id;

        foreach (self::allVacunasConfig() as [$tipoVacuna, $grupoEtareo, $label]) {
            $key = "{$tipoVacuna}__{$grupoEtareo}";
            $datos = $this->vacunas[$key] ?? [];
            $values = array_map('intval', $datos);
            if (array_sum($values) > 0) {
                PrestVacuna::updateOrCreate(
                    ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'tipo_vacuna' => $tipoVacuna, 'grupo_etareo' => $grupoEtareo],
                    $values
                );
            }
        }

        Notification::make()->title('Vacunas guardadas')->success()->send();
    }

    public function guardarMicronutrientes(): void
    {
        if ($this->mesCerrado) return;
        $centroId = auth()->user()->centro_salud_id;

        foreach ($this->micronutrientes as $tipo => $cantidad) {
            PrestMicronutriente::updateOrCreate(
                ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'tipo' => $tipo],
                ['cantidad' => (int) $cantidad]
            );
        }

        $this->verificarCeroMicronutrientes($centroId);
        Notification::make()->title('Micronutrientes guardados')->success()->send();
    }

    public function guardarCrecimiento(): void
    {
        if ($this->mesCerrado) return;
        $centroId = auth()->user()->centro_salud_id;

        foreach ($this->crecimiento as $ge => $datos) {
            PrestCrecimiento::updateOrCreate(
                ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'grupo_etareo' => $ge],
                array_map('intval', $datos)
            );
        }

        $this->verificarCeroCrecimiento($centroId);
        Notification::make()->title('Crecimiento guardado')->success()->send();
    }

    private function verificarCeroCrecimiento(int $centroId): void
    {
        $total = PrestCrecimiento::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->sum(\DB::raw('nuevos_m + nuevos_f + repetidos_m + repetidos_f'));

        if ($total == 0) {
            $this->dispatch('abrir-justificacion-cero', indicador: 'control_crecimiento');
        }
    }

    private function verificarCeroMicronutrientes(int $centroId): void
    {
        $total = PrestMicronutriente::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->whereIn('tipo', ['hierro_menor_6m', 'hierro_menor_1', 'hierro_1anio', 'hierro_2_5',
                'vitA_menor_1_unica', 'vitA_1anio_1ra', 'vitA_1anio_2da', 'vitA_2_5_1ra', 'vitA_2_5_2da',
                'zinc_menor_1', 'zinc_1anio'])
            ->sum('cantidad');

        if ($total == 0) {
            $this->dispatch('abrir-justificacion-cero', indicador: 'micronutrientes_menor_5');
        }
    }
}
