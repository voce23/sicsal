<?php

namespace App\Filament\Pages\Concerns;

use App\Filament\Pages\Prestaciones;
use App\Models\JustificacionCero;
use App\Models\MesCerrado;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

trait FormularioMensualTrait
{
    #[Url]
    public int $mes = 1;

    #[Url]
    public int $anio = 0;

    public bool $mesCerrado = false;

    public string $justIndicador = '';
    public string $justMotivo = '';
    public string $justDetalle = '';

    private static array $nombresMeses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function getTitle(): string
    {
        $seccion = static::SECCION ?? 'Prestaciones';

        return $seccion . ' — ' . (self::$nombresMeses[$this->mes] ?? '') . ' ' . $this->anio;
    }

    public function mountFormulario(): void
    {
        $this->mes = (int) request()->query('mes', date('n'));
        $this->anio = (int) request()->query('anio', date('Y'));

        $centroId = auth()->user()->centro_salud_id;

        $this->mesCerrado = MesCerrado::where('centro_salud_id', $centroId)
            ->where('mes', $this->mes)
            ->where('anio', $this->anio)
            ->exists();
    }

    public function abrirJustificacion(string $indicador): void
    {
        $this->justIndicador = $indicador;
        $this->justMotivo = '';
        $this->justDetalle = '';
        $this->dispatch('open-modal', id: 'justificacion-cero');
    }

    public function guardarJustificacion(): void
    {
        $this->validate([
            'justMotivo' => 'required',
        ], [
            'justMotivo.required' => 'Debe seleccionar un motivo.',
        ]);

        JustificacionCero::updateOrCreate(
            [
                'centro_salud_id' => auth()->user()->centro_salud_id,
                'mes' => $this->mes,
                'anio' => $this->anio,
                'indicador' => $this->justIndicador,
            ],
            [
                'motivo' => $this->justMotivo,
                'detalle' => $this->justMotivo === 'otro' ? $this->justDetalle : null,
                'registrado_por' => auth()->id(),
            ]
        );

        $this->dispatch('close-modal', id: 'justificacion-cero');
        Notification::make()->title('Justificación de cero registrada')->success()->send();
    }

    public function cerrarMes(): void
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['superadmin', 'admin'])) {
            return;
        }

        MesCerrado::updateOrCreate(
            ['centro_salud_id' => $user->centro_salud_id, 'mes' => $this->mes, 'anio' => $this->anio],
            ['cerrado_por' => $user->id, 'fecha_cierre' => now()]
        );

        $this->mesCerrado = true;
        Notification::make()->title('Mes cerrado correctamente')->success()->send();
    }

    public function reabrirMes(): void
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['superadmin', 'admin'])) {
            return;
        }

        $mc = MesCerrado::where('centro_salud_id', $user->centro_salud_id)
            ->where('mes', $this->mes)->where('anio', $this->anio)->first();

        if ($mc) {
            $mc->update(['reabierto_por' => $user->id, 'fecha_reapertura' => now()]);
            $mc->delete();
        }

        $this->mesCerrado = false;
        Notification::make()->title('Mes reabierto correctamente')->warning()->send();
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        $user = auth()->user();
        if ($user->hasAnyRole(['superadmin', 'admin'])) {
            if ($this->mesCerrado) {
                $actions[] = Action::make('reabrirMes')
                    ->label('Reabrir mes')
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('¿Reabrir este mes?')
                    ->modalDescription('Esto permitirá editar los datos nuevamente.')
                    ->action(fn () => $this->reabrirMes());
            } else {
                $actions[] = Action::make('cerrarMes')
                    ->label('Cerrar mes')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('¿Cerrar este mes?')
                    ->modalDescription('Los registradores no podrán modificar los datos.')
                    ->action(fn () => $this->cerrarMes());
            }
        }

        $actions[] = Action::make('volver')
            ->label('Volver al selector')
            ->icon(Heroicon::OutlinedArrowLeft)
            ->color('gray')
            ->url(Prestaciones::getUrl());

        return $actions;
    }
}
