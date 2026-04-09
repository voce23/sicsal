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

    <div x-data="{ activeTab: 'consulta' }" class="space-y-4">
        {{-- Tab Navigation --}}
        <div class="overflow-x-auto">
            <nav class="flex gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
                @foreach ([
                    'consulta' => '1. Consulta Externa',
                    'referencias' => '2. Referencias',
                    'odontologia' => '3. Odontología',
                    'enfermeria' => '4. Enfermería',
                    'internaciones' => '5. Internaciones',
                    'actividades' => '6. Actividades',
                    'observaciones' => '7. Observaciones',
                    'causas' => '8. Causas Consulta',
                ] as $key => $label)
                    <button x-on:click="activeTab = '{{ $key }}'"
                            :class="activeTab === '{{ $key }}' ? 'bg-white text-primary-600 shadow dark:bg-gray-700 dark:text-primary-400' : 'text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
                            class="whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Tab 1: Consulta Externa --}}
        <div x-show="activeTab === 'consulta'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Consulta Externa</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-600">
                                <th class="px-2 py-2 text-left">Grupo Etáreo</th>
                                <th colspan="2" class="px-2 py-2 text-center">Primera</th>
                                <th colspan="2" class="px-2 py-2 text-center">Nueva</th>
                                <th colspan="2" class="px-2 py-2 text-center">Repetida</th>
                            </tr>
                            <tr class="border-b text-xs text-gray-500 dark:border-gray-600">
                                <th></th>
                                <th class="px-2 py-1">M</th><th class="px-2 py-1">F</th>
                                <th class="px-2 py-1">M</th><th class="px-2 py-1">F</th>
                                <th class="px-2 py-1">M</th><th class="px-2 py-1">F</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $labelsConsulta = [
                                    'menor_6m' => '< 6 meses', '6m_menor_1' => '6m - < 1 año', '1_4' => '1 - 4 años',
                                    '5_9' => '5 - 9 años', '10_14' => '10 - 14 años', '15_19' => '15 - 19 años',
                                    '20_39' => '20 - 39 años', '40_49' => '40 - 49 años', '50_59' => '50 - 59 años',
                                    'mayor_60' => '≥ 60 años',
                                ];
                            @endphp
                            @foreach ($labelsConsulta as $grupo => $label)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-2 py-1 font-medium">{{ $label }}</td>
                                    @foreach (['primera_m','primera_f','nueva_m','nueva_f','repetida_m','repetida_f'] as $campo)
                                        <td class="px-1 py-1">
                                            <input type="number" min="0"
                                                   wire:model="consultaExterna.{{ $grupo }}.{{ $campo }}"
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
                        <button wire:click="guardarConsultaExterna" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Guardar Consulta Externa
                        </button>
                    </div>
                @endunless
            </div>
        </div>

        {{-- Tab 2: Referencias --}}
        <div x-show="activeTab === 'referencias'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Referencias y Contrarreferencias</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-600">
                                <th class="px-2 py-2 text-left">Tipo</th>
                                <th class="px-2 py-2 text-center">Masculino</th>
                                <th class="px-2 py-2 text-center">Femenino</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (\App\Filament\Pages\FormularioServicios::$tiposReferencia as $tipo => $label)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-2 py-1 font-medium">{{ $label }}</td>
                                    <td class="px-1 py-1">
                                        <input type="number" min="0"
                                               wire:model="referencias.{{ $tipo }}.masculino"
                                               class="w-20 rounded border px-2 py-1 text-center text-sm dark:border-gray-600 dark:bg-gray-700"
                                               {{ $mesCerrado ? 'disabled' : '' }}>
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="number" min="0"
                                               wire:model="referencias.{{ $tipo }}.femenino"
                                               class="w-20 rounded border px-2 py-1 text-center text-sm dark:border-gray-600 dark:bg-gray-700"
                                               {{ $mesCerrado ? 'disabled' : '' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @unless ($mesCerrado)
                    <div class="mt-4 flex justify-end">
                        <button wire:click="guardarReferencias" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Guardar Referencias
                        </button>
                    </div>
                @endunless
            </div>
        </div>

        {{-- Tab 3: Odontología --}}
        <div x-show="activeTab === 'odontologia'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Atención Odontológica</h3>
                @php
                    $procedimientos = \App\Filament\Pages\FormularioServicios::$procedimientosOdonto;
                    $gruposOd = \App\Filament\Pages\FormularioServicios::$gruposOdonto;
                @endphp
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-600">
                                <th class="px-2 py-2 text-left">Procedimiento</th>
                                @foreach ($gruposOd as $geKey => $geLabel)
                                    <th colspan="2" class="px-1 py-1 text-center text-xs">{{ $geLabel }}</th>
                                @endforeach
                            </tr>
                            <tr class="border-b text-xs text-gray-500 dark:border-gray-600">
                                <th></th>
                                @foreach ($gruposOd as $geKey => $geLabel)
                                    <th class="px-1 py-1">M</th>
                                    <th class="px-1 py-1">F</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($procedimientos as $procKey => $procLabel)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-2 py-1 font-medium text-xs whitespace-nowrap">{{ $procLabel }}</td>
                                    @foreach ($gruposOd as $geKey => $geLabel)
                                        @php $key = "{$procKey}__{$geKey}"; @endphp
                                        <td class="px-1 py-1">
                                            <input type="number" min="0"
                                                   wire:model="odontologia.{{ $key }}.masculino"
                                                   class="w-12 rounded border px-1 py-1 text-center text-xs dark:border-gray-600 dark:bg-gray-700"
                                                   {{ $mesCerrado ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-1 py-1">
                                            <input type="number" min="0"
                                                   wire:model="odontologia.{{ $key }}.femenino"
                                                   class="w-12 rounded border px-1 py-1 text-center text-xs dark:border-gray-600 dark:bg-gray-700"
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
                        <button wire:click="guardarOdontologia" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Guardar Odontología
                        </button>
                    </div>
                @endunless
            </div>
        </div>

        {{-- Tab 4: Enfermería --}}
        <div x-show="activeTab === 'enfermeria'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Enfermería y Cirugías</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (\App\Filament\Pages\FormularioServicios::$tiposEnfermeria as $tipo => $label)
                        <div class="flex items-center justify-between rounded border px-3 py-2 dark:border-gray-600">
                            <label class="text-sm">{{ $label }}</label>
                            <input type="number" min="0"
                                   wire:model="enfermeria.{{ $tipo }}"
                                   class="w-20 rounded border px-2 py-1 text-center text-sm dark:border-gray-600 dark:bg-gray-700"
                                   {{ $mesCerrado ? 'disabled' : '' }}>
                        </div>
                    @endforeach
                </div>
                @unless ($mesCerrado)
                    <div class="mt-4 flex justify-end">
                        <button wire:click="guardarEnfermeria" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Guardar Enfermería
                        </button>
                    </div>
                @endunless
            </div>
        </div>

        {{-- Tab 5: Internaciones --}}
        <div x-show="activeTab === 'internaciones'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Internaciones</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (\App\Filament\Pages\FormularioServicios::$indicadoresInternacion as $ind => $label)
                        <div class="flex items-center justify-between rounded border px-3 py-2 dark:border-gray-600">
                            <label class="text-sm">{{ $label }}</label>
                            <input type="number" min="0"
                                   wire:model="internaciones.{{ $ind }}"
                                   class="w-20 rounded border px-2 py-1 text-center text-sm dark:border-gray-600 dark:bg-gray-700"
                                   {{ $mesCerrado ? 'disabled' : '' }}>
                        </div>
                    @endforeach
                </div>
                @unless ($mesCerrado)
                    <div class="mt-4 flex justify-end">
                        <button wire:click="guardarInternaciones" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Guardar Internaciones
                        </button>
                    </div>
                @endunless
            </div>
        </div>

        {{-- Tab 6: Actividades --}}
        <div x-show="activeTab === 'actividades'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Actividades con la Comunidad</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    @php
                        $labelsAct = [
                            'actividades_con_comunidad' => 'Actividades con la comunidad',
                            'cai_establecimiento' => 'CAI en establecimiento',
                            'comunidades_en_cai' => 'Comunidades en CAI',
                            'familias_nuevas_carpetizadas' => 'Familias nuevas carpetizadas',
                            'familias_seguimiento' => 'Familias en seguimiento',
                            'visitas_primeras' => 'Visitas primeras',
                            'visitas_segundas' => 'Visitas segundas',
                            'visitas_terceras' => 'Visitas terceras',
                            'reuniones_autoridades' => 'Reuniones con autoridades',
                            'reuniones_comites_salud' => 'Reuniones comités de salud',
                            'actividades_educativas_salud' => 'Actividades educativas en salud',
                            // PcD atendidas va en la pestaña de Referencias (Form 301)
                        ];
                    @endphp
                    @foreach ($labelsAct as $ta => $label)
                        <div class="flex items-center justify-between rounded border px-3 py-2 dark:border-gray-600">
                            <label class="text-sm">{{ $label }}</label>
                            <input type="number" min="0"
                                   wire:model="actividades.{{ $ta }}"
                                   class="w-20 rounded border px-2 py-1 text-center text-sm dark:border-gray-600 dark:bg-gray-700"
                                   {{ $mesCerrado ? 'disabled' : '' }}>
                        </div>
                    @endforeach
                </div>
                @unless ($mesCerrado)
                    <div class="mt-4 flex justify-end">
                        <button wire:click="guardarActividades" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Guardar Actividades
                        </button>
                    </div>
                @endunless
            </div>
        </div>

        {{-- Tab 7: Observaciones --}}
        <div x-show="activeTab === 'observaciones'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Observaciones del mes</h3>
                <p class="mb-3 text-sm text-gray-500">Estas notas aparecerán en el informe CAI.</p>
                <textarea wire:model="observaciones"
                          rows="6" maxlength="1000"
                          class="w-full rounded-lg border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700"
                          placeholder="Escriba las observaciones del mes..."
                          {{ $mesCerrado ? 'disabled' : '' }}></textarea>
                <div class="mt-1 text-right text-xs text-gray-400">
                    {{ strlen($observaciones) }}/1000
                </div>
                @unless ($mesCerrado)
                    <div class="mt-4 flex justify-end">
                        <button wire:click="guardarObservaciones" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Guardar Observaciones
                        </button>
                    </div>
                @endunless
            </div>
        </div>
        {{-- Tab 8: Causas de Consulta Externa --}}
        <div x-show="activeTab === 'causas'" x-cloak class="space-y-4">
            <div class="rounded-xl border bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-1 text-lg font-semibold">10 Principales Causas de Consulta Externa</h3>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    Ingrese el diagnóstico y la cantidad de atenciones por grupo etáreo y sexo (M = Masculino, F = Femenino).
                </p>
                @php
                    $gruposLabel = [
                        'menor_6m'   => '&lt;6m',
                        '6m_menor_1' => '6m-1a',
                        '1_4'        => '1-4a',
                        '5_9'        => '5-9a',
                        '10_14'      => '10-14a',
                        '15_19'      => '15-19a',
                        '20_39'      => '20-39a',
                        '40_49'      => '40-49a',
                        '50_59'      => '50-59a',
                        'mayor_60'   => '≥60a',
                    ];
                @endphp
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b bg-rose-700 text-white dark:border-gray-600">
                                <th class="px-2 py-2 text-center">N°</th>
                                <th class="px-3 py-2 text-left" style="min-width:180px">Diagnóstico (CIE-10)</th>
                                @foreach ($gruposLabel as $gk => $gl)
                                    <th colspan="2" class="px-1 py-1 text-center font-semibold">{!! $gl !!}</th>
                                @endforeach
                                <th class="px-2 py-2 text-center">Tot.M</th>
                                <th class="px-2 py-2 text-center">Tot.F</th>
                                <th class="px-2 py-2 text-center">Total</th>
                            </tr>
                            <tr class="border-b bg-rose-600 text-white text-center dark:border-gray-600">
                                <th></th><th></th>
                                @foreach ($gruposLabel as $gk => $gl)
                                    <th class="px-1 py-1" style="min-width:28px">M</th>
                                    <th class="px-1 py-1" style="min-width:28px">F</th>
                                @endforeach
                                <th colspan="3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($pos = 1; $pos <= 10; $pos++)
                                @php
                                    $causa = $causasConsulta[$pos] ?? ['diagnostico'=>'','grupos'=>[]];
                                    $totM = collect($causa['grupos'] ?? [])->sum('m');
                                    $totF = collect($causa['grupos'] ?? [])->sum('f');
                                @endphp
                                <tr class="border-b {{ $pos % 2 === 0 ? 'bg-gray-50 dark:bg-gray-900' : 'bg-white dark:bg-gray-800' }}">
                                    <td class="px-2 py-1 text-center font-bold text-rose-600">{{ $pos }}</td>
                                    <td class="px-1 py-1">
                                        <input type="text"
                                               wire:model="causasConsulta.{{ $pos }}.diagnostico"
                                               placeholder="Diagnóstico {{ $pos }}…"
                                               class="w-full rounded border px-2 py-1 text-xs dark:border-gray-600 dark:bg-gray-700"
                                               {{ $mesCerrado ? 'disabled' : '' }}>
                                    </td>
                                    @foreach (array_keys($gruposLabel) as $g)
                                        <td class="px-0.5 py-1">
                                            <input type="number" min="0"
                                                   wire:model="causasConsulta.{{ $pos }}.grupos.{{ $g }}.m"
                                                   class="w-10 rounded border px-1 py-1 text-center text-xs dark:border-gray-600 dark:bg-gray-700"
                                                   {{ $mesCerrado ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-0.5 py-1">
                                            <input type="number" min="0"
                                                   wire:model="causasConsulta.{{ $pos }}.grupos.{{ $g }}.f"
                                                   class="w-10 rounded border px-1 py-1 text-center text-xs dark:border-gray-600 dark:bg-gray-700"
                                                   {{ $mesCerrado ? 'disabled' : '' }}>
                                        </td>
                                    @endforeach
                                    <td class="px-2 py-1 text-center font-semibold text-blue-700">{{ $totM }}</td>
                                    <td class="px-2 py-1 text-center font-semibold text-pink-700">{{ $totF }}</td>
                                    <td class="px-2 py-1 text-center font-bold">{{ $totM + $totF }}</td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

                @unless ($mesCerrado)
                    <div class="mt-4 flex justify-end">
                        <button wire:click="guardarCausasConsulta"
                                class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">
                            Guardar Causas de Consulta
                        </button>
                    </div>
                @endunless
            </div>
        </div>

    </div>
</x-filament-panels::page>
