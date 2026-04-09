<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\FormularioMensualTrait;
use App\Models\PrestAnticoncepcion;
use App\Models\PrestCancerPrevencion;
use App\Models\PrestIle;
use App\Models\PrestParto;
use App\Models\PrestPrenatal;
use App\Models\PrestPuerperio;
use App\Models\PrestRecienNacido;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class FormularioMaterna extends Page
{
    use FormularioMensualTrait;

    protected string $view = 'filament.pages.formulario-materna';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|\UnitEnum|null $navigationGroup = 'Prestaciones Mensuales';

    protected static ?string $navigationLabel = 'Salud Materna';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    const SECCION = 'Salud Materna';

    public array $prenatales = [];

    public array $partos = [];

    public array $puerperio = [];

    public array $recienNacidos = [];

    public array $anticoncepcion = [];

    public array $cancerPrevencion = [];

    public array $ile = [];

    public static array $metodosAnticoncepcion = [
        'DIU' => 'DIU',
        'inyectable_mensual' => 'Inyectable mensual',
        'inyectable_trimestral' => 'Inyectable trimestral',
        'pildora' => 'Píldora',
        'condon_masculino' => 'Condón masculino',
        'condon_femenino' => 'Condón femenino',
        'implante_subdermic' => 'Implante subdérmico',
        'metodos_naturales' => 'Métodos naturales',
        'AQV_femenino' => 'AQV femenino',
        'AQV_masculino' => 'AQV masculino',
        'pildora_emergencia' => 'Píldora de emergencia',
    ];

    public static array $gruposAnticoncepcion = [
        'menor_10' => '< 10', '10_14' => '10-14', '15_19' => '15-19',
        '20_34' => '20-34', '35_49' => '35-49', '50_mas' => '≥ 50',
    ];

    public static array $partosConfig = [
        ['vaginal', 'servicio', 'personal_calificado', 'Parto vaginal en servicio - personal calificado'],  // F57
        ['vaginal', 'domicilio', 'personal_calificado', 'Parto vaginal domicilio - personal calificado'],
        ['vaginal', 'domicilio', 'partera_capacitada', 'Parto vaginal domicilio - partera capacitada'],     // F59
        ['vaginal', 'domicilio', 'partera_empirica', 'Parto vaginal domicilio - partera empírica'],          // F58
        ['vaginal', 'domicilio', 'articulacion', 'Parto vaginal domicilio - articulación'],                  // F56
        ['vaginal', 'domicilio', 'otros', 'Partos atendidos por otros'],                                     // F60
        ['cesarea', 'servicio', 'personal_calificado', 'Cesárea en servicio'],                               // F53
    ];

    public static array $gruposParto = [
        'menor_10' => '< 10', '10_14' => '10-14', '15_19' => '15-19',
        '20_34' => '20-34', '35_49' => '35-49', '50_mas' => '≥ 50',
    ];

    public static array $indicadoresCancer = [
        'pap_nueva' => 'PAP nueva',
        'pap_repetida' => 'PAP repetida',
        'ivaa_nueva' => 'IVAA nueva',
        'ivaa_repetida' => 'IVAA repetida',
        'ivaa_positivo' => 'IVAA positivo',
        'crioterapia' => 'Crioterapia',
        'examen_mama_nueva' => 'Examen clínico mama nueva',
        'examen_mama_repetida' => 'Examen clínico mama repetida',
        'mamografia_referida' => 'Mamografía referida',
    ];

    public function mount(): void
    {
        $this->mountFormulario();
        $this->cargarDatos(auth()->user()->centro_salud_id);
    }

    private function cargarDatos(int $centroId): void
    {
        // Prenatales
        $tiposControl = ['nueva_1er_trim', 'nueva_2do_trim', 'nueva_3er_trim', 'repetida', 'con_4to_control'];
        $gruposPrenatal = ['menor_10', '10_14', '15_19', '20_34', '35_49', '50_mas'];
        $rowsPrenatal = PrestPrenatal::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy(fn ($r) => "{$r->tipo_control}__{$r->grupo_etareo}");
        foreach ($tiposControl as $tc) {
            foreach ($gruposPrenatal as $ge) {
                $row = $rowsPrenatal->get("{$tc}__{$ge}");
                $this->prenatales["{$tc}__{$ge}"] = [
                    'dentro' => $row->dentro ?? 0, 'fuera' => $row->fuera ?? 0,
                ];
            }
        }

        // Partos
        $rowsPartos = PrestParto::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy(fn ($r) => "{$r->tipo}__{$r->lugar}__{$r->atendido_por}__{$r->grupo_etareo}");
        foreach (self::$partosConfig as [$tipo, $lugar, $atendido, $label]) {
            foreach (array_keys(self::$gruposParto) as $ge) {
                $key = "{$tipo}__{$lugar}__{$atendido}__{$ge}";
                $row = $rowsPartos->get($key);
                $this->partos[$key] = $row->cantidad ?? 0;
            }
        }

        // Puerperio
        $rowsPuerperio = PrestPuerperio::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy('tipo_control');
        foreach (['48h', '7dias', '28dias', '42dias'] as $tc) {
            $row = $rowsPuerperio->get($tc);
            $this->puerperio[$tc] = $row->cantidad ?? 0;
        }

        // Recién Nacidos
        $indicadoresRN = [
            'nacidos_vivos_servicio', 'nacidos_vivos_domicilio', 'nacidos_vivos_4cpn',
            'nacidos_vivos_peso_menor_2500', 'nacidos_muertos', 'rn_lactancia_inmediata',
            'rn_alojamiento_conjunto', 'rn_corte_tardio_cordon', 'rn_malformacion_congenita', 'rn_control_48h',
        ];
        $rowsRN = PrestRecienNacido::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy('indicador');
        foreach ($indicadoresRN as $ind) {
            $row = $rowsRN->get($ind);
            $this->recienNacidos[$ind] = $row->cantidad ?? 0;
        }

        // Anticoncepción
        $rowsAnti = PrestAnticoncepcion::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy(fn ($r) => "{$r->metodo}__{$r->tipo_usuaria}__{$r->grupo_etareo}");
        foreach (array_keys(self::$metodosAnticoncepcion) as $metodo) {
            foreach (['nueva', 'continua'] as $tipo) {
                foreach (array_keys(self::$gruposAnticoncepcion) as $ge) {
                    $key = "{$metodo}__{$tipo}__{$ge}";
                    $row = $rowsAnti->get($key);
                    $this->anticoncepcion[$key] = $row->cantidad ?? 0;
                }
            }
        }

        // Prevención Cáncer
        $rowsCancer = PrestCancerPrevencion::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy('indicador');
        foreach (array_keys(self::$indicadoresCancer) as $ind) {
            $row = $rowsCancer->get($ind);
            $this->cancerPrevencion[$ind] = $row->cantidad ?? 0;
        }

        // ILE — Form 301 F63-F65
        $rowsIle = PrestIle::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->get()->keyBy('indicador');
        foreach (['ile_1er_trimestre', 'ile_2do_trimestre', 'ile_3er_trimestre'] as $ind) {
            $this->ile[$ind] = $rowsIle->get($ind)?->cantidad ?? 0;
        }
    }

    public function guardarPrenatales(): void
    {
        if ($this->mesCerrado) {
            return;
        }
        $centroId = auth()->user()->centro_salud_id;

        foreach ($this->prenatales as $key => $datos) {
            [$tc, $ge] = explode('__', $key);
            PrestPrenatal::updateOrCreate(
                ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'tipo_control' => $tc, 'grupo_etareo' => $ge],
                ['dentro' => (int) $datos['dentro'], 'fuera' => (int) $datos['fuera']]
            );
        }

        $this->verificarCeroPrenatales($centroId);
        Notification::make()->title('Prenatales guardados')->success()->send();
    }

    public function guardarPartosYPuerperio(): void
    {
        if ($this->mesCerrado) {
            return;
        }
        $centroId = auth()->user()->centro_salud_id;

        foreach (self::$partosConfig as [$tipo, $lugar, $atendido, $label]) {
            foreach (array_keys(self::$gruposParto) as $ge) {
                $key = "{$tipo}__{$lugar}__{$atendido}__{$ge}";
                PrestParto::updateOrCreate(
                    ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'tipo' => $tipo, 'lugar' => $lugar, 'atendido_por' => $atendido, 'grupo_etareo' => $ge],
                    ['cantidad' => (int) ($this->partos[$key] ?? 0)]
                );
            }
        }

        foreach ($this->puerperio as $tc => $cantidad) {
            PrestPuerperio::updateOrCreate(
                ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'tipo_control' => $tc],
                ['cantidad' => (int) $cantidad]
            );
        }

        foreach ($this->recienNacidos as $ind => $cantidad) {
            PrestRecienNacido::updateOrCreate(
                ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'indicador' => $ind],
                ['cantidad' => (int) $cantidad]
            );
        }

        $this->verificarCeroPuerperio($centroId);
        Notification::make()->title('Partos y puerperio guardados')->success()->send();
    }

    public function guardarAnticoncepcion(): void
    {
        if ($this->mesCerrado) {
            return;
        }
        $centroId = auth()->user()->centro_salud_id;

        foreach (array_keys(self::$metodosAnticoncepcion) as $metodo) {
            foreach (['nueva', 'continua'] as $tipo) {
                foreach (array_keys(self::$gruposAnticoncepcion) as $ge) {
                    $key = "{$metodo}__{$tipo}__{$ge}";
                    PrestAnticoncepcion::updateOrCreate(
                        ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'metodo' => $metodo, 'tipo_usuaria' => $tipo, 'grupo_etareo' => $ge],
                        ['cantidad' => (int) ($this->anticoncepcion[$key] ?? 0)]
                    );
                }
            }
        }

        Notification::make()->title('Anticoncepción guardada')->success()->send();
    }

    public function guardarCancerPrevencion(): void
    {
        if ($this->mesCerrado) {
            return;
        }
        $centroId = auth()->user()->centro_salud_id;

        foreach ($this->cancerPrevencion as $ind => $cantidad) {
            PrestCancerPrevencion::updateOrCreate(
                ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'indicador' => $ind],
                ['cantidad' => (int) $cantidad]
            );
        }

        Notification::make()->title('Prevención de cáncer guardada')->success()->send();
    }

    public function guardarIle(): void
    {
        if ($this->mesCerrado) {
            return;
        }
        $centroId = auth()->user()->centro_salud_id;

        foreach ($this->ile as $ind => $cantidad) {
            PrestIle::updateOrCreate(
                ['centro_salud_id' => $centroId, 'mes' => $this->mes, 'anio' => $this->anio, 'indicador' => $ind],
                ['cantidad' => (int) $cantidad]
            );
        }

        Notification::make()->title('ILE guardada')->success()->send();
    }

    private function verificarCeroPrenatales(int $centroId): void
    {
        $total = PrestPrenatal::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->sum(\DB::raw('dentro + fuera'));

        if ($total == 0) {
            $this->dispatch('abrir-justificacion-cero', indicador: 'control_prenatal');
        }
    }

    private function verificarCeroPuerperio(int $centroId): void
    {
        $total = PrestPuerperio::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)->where('anio', $this->anio)
            ->sum('cantidad');

        if ($total == 0) {
            $this->dispatch('abrir-justificacion-cero', indicador: 'puerperio');
        }
    }
}
