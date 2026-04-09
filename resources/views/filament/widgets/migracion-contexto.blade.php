<x-filament-widgets::widget>
    <x-filament::section heading="Contexto de Migración" icon="heroicon-o-arrow-right-start-on-rectangle" icon-color="warning">
        @php $d = $this->datos; @endphp

        <div class="space-y-2 text-sm">
            <div class="flex justify-between border-b pb-2 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Mujeres 15-49 activas en padrón (MEF)</span>
                <span class="font-semibold">{{ number_format($d['mefActivas']) }}</span>
            </div>
            <div class="flex justify-between border-b pb-2 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-400">Mujeres 15-49 migradas registradas</span>
                <span class="font-semibold">{{ number_format($d['mefMigradas']) }} <span class="text-xs text-gray-400">({{ $d['pctMefMigradas'] }}%)</span></span>
            </div>
            <div class="flex justify-between pb-2">
                <span class="text-gray-600 dark:text-gray-400">Total migrantes registrados</span>
                <span class="font-semibold">{{ number_format($d['migrantes']) }} <span class="text-xs text-gray-400">({{ $d['pctMigrantes'] }}% del padrón)</span></span>
            </div>
        </div>

        @if ($d['pctMigrantes'] > 10)
            <div class="mt-3 rounded border border-amber-200 bg-amber-50 p-2 text-xs text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                Este indicador explica baja cobertura en: Control prenatal · Partos · Planificación familiar · Vacunación.
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
