<x-filament-widgets::widget>
    <x-filament::section heading="Cobertura Rápida del Mes" icon="heroicon-o-chart-bar" icon-color="success">
        @php $programas = $this->datos; @endphp

        <div class="space-y-3">
            @foreach ($programas as $prog)
                @php
                    $pct = $prog['meta'] > 0 ? min(round($prog['atendidos'] / $prog['meta'] * 100), 100) : 0;
                    $color = $pct >= 80 ? 'bg-green-500' : ($pct >= 50 ? 'bg-yellow-500' : 'bg-red-500');
                @endphp
                <div>
                    <div class="mb-1 flex justify-between text-xs">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $prog['nombre'] }}</span>
                        <span class="text-gray-500">{{ $prog['atendidos'] }} / {{ round($prog['meta']) }} ({{ $pct }}%)</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="{{ $color }} h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
