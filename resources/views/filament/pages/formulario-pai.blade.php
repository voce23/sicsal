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

    <div x-data="{ activeTab: 'vacunas' }" class="space-y-4">
        {{-- Tab Navigation --}}
        <div class="overflow-x-auto">
            <nav class="flex gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
                @foreach ([
                    'vacunas' => '1. Vacunas',
                    'micronutrientes' => '2. Micronutrientes',
                    'crecimiento' => '3. Crecimiento',
                ] as $key => $label)
                    <button x-on:click="activeTab = '{{ $key }}'"
                            :class="activeTab === '{{ $key }}' ? 'bg-white text-primary-600 shadow dark:bg-gray-700 dark:text-primary-400' : 'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
                            class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Tab 1: Vacunas --}}
        <div x-show="activeTab === 'vacunas'" x-cloak class="space-y-6">

            {{-- Sección 1: Menores de 5 años --}}
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Vacunaciones en menores de 5 años</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-600">
                                <th rowspan="3" class="px-2 py-2 text-left align-bottom">Vacuna</th>
                                @foreach (\App\Filament\Pages\FormularioPai::$gruposMenores5 as $geKey => $geLabel)
                                    <th colspan="4" class="px-1 py-1 text-center text-xs">{{ $geLabel }}</th>
                                @endforeach
                            </tr>
                            <tr class="border-b dark:border-gray-600">
                                @foreach (\App\Filament\Pages\FormularioPai::$gruposMenores5 as $geKey => $geLabel)
                                    <th colspan="2" class="px-1 py-1 text-center text-xs text-gray-500">Dentro</th>
                                    <th colspan="2" class="px-1 py-1 text-center text-xs text-gray-500">Fuera</th>
                                @endforeach
                            </tr>
                            <tr class="border-b text-xs text-gray-400 dark:border-gray-600">
                                @foreach (\App\Filament\Pages\FormularioPai::$gruposMenores5 as $geKey => $geLabel)
                                    <th class="px-1 py-1">M</th><th class="px-1 py-1">F</th>
                                    <th class="px-1 py-1">M</th><th class="px-1 py-1">F</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\App\Filament\Pages\FormularioPai::$vacunasMenores5 as [$tipo, $label])
                                <tr class="border-b dark:border-gray-700">
                                    <td class="whitespace-nowrap px-2 py-1 font-medium">{{ $label }}</td>
                                    @foreach (array_keys(\App\Filament\Pages\FormularioPai::$gruposMenores5) as $ge)
                                        @php $key = "{$tipo}__{$ge}"; @endphp
                                        @foreach (['dentro_m','dentro_f','fuera_m','fuera_f'] as $campo)
                                            <td class="px-0.5 py-1">
                                                <input type="number" min="0"
                                                       wire:model="vacunas.{{ $key }}.{{ $campo }}"
                                                       class="w-12 rounded border px-1 py-1 text-center text-xs dark:border-gray-600 dark:bg-gray-700"
                                                       {{ $mesCerrado ? 'disabled' : '' }}>
                                            </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Sección 2: Otras vacunaciones --}}
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Otras vacunaciones</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-600">
                                <th rowspan="3" class="px-2 py-2 text-left align-bottom">Vacuna</th>
                                @foreach (\App\Filament\Pages\FormularioPai::$gruposOtrasVac as $geKey => $geLabel)
                                    <th colspan="4" class="px-1 py-1 text-center text-xs">{{ $geLabel }}</th>
                                @endforeach
                            </tr>
                            <tr class="border-b dark:border-gray-600">
                                @foreach (\App\Filament\Pages\FormularioPai::$gruposOtrasVac as $geKey => $geLabel)
                                    <th colspan="2" class="px-1 py-1 text-center text-xs text-gray-500">Dentro</th>
                                    <th colspan="2" class="px-1 py-1 text-center text-xs text-gray-500">Fuera</th>
                                @endforeach
                            </tr>
                            <tr class="border-b text-xs text-gray-400 dark:border-gray-600">
                                @foreach (\App\Filament\Pages\FormularioPai::$gruposOtrasVac as $geKey => $geLabel)
                                    <th class="px-1 py-1">M</th><th class="px-1 py-1">F</th>
                                    <th class="px-1 py-1">M</th><th class="px-1 py-1">F</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\App\Filament\Pages\FormularioPai::$vacunasOtras as [$tipo, $label])
                                <tr class="border-b dark:border-gray-700">
                                    <td class="whitespace-nowrap px-2 py-1 font-medium">{{ $label }}</td>
                                    @foreach (array_keys(\App\Filament\Pages\FormularioPai::$gruposOtrasVac) as $ge)
                                        @php $key = "{$tipo}__{$ge}"; @endphp
                                        @foreach (['dentro_m','dentro_f','fuera_m','fuera_f'] as $campo)
                                            <td class="px-0.5 py-1">
                                                <input type="number" min="0"
                                                       wire:model="vacunas.{{ $key }}.{{ $campo }}"
                                                       class="w-12 rounded border px-1 py-1 text-center text-xs dark:border-gray-600 dark:bg-gray-700"
                                                       {{ $mesCerrado ? 'disabled' : '' }}>
                                            </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @unless ($mesCerrado)
                <div class="flex justify-end">
                    <button wire:click="guardarVacunas" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                        Guardar Vacunas
                    </button>
                </div>
            @endunless
        </div>

        {{-- Tab 2: Micronutrientes --}}
        <div x-show="activeTab === 'micronutrientes'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Micronutrientes y Suplementos</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    @php
                        // Orden exacto Form 301 SNIS — columna AE, filas 7-27
                        $labelsMicro = [
                            'hierro_embarazadas_completo' => 'AE7 — Hierro embarazadas (dosis completa)',       // AE7
                            'hierro_puerperas_completo'   => 'AE8 — Hierro puérperas (dosis completa)',         // AE8
                            'hierro_menor_6m'             => 'AE9 — Hierro < 6 meses (desde 4m)',               // AE9
                            'hierro_menor_1'              => 'AE10 — Hierro < 1 año',                           // AE10
                            'hierro_1anio'                => 'AE11 — Hierro 1 año',                             // AE11
                            'hierro_2_5'                  => 'AE12 — Hierro 2 a < 5 años',                      // AE12
                            'vitA_puerpera_unica'         => 'AE13 — Vitamina A puérpera (dosis única)',         // AE13
                            'vitA_menor_1_unica'          => 'AE14 — Vitamina A < 1 año (dosis única)',          // AE14
                            'vitA_1anio_1ra'              => 'AE15 — Vitamina A 1 año (1ra dosis)',              // AE15
                            'vitA_1anio_2da'              => 'AE16 — Vitamina A 1 año (2da dosis)',              // AE16
                            'vitA_2_5_1ra'                => 'AE17 — Vitamina A 2 a < 5 años (1ra dosis)',      // AE17
                            'vitA_2_5_2da'                => 'AE18 — Vitamina A 2 a < 5 años (2da dosis)',      // AE18
                            'zinc_menor_1'                => 'AE19 — Zinc < 1 año (talla baja)',                 // AE19
                            'zinc_1anio'                  => 'AE20 — Zinc 1 año (talla baja)',                   // AE20
                            'nutribebe_menor_1'           => 'AE21 — Nutribebé < 1 año',                        // AE21
                            'nutribebe_1anio'             => 'AE22 — Nutribebé 1 año',                          // AE22
                            'lactancia_inmediata'         => 'AE23 — RN con lactancia materna inmediata',        // AE23
                            'lactancia_exclusiva_6m'      => 'AE24 — 6 meses con lactancia materna exclusiva',  // AE24
                            'carmelo_mayor_60'            => 'AE25 — Carmelo ≥ 60 años',                        // AE25
                            'nutrimama_embarazada'        => 'AE26 — Nutri Mamá embarazadas',                   // AE26
                            'nutrimama_lactancia'         => 'AE27 — Nutri Mamá en lactancia',                  // AE27
                        ];
                    @endphp
                    @foreach ($labelsMicro as $tipo => $label)
                        <div class="flex items-center justify-between rounded border px-3 py-2 dark:border-gray-600">
                            <label class="text-sm">{{ $label }}</label>
                            <input type="number" min="0"
                                   wire:model="micronutrientes.{{ $tipo }}"
                                   class="w-20 rounded border px-2 py-1 text-center text-sm dark:border-gray-600 dark:bg-gray-700"
                                   {{ $mesCerrado ? 'disabled' : '' }}>
                        </div>
                    @endforeach
                </div>
                @unless ($mesCerrado)
                    <div class="mt-4 flex justify-end">
                        <button wire:click="guardarMicronutrientes" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Guardar Micronutrientes
                        </button>
                    </div>
                @endunless
            </div>
        </div>

        {{-- Tab 3: Crecimiento --}}
        <div x-show="activeTab === 'crecimiento'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Control de Crecimiento Infantil</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-600">
                                <th class="px-2 py-2 text-left">Grupo</th>
                                <th colspan="2" class="px-2 py-2 text-center">Nuevos</th>
                                <th colspan="2" class="px-2 py-2 text-center">Repetidos</th>
                            </tr>
                            <tr class="border-b text-xs text-gray-500 dark:border-gray-600">
                                <th></th>
                                <th class="px-2 py-1">M</th><th class="px-2 py-1">F</th>
                                <th class="px-2 py-1">M</th><th class="px-2 py-1">F</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $labelsCrec = [
                                    'menor_1_dentro' => '< 1 año (dentro)', 'menor_1_fuera' => '< 1 año (fuera)',
                                    '1_menor_2_dentro' => '1 - < 2 años (dentro)', '1_menor_2_fuera' => '1 - < 2 años (fuera)',
                                    '2_menor_5_dentro' => '2 - < 5 años (dentro)', '2_menor_5_fuera' => '2 - < 5 años (fuera)',
                                ];
                            @endphp
                            @foreach ($labelsCrec as $ge => $label)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-2 py-1 font-medium">{{ $label }}</td>
                                    @foreach (['nuevos_m','nuevos_f','repetidos_m','repetidos_f'] as $campo)
                                        <td class="px-1 py-1">
                                            <input type="number" min="0"
                                                   wire:model="crecimiento.{{ $ge }}.{{ $campo }}"
                                                   class="w-16 rounded border px-2 py-1 text-center text-sm dark:border-gray-600 dark:bg-gray-700"
                                                   {{ $mesCerrado ? 'disabled' : '' }}>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @unless ($mesCerrado)
                    <div class="mt-4 flex justify-end">
                        <button wire:click="guardarCrecimiento" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Guardar Crecimiento
                        </button>
                    </div>
                @endunless
            </div>
        </div>
    </div>

    @include('filament.pages.partials.justificacion-cero-modal')
</x-filament-panels::page>
