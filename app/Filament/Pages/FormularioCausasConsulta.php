<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\FormularioMensualTrait;
use App\Models\CausaConsultaExterna;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class FormularioCausasConsulta extends Page
{
    use FormularioMensualTrait;

    protected string $view = 'filament.pages.formulario-causas-consulta';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static string|\UnitEnum|null $navigationGroup = 'Prestaciones Mensuales';

    protected static ?string $navigationLabel = 'Causas Consulta Externa';

    protected static ?int $navigationSort = 6;

    protected static bool $shouldRegisterNavigation = false;

    const SECCION = 'Causas de Consulta Externa';

    // 10 posiciones, cada una con diagnóstico + M/F por grupo etáreo
    public array $causas = [];

    public static array $grupos = [
        'menor_6m'   => '< 6m',
        '6m_menor_1' => '6m-1a',
        '1_4'        => '1-4',
        '5_9'        => '5-9',
        '10_14'      => '10-14',
        '15_19'      => '15-19',
        '20_39'      => '20-39',
        '40_49'      => '40-49',
        '50_59'      => '50-59',
        'mayor_60'   => '≥60',
    ];

    public function mount(): void
    {
        $this->mountFormulario();
        $this->cargarDatos(auth()->user()->centro_salud_id);
    }

    private function cargarDatos(int $centroId): void
    {
        // Inicializar las 10 posiciones vacías
        $this->causas = [];
        for ($pos = 1; $pos <= 10; $pos++) {
            $grupos = [];
            foreach (array_keys(self::$grupos) as $ge) {
                $grupos[$ge] = ['m' => 0, 'f' => 0];
            }
            $this->causas[$pos] = [
                'diagnostico' => '',
                'grupos'      => $grupos,
            ];
        }

        // Cargar datos existentes agrupando por posición y grupo_etareo
        $rows = CausaConsultaExterna::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->get();

        foreach ($rows as $row) {
            $pos = $row->posicion ?? 1;
            if ($pos < 1 || $pos > 10) continue;

            // El diagnóstico es el mismo para todas las filas de la misma posición
            if (!empty($row->diagnostico)) {
                $this->causas[$pos]['diagnostico'] = $row->diagnostico;
            }

            $ge = $row->grupo_etareo;
            if (isset($this->causas[$pos]['grupos'][$ge])) {
                $this->causas[$pos]['grupos'][$ge] = [
                    'm' => $row->masculino,
                    'f' => $row->femenino,
                ];
            }
        }
    }

    public function guardar(): void
    {
        if ($this->mesCerrado) return;

        $centroId = auth()->user()->centro_salud_id;

        // Borrar las existentes del mes para este centro
        CausaConsultaExterna::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->delete();

        $count = 0;
        foreach ($this->causas as $pos => $causa) {
            $diagnostico = trim($causa['diagnostico'] ?? '');
            if ($diagnostico === '') continue; // omitir filas vacías

            foreach (self::$grupos as $ge => $_label) {
                $m = (int) ($causa['grupos'][$ge]['m'] ?? 0);
                $f = (int) ($causa['grupos'][$ge]['f'] ?? 0);

                CausaConsultaExterna::create([
                    'centro_salud_id' => $centroId,
                    'mes'             => $this->mes,
                    'anio'            => $this->anio,
                    'posicion'        => $pos,
                    'diagnostico'     => $diagnostico,
                    'grupo_etareo'    => $ge,
                    'masculino'       => $m,
                    'femenino'        => $f,
                ]);
                $count++;
            }
        }

        Notification::make()->title('Causas de consulta externa guardadas')->success()->send();
    }

}
