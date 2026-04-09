<x-filament-panels::page>
    @if ($mesCerrado)
        <div class="rounded-lg border border-green-300 bg-green-50 p-4 text-green-800 dark:border-green-700 dark:bg-green-900/20 dark:text-green-300">
            <div class="flex items-center gap-2">
                <x-heroicon-s-lock-closed class="h-5 w-5" />
                <span class="font-semibold">Este mes está cerrado.</span>
                Los datos son de solo lectura.
            </div>
        </div>
    @endif

    <div class="rounded-xl border bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">10 Primeras Causas de Consulta Externa</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Ingrese el diagnóstico (CIE-10 o descripción) y las cantidades por sexo y grupo etáreo. Deje vacío el diagnóstico para omitir esa posición.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs" style="min-width: 900px;">
                <thead>
                    <tr class="border-b dark:border-gray-600">
                        <th class="px-2 py-2 text-left w-6">#</th>
                        <th class="px-2 py-2 text-left" style="min-width:160px">Diagnóstico (CIE-10)</th>
                        @foreach (\App\Filament\Pages\FormularioCausasConsulta::$grupos as $ge => $label)
                            <th class="px-1 py-2 text-center" colspan="2">{{ $label }}</th>
                        @endforeach
                        <th class="px-2 py-2 text-center">Total M</th>
                        <th class="px-2 py-2 text-center">Total F</th>
                        <th class="px-2 py-2 text-center font-bold">Total</th>
                    </tr>
                    <tr class="border-b bg-gray-50 dark:border-gray-600 dark:bg-gray-700/50">
                        <th></th>
                        <th></th>
                        @foreach (\App\Filament\Pages\FormularioCausasConsulta::$grupos as $ge => $label)
                            <th class="px-1 py-1 text-center text-blue-600 dark:text-blue-400 font-normal">M</th>
                            <th class="px-1 py-1 text-center text-pink-600 dark:text-pink-400 font-normal">F</th>
                        @endforeach
                        <th></th><th></th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($causas as $pos => $causa)
                        @php
                            $totalM = collect($causa['grupos'])->sum('m');
                            $totalF = collect($causa['grupos'])->sum('f');
                        @endphp
                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                            <td class="px-2 py-1 text-gray-400 font-medium">{{ $pos }}</td>
                            <td class="px-1 py-1">
                                <input type="text"
                                       wire:model.lazy="causas.{{ $pos }}.diagnostico"
                                       placeholder="Diagnóstico..."
                                       class="w-full rounded border px-2 py-1 text-xs dark:border-gray-600 dark:bg-gray-700 {{ $mesCerrado ? 'bg-gray-100' : '' }}"
                                       {{ $mesCerrado ? 'disabled' : '' }}>
                            </td>
                            @foreach (\App\Filament\Pages\FormularioCausasConsulta::$grupos as $ge => $label)
                                <td class="px-0.5 py-1">
                                    <input type="number" min="0"
                                           wire:model.lazy="causas.{{ $pos }}.grupos.{{ $ge }}.m"
                                           class="w-10 rounded border px-1 py-1 text-center text-xs dark:border-gray-600 dark:bg-gray-700"
                                           {{ $mesCerrado ? 'disabled' : '' }}>
                                </td>
                                <td class="px-0.5 py-1">
                                    <input type="number" min="0"
                                           wire:model.lazy="causas.{{ $pos }}.grupos.{{ $ge }}.f"
                                           class="w-10 rounded border px-1 py-1 text-center text-xs dark:border-gray-600 dark:bg-gray-700"
                                           {{ $mesCerrado ? 'disabled' : '' }}>
                                </td>
                            @endforeach
                            <td class="px-2 py-1 text-center font-medium text-blue-600 dark:text-blue-400">{{ $totalM }}</td>
                            <td class="px-2 py-1 text-center font-medium text-pink-600 dark:text-pink-400">{{ $totalF }}</td>
                            <td class="px-2 py-1 text-center font-bold">{{ $totalM + $totalF }}</td>
                        </tr>
                    @endforeach

                    {{-- Fila de totales generales --}}
                    @php
                        $grandM = 0; $grandF = 0;
                        foreach ($causas as $c) {
                            $grandM += collect($c['grupos'])->sum('m');
                            $grandF += collect($c['grupos'])->sum('f');
                        }
                    @endphp
                    <tr class="border-t-2 bg-gray-50 font-bold dark:border-gray-600 dark:bg-gray-700/50">
                        <td class="px-2 py-2" colspan="2">TOTALES</td>
                        @foreach (\App\Filament\Pages\FormularioCausasConsulta::$grupos as $ge => $label)
                            @php
                                $geM = collect($causas)->sum(fn($c) => $c['grupos'][$ge]['m'] ?? 0);
                                $geF = collect($causas)->sum(fn($c) => $c['grupos'][$ge]['f'] ?? 0);
                            @endphp
                            <td class="px-1 py-2 text-center text-blue-600 dark:text-blue-400">{{ $geM }}</td>
                            <td class="px-1 py-2 text-center text-pink-600 dark:text-pink-400">{{ $geF }}</td>
                        @endforeach
                        <td class="px-2 py-2 text-center text-blue-600 dark:text-blue-400">{{ $grandM }}</td>
                        <td class="px-2 py-2 text-center text-pink-600 dark:text-pink-400">{{ $grandF }}</td>
                        <td class="px-2 py-2 text-center">{{ $grandM + $grandF }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @unless ($mesCerrado)
            <div class="mt-4 flex justify-end">
                <button wire:click="guardar"
                        wire:loading.attr="disabled"
                        class="rounded-lg bg-primary-600 px-5 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="guardar">Guardar Causas de Consulta Externa</span>
                    <span wire:loading wire:target="guardar">Guardando...</span>
                </button>
            </div>
        @endunless
    </div>

    @include('filament.pages.partials.justificacion-cero-modal')
</x-filament-panels::page>
