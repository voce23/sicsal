<?php

namespace App\Filament\Widgets;

use App\Models\Defuncion;
use App\Models\MetaIne;
use App\Models\Persona;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EstadisticasPoblacion extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $centroId = auth()->user()->centro_salud_id;

        if (! $centroId) {
            return [
                Stat::make('Sin centro asignado', '—')
                    ->description('Asigne un centro de salud a su usuario')
                    ->color('danger'),
            ];
        }

        $anio = (int) date('Y');

        $activos = Persona::where('centro_salud_id', $centroId)
            ->where('activo', true)
            ->where('estado', '!=', 'migrado')
            ->count();

        $migrantes = Persona::where('centro_salud_id', $centroId)
            ->where('activo', true)
            ->where('estado', 'migrado')
            ->count();

        $defunciones = Defuncion::where('centro_salud_id', $centroId)
            ->whereYear('fecha_defuncion', $anio)
            ->count();

        $metaIne = MetaIne::where('centro_salud_id', $centroId)
            ->where('anio', $anio)
            ->sum('cantidad');

        return [
            Stat::make('Padrón activo', number_format($activos))
                ->description('Residentes activos')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Migrantes', number_format($migrantes))
                ->description('Migración registrada')
                ->descriptionIcon('heroicon-m-arrow-right-start-on-rectangle')
                ->color('warning'),
            Stat::make('Defunciones ' . $anio, number_format($defunciones))
                ->description('Año en curso')
                ->descriptionIcon('heroicon-m-document-minus')
                ->color('danger'),
            Stat::make('Meta INE', number_format($metaIne))
                ->description('Población proyectada ' . $anio)
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
