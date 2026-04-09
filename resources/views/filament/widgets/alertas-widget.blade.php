<x-filament-widgets::widget>
    <x-filament::section heading="Alertas Activas" icon="heroicon-o-bell-alert" icon-color="danger">
        @php $d = $this->datos; @endphp

        <div class="space-y-3">
            @if (count($d['mesesSinCerrar']) > 0)
                <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-700 dark:bg-amber-900/20">
                    <x-heroicon-s-exclamation-triangle class="h-5 w-5 shrink-0 text-amber-500" />
                    <div>
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">Meses sin cerrar en {{ $d['anio'] }}</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400">{{ implode(', ', $d['mesesSinCerrar']) }}</p>
                    </div>
                </div>
            @endif

            @if ($d['totalPersonas'] === 0)
                <div class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-700 dark:bg-blue-900/20">
                    <x-heroicon-s-information-circle class="h-5 w-5 shrink-0 text-blue-500" />
                    <div>
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-300">Sin personas registradas</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400">Registre personas en el padrón para ver estadísticas.</p>
                    </div>
                </div>
            @endif

            @if (count($d['mesesSinCerrar']) === 0 && $d['totalPersonas'] > 0)
                <div class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-700 dark:bg-green-900/20">
                    <x-heroicon-s-check-circle class="h-5 w-5 shrink-0 text-green-500" />
                    <p class="text-sm font-medium text-green-800 dark:text-green-300">Sin alertas pendientes</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
