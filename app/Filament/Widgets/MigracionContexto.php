<?php

namespace App\Filament\Widgets;

use App\Models\Persona;
use Filament\Widgets\Widget;

class MigracionContexto extends Widget
{
    protected string $view = 'filament.widgets.migracion-contexto';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function getDatosProperty(): array
    {
        $centroId = auth()->user()->centro_salud_id;
        if (! $centroId) {
            return ['mefActivas' => 0, 'mefMigradas' => 0, 'migrantes' => 0, 'pctMigrantes' => 0, 'pctMefMigradas' => 0];
        }

        $totalPadron = Persona::where('centro_salud_id', $centroId)->where('activo', true)->count();
        $migrantes = Persona::where('centro_salud_id', $centroId)->where('activo', true)->where('estado', 'migrado')->count();

        $mefActivas = Persona::where('centro_salud_id', $centroId)
            ->where('activo', true)->where('sexo', 'F')->where('estado', '!=', 'migrado')
            ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 15 AND 49')->count();

        $mefMigradas = Persona::where('centro_salud_id', $centroId)
            ->where('activo', true)->where('sexo', 'F')->where('estado', 'migrado')
            ->whereRaw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 15 AND 49')->count();

        return [
            'mefActivas' => $mefActivas,
            'mefMigradas' => $mefMigradas,
            'migrantes' => $migrantes,
            'pctMigrantes' => $totalPadron > 0 ? round($migrantes / $totalPadron * 100, 1) : 0,
            'pctMefMigradas' => ($mefActivas + $mefMigradas) > 0 ? round($mefMigradas / ($mefActivas + $mefMigradas) * 100, 1) : 0,
        ];
    }
}
