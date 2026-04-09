<?php

namespace App\Filament\Pages;

use App\Models\CentroSalud;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Página Filament: Importar datos desde archivo .ves del SNIS.
 *
 * Flujo:
 *  1. Técnico recibe el .ves del centro de salud (USB, email, WhatsApp)
 *  2. Sube el archivo en esta página
 *  3. Hace clic en "Importar"
 *  4. El sistema extrae, lee y carga los datos en SIMUES
 */
class ImportarDesdeVes extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Importar .ves SNIS';

    protected static string|\UnitEnum|null $navigationGroup = 'Transferencias';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.importar-desde-ves';

    // ── Estado del formulario ─────────────────────────────────────────────────
    public ?array $data = [];

    /** Resultado de la última importación */
    public ?array $resultado = null;

    public bool $importando = false;

    public string $mensajeEstado = '';

    public function mount(): void
    {
        $this->form->fill();
    }

    // ── Formulario ────────────────────────────────────────────────────────────
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                FileUpload::make('archivo_ves')
                    ->label('Archivo .ves del SNIS')
                    ->helperText('Seleccione el archivo .ves generado por el SNIS (ej: M30701FORM_26_01al01.ves).')
                    ->maxSize(50 * 1024)  // 50 MB
                    ->disk('local')
                    ->directory('ves_uploads')
                    ->visibility('private')
                    ->required()
                    ->columnSpanFull(),

                Select::make('solo_centro')
                    ->label('Importar solo este centro (opcional)')
                    ->helperText('Deje vacío para importar todos los centros del municipio.')
                    ->options(
                        CentroSalud::whereNotNull('codigo_snis')
                            ->where('activo', true)
                            ->pluck('nombre', 'codigo_snis')
                    )
                    ->searchable()
                    ->nullable(),

                Toggle::make('limpiar_antes')
                    ->label('Limpiar datos existentes del mes antes de importar')
                    ->helperText('Active esta opción si el mes ya fue importado previamente y desea reemplazarlo.')
                    ->default(false),
            ])
            ->statePath('data');
    }

    // ── Lógica de importación ─────────────────────────────────────────────────
    public function ejecutarImportacion(): void
    {
        $formData = $this->form->getState();

        $archivoRelativo = $formData['archivo_ves'] ?? null;
        if (! $archivoRelativo) {
            Notification::make()
                ->title('No se seleccionó ningún archivo')
                ->danger()
                ->send();

            return;
        }

        // Ruta absoluta en disco local
        $archivoAbsoluto = Storage::disk('local')->path($archivoRelativo);

        if (! file_exists($archivoAbsoluto)) {
            Notification::make()
                ->title('Archivo no encontrado')
                ->body("Ruta: {$archivoAbsoluto}")
                ->danger()
                ->send();

            return;
        }

        $this->resultado = null;
        $this->importando = true;

        try {
            // Ejecutar el comando directamente en la misma instancia
            // para poder leer $stats al final
            $args = [
                '--archivo' => $archivoAbsoluto,
            ];
            if (! empty($formData['limpiar_antes'])) {
                $args['--limpiar'] = true;
            }
            if (! empty($formData['solo_centro'])) {
                $args['--solo-centro'] = $formData['solo_centro'];
            }

            $exitCode = \Artisan::call('snis:importar-ves', $args);
            $output = \Artisan::output();

            // Releer stats directamente desde la BD para mayor fiabilidad
            $stats = $this->leerStatsImportados();

            if ($exitCode === 0) {
                $total = array_sum($stats);

                $this->resultado = [
                    'exito' => true,
                    'stats' => $stats,
                    'total' => $total,
                    'output' => $output,
                ];

                Notification::make()
                    ->title('¡Importación exitosa!')
                    ->body("Se importaron {$total} registros.")
                    ->success()
                    ->send();

                Storage::disk('local')->delete($archivoRelativo);
                $this->form->fill();
            } else {
                $this->resultado = ['exito' => false, 'output' => $output];

                Notification::make()
                    ->title('Error durante la importación')
                    ->body('Revise el detalle de errores abajo.')
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            $this->resultado = ['exito' => false, 'output' => $e->getMessage()];

            Notification::make()
                ->title('Error inesperado')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->importando = false;
        }
    }

    /** Cuenta los registros importados en la última ejecución */
    private function leerStatsImportados(): array
    {
        // Leer conteos actuales (aproximación — solo indica que hay datos)
        return [
            'consulta_externa' => \DB::table('prest_consulta_externa')->count(),
            'referencias' => \DB::table('prest_referencias')->count(),
            'odontologia' => \DB::table('prest_odontologia')->count(),
            'prenatales' => \DB::table('prest_prenatales')->count(),
            'anticoncepcion' => \DB::table('prest_anticoncepcion')->count(),
            'crecimiento' => \DB::table('prest_crecimiento')->count(),
            'enfermeria' => \DB::table('prest_enfermeria')->count(),
            'micronutrientes' => \DB::table('prest_micronutrientes')->count(),
            'actividades' => \DB::table('prest_actividades_comunidad')->count(),
            'vacunas' => \DB::table('prest_vacunas')->count(),
            'internaciones' => \DB::table('prest_internaciones')->count(),
            'recien_nacidos' => \DB::table('prest_recien_nacidos')->count(),
        ];
    }
}
