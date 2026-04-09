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

    <div x-data="{ activeTab: 'prenatales' }" class="space-y-4">
        {{-- Tab Navigation --}}
        <div class="overflow-x-auto">
            <nav class="flex gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
                @foreach ([
                    'prenatales' => '1. Prenatales',
                    'partos' => '2. Partos y Puerperio',
                    'anticoncepcion' => '3. Anticoncepción',
                    'cancer' => '4. Prev. Cáncer',
                    'ile' => '5. ILE',
                ] as $key => $label)
                    <button x-on:click="activeTab = '{{ $key }}'"
                            :class="activeTab === '{{ $key }}' ? 'bg-white text-primary-600 shadow dark:bg-gray-700 dark:text-primary-400' : 'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
                            class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Tab 1: Prenatales --}}
        <div x-show="activeTab === 'prenatales'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Controles Prenatales</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-600">
                                <th class="px-2 py-2 text-left">Tipo Control</th>
                                <th class="px-2 py-2 text-left">Grupo Etáreo</th>
                                <th class="px-2 py-2 text-center">Dentro</th>
                                <th class="px-2 py-2 text-center">Fuera</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $labelsTC = ['nueva_1er_trim' => 'Nueva 1er Trim.', 'nueva_2do_trim' => 'Nueva 2do Trim.', 'nueva_3er_trim' => 'Nueva 3er Trim.', 'repetida' => 'Repetida', 'con_4to_control' => 'Con 4to control'];
                                $labelsGE = ['menor_10' => '< 10', '10_14' => '10-14', '15_19' => '15-19', '20_34' => '20-34', '35_49' => '35-49', '50_mas' => '≥ 50'];
                            @endphp
                            @foreach ($labelsTC as $tc => $tcLabel)
                                @foreach ($labelsGE as $ge => $geLabel)
                                    <tr class="border-b dark:border-gray-700">
                                        @if ($loop->first)
                                            <td class="px-2 py-1 font-medium" rowspan="{{ count($labelsGE) }}">{{ $tcLabel }}</td>
                                        @endif
                                        <td class="px-2 py-1">{{ $geLabel }}</td>
                                        <td class="px-1 py-1">
                                            <input type="number" min="0"
                                                   wire:model="prenatales.{{ $tc }}__{{ $ge }}.dentro"
                                                   class="w-16 rounded border px-2 py-1 text-center text-sm dark:border-gray-600 dark:bg-gray-700"
                                                   {{ $mesCerrado ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-1 py-1">
                                            <input type="number" min="0"
                                                   wire:model="prenatales.{{ $tc }}__{{ $ge }}.fuera"
                                                   class="w-16 rounded border px-2 py-1 text-center text-sm dark:border-gray-600 dark:bg-gray-700"
                                                   {{ $mesCerrado ? 'disabled' : '' }}>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @unless ($mesCerrado)
                    <div class="mt-4 flex justify-end">
                        <button wire:click="guardarPrenatales" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Guardar Prenatales
                        </button>
                    </div>
                @endunless
            </div>
        </div>

        {{-- Tab 2: Partos y Puerperio --}}
        <div x-show="activeTab === 'partos'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Partos</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="px-2 py-2 text-left">Tipo de parto</th>
                                @foreach (\App\Filament\Pages\FormularioMaterna::$gruposParto as $ge => $labelGe)
                                    <th class="px-2 py-2 text-center">{{ $labelGe }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\App\Filament\Pages\FormularioMaterna::$partosConfig as [$tipo, $lugar, $atendido, $label])
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-2 py-2 text-sm">{{ $label }}</td>
                                    @foreach (array_keys(\App\Filament\Pages\FormularioMaterna::$gruposParto) as $ge)
                                        <td class="px-2 py-1 text-center">
                                            <input type="number" min="0"
                                                   wire:model="partos.{{ $tipo }}__{{ $lugar }}__{{ $atendido }}__{{ $ge }}"
                                                   class="w-16 rounded border px-1 py-1 text-center text-sm dark:border-gray-600 dark:bg-gray-700"
                                                   {{ $mesCerrado ? 'disabled' : '' }}>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h3 class="mb-4 mt-6 text-lg font-semibold">Puerperio</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    @php
                        $labelsPuerperio = ['48h' => 'Control 48 horas post parto (F54)', '7dias' => 'Control 7 días', '28dias' => 'Control 28 días', '42dias' => 'Control 42 días (Primer Control Post Parto F61)'];
                    @endphp
                    @foreach ($labelsPuerperio as $tc => $label)
                        <div class="flex items-center justify-between rounded border px-3 py-2 dark:border-gray-600">
                            <label class="text-sm">{{ $label }}</label>
                            <input type="number" min="0"
                                   wire:model="puerperio.{{ $tc }}"
                                   class="w-20 rounded border px-2 py-1 text-center text-sm dark:border-gray-600 dark:bg-gray-700"
                                   {{ $mesCerrado ? 'disabled' : '' }}>
                        </div>
                    @endforeach
                </div>

                <h3 class="mb-4 mt-6 text-lg font-semibold">Recién Nacidos</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    @php
                        $labelsRN = [
                            'nacidos_vivos_servicio' => 'Nacidos vivos (total)',                // F113
                            'nacidos_vivos_domicilio' => 'Nacidos vivos en domicilio',
                            'nacidos_vivos_4cpn' => 'Nacidos vivos con 4 CPN',                  // F114
                            'nacidos_vivos_peso_menor_2500' => 'Nacidos vivos < 2500g',         // F115
                            'nacidos_muertos' => 'Nacidos muertos',                             // F121
                            'rn_lactancia_inmediata' => 'RN con apego precoz',                  // F129
                            'rn_alojamiento_conjunto' => 'RN alojamiento conjunto',             // F131
                            'rn_corte_tardio_cordon' => 'RN corte tardío de cordón umbilical', // F130
                            'rn_malformacion_congenita' => 'RN malformación congénita',         // F128
                            'rn_control_48h' => 'RN control 48 horas',                          // F132
                        ];
                    @endphp
                    @foreach ($labelsRN as $ind => $label)
                        <div class="flex items-center justify-between rounded border px-3 py-2 dark:border-gray-600">
                            <label class="text-sm">{{ $label }}</label>
                            <input type="number" min="0"
                                   wire:model="recienNacidos.{{ $ind }}"
                                   class="w-20 rounded border px-2 py-1 text-center text-sm dark:border-gray-600 dark:bg-gray-700"
                                   {{ $mesCerrado ? 'disabled' : '' }}>
                        </div>
                    @endforeach
                </div>
                @unless ($mesCerrado)
                    <div class="mt-4 flex justify-end">
                        <button wire:click="guardarPartosYPuerperio" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Guardar Partos y Puerperio
                        </button>
                    </div>
                @endunless
            </div>
        </div>

        {{-- Tab 3: Anticoncepción --}}
        <div x-show="activeTab === 'anticoncepcion'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Anticoncepción</h3>
                @php
                    $metodos = \App\Filament\Pages\FormularioMaterna::$metodosAnticoncepcion;
                    $gruposAC = \App\Filament\Pages\FormularioMaterna::$gruposAnticoncepcion;
                @endphp
                @foreach ($metodos as $metodoKey => $metodoLabel)
                    <div class="mb-6">
                        <h4 class="mb-2 font-semibold text-sm text-gray-700 dark:text-gray-300">{{ $metodoLabel }}</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b dark:border-gray-600">
                                        <th class="px-2 py-1 text-left text-xs">Tipo</th>
                                        @foreach ($gruposAC as $geKey => $geLabel)
                                            <th class="px-2 py-1 text-center text-xs">{{ $geLabel }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (['nueva' => 'Nueva', 'continua' => 'Continua'] as $tipoKey => $tipoLabel)
                                        <tr class="border-b dark:border-gray-700">
                                            <td class="px-2 py-1 font-medium text-xs">{{ $tipoLabel }}</td>
                                            @foreach ($gruposAC as $geKey => $geLabel)
                                                <td class="px-1 py-1">
                                                    <input type="number" min="0"
                                                           wire:model="anticoncepcion.{{ $metodoKey }}__{{ $tipoKey }}__{{ $geKey }}"
                                                           class="w-14 rounded border px-1 py-1 text-center text-xs dark:border-gray-600 dark:bg-gray-700"
                                                           {{ $mesCerrado ? 'disabled' : '' }}>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
                @unless ($mesCerrado)
                    <div class="mt-4 flex justify-end">
                        <button wire:click="guardarAnticoncepcion" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Guardar Anticoncepción
                        </button>
                    </div>
                @endunless
            </div>
        </div>

        {{-- Tab 4: Prev. Cáncer --}}
        <div x-show="activeTab === 'cancer'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Prevención de Cáncer</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (\App\Filament\Pages\FormularioMaterna::$indicadoresCancer as $ind => $label)
                        <div class="flex items-center justify-between rounded border px-3 py-2 dark:border-gray-600">
                            <label class="text-sm">{{ $label }}</label>
                            <input type="number" min="0"
                                   wire:model="cancerPrevencion.{{ $ind }}"
                                   class="w-20 rounded border px-2 py-1 text-center text-sm dark:border-gray-600 dark:bg-gray-700"
                                   {{ $mesCerrado ? 'disabled' : '' }}>
                        </div>
                    @endforeach
                </div>
                @unless ($mesCerrado)
                    <div class="mt-4 flex justify-end">
                        <button wire:click="guardarCancerPrevencion" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Guardar Prev. Cáncer
                        </button>
                    </div>
                @endunless
            </div>
        </div>

        {{-- Tab 5: ILE --}}
        <div x-show="activeTab === 'ile'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-1 text-lg font-semibold">Interrupción Legal del Embarazo (ILE)</h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    Form 301 filas 63-65. Solo se registra cuando hay atenciones de ILE por causal Violencia Sexual.
                </p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @php
                        $labelsIle = [
                            'ile_1er_trimestre' => 'ILE 1er trimestre (Violencia Sexual)',  // F63
                            'ile_2do_trimestre' => 'ILE 2do trimestre (Violencia Sexual)',  // F64
                            'ile_3er_trimestre' => 'ILE 3er trimestre (Violencia Sexual)',  // F65
                        ];
                    @endphp
                    @foreach ($labelsIle as $ind => $label)
                        <div class="flex items-center justify-between rounded border px-3 py-2 dark:border-gray-600">
                            <label class="text-sm">{{ $label }}</label>
                            <input type="number" min="0"
                                   wire:model="ile.{{ $ind }}"
                                   class="w-20 rounded border px-2 py-1 text-center text-sm dark:border-gray-600 dark:bg-gray-700"
                                   {{ $mesCerrado ? 'disabled' : '' }}>
                        </div>
                    @endforeach
                </div>
                @unless ($mesCerrado)
                    <div class="mt-4 flex justify-end">
                        <button wire:click="guardarIle"
                                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Guardar ILE
                        </button>
                    </div>
                @endunless
            </div>
        </div>
    </div>

    @include('filament.pages.partials.justificacion-cero-modal')
</x-filament-panels::page>
