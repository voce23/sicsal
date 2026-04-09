@php $datos = $this->datos; @endphp

<div>
    {{-- Header --}}
    <section class="border-b border-gray-200 bg-gradient-to-r from-teal-700 to-cyan-600 text-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-extrabold sm:text-3xl">Generador de Informe CAI</h1>
                    <p class="mt-1 text-teal-100">{{ $this->nombrePeriodo }}</p>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6">
        {{-- Selectores --}}
        <div class="flex flex-wrap items-end gap-4">
            @if (count($this->centros) > 0)
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Centro de Salud</label>
                    <select wire:model.live="centroSaludId"
                        class="rounded-lg border-gray-300 text-sm shadow-sm">
                        <option value="0">— Seleccionar —</option>
                        @foreach ($this->centros as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Año</label>
                <select wire:model.live="anio" class="rounded-lg border-gray-300 text-sm shadow-sm">
                    @for ($y = date('Y'); $y >= date('Y') - 3; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Período</label>
                <div class="flex gap-2">
                    @foreach ([
                        'cai1' => 'CAI 1 (Ene–Abr)',
                        'cai2' => 'CAI 2 (Ene–Ago)',
                        'gestion' => 'Cierre Gestión',
                    ] as $val => $label)
                        <button
                            wire:click="$set('periodo', '{{ $val }}')"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition
                                {{ $periodo === $val ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        @if ($datos)
            {{-- Estado de meses --}}
            <div class="flex flex-wrap gap-2">
                @foreach ($datos['meses_cerrados'] as $mc)
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium
                        {{ $mc['cerrado'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        @if ($mc['cerrado'])
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
                        @else
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
                        @endif
                        {{ $mc['mes'] }}
                    </span>
                @endforeach
            </div>

            {{-- Sección 1: Contexto de Migración --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-bold text-gray-900">1. Contexto de Migración</h2>
                @php $mig = $datos['migracion']; @endphp
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-lg bg-blue-50 p-3">
                        <p class="text-2xl font-bold text-blue-700">{{ number_format($mig['total_padron']) }}</p>
                        <p class="text-xs text-gray-600">Padrón total</p>
                    </div>
                    <div class="rounded-lg bg-green-50 p-3">
                        <p class="text-2xl font-bold text-green-700">{{ number_format($mig['residentes']) }}</p>
                        <p class="text-xs text-gray-600">Residentes activos</p>
                    </div>
                    <div class="rounded-lg bg-yellow-50 p-3">
                        <p class="text-2xl font-bold text-yellow-700">{{ number_format($mig['migrantes']) }}</p>
                        <p class="text-xs text-gray-600">Migrantes ({{ $mig['pct_migrantes'] }}%)</p>
                    </div>
                    <div class="rounded-lg bg-purple-50 p-3">
                        <p class="text-2xl font-bold text-purple-700">{{ number_format($mig['mef_activas']) }}</p>
                        <p class="text-xs text-gray-600">MEF activas</p>
                    </div>
                </div>
                <div class="mt-3 text-sm text-gray-600">
                    MEF migradas: <strong>{{ $mig['mef_migradas'] }}</strong> ({{ $mig['pct_mef_migradas'] }}% del total MEF)
                    · Hombres migrados: <strong>{{ $mig['hombres_migrados'] }}</strong>
                </div>
            </div>

            {{-- Sección 2: Censo Poblacional --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-bold text-gray-900">2. Censo Poblacional</h2>
                @php $censo = $datos['censo']; @endphp

                <h4 class="mb-2 text-sm font-semibold text-gray-700">Población por Comunidad</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="px-3 py-2">Comunidad</th>
                                <th class="px-3 py-2 text-center">Distancia</th>
                                <th class="px-3 py-2 text-center">Total</th>
                                <th class="px-3 py-2 text-center">Hombres</th>
                                <th class="px-3 py-2 text-center">Mujeres</th>
                                <th class="px-3 py-2 text-center">&lt; 5 años</th>
                                <th class="px-3 py-2 text-center">Migrantes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($censo['comunidades'] as $com)
                                <tr class="border-b">
                                    <td class="px-3 py-2 font-medium">{{ $com['nombre'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $com['distancia_km'] !== null ? $com['distancia_km'] . ' km' : '—' }}</td>
                                    <td class="px-3 py-2 text-center font-semibold">{{ $com['total'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $com['hombres'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $com['mujeres'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $com['menor_5'] }}</td>
                                    <td class="px-3 py-2 text-center text-yellow-600">{{ $com['migrantes'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h4 class="mb-2 mt-4 text-sm font-semibold text-gray-700">Pirámide INE vs. Real</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="px-3 py-2">Grupo</th>
                                <th class="px-3 py-2 text-center">INE M</th>
                                <th class="px-3 py-2 text-center">INE F</th>
                                <th class="px-3 py-2 text-center">Real M</th>
                                <th class="px-3 py-2 text-center">Real F</th>
                                <th class="px-3 py-2 text-center">Dif.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($censo['piramide'] as $g)
                                @php $dif = ($g['real_m'] + $g['real_f']) - ($g['ine_m'] + $g['ine_f']); @endphp
                                <tr class="border-b">
                                    <td class="px-3 py-2 font-medium">{{ $g['label'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $g['ine_m'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $g['ine_f'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $g['real_m'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $g['real_f'] }}</td>
                                    <td class="px-3 py-2 text-center {{ $dif >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                        {{ $dif >= 0 ? '+' : '' }}{{ $dif }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Sección 3: Prestaciones Acumuladas --}}
            <div x-data="{ open: false }" class="rounded-xl border border-gray-200 bg-white p-6">
                <button @click="open = !open" class="flex w-full items-center justify-between text-left">
                    <h2 class="text-lg font-bold text-gray-900">3. Prestaciones Acumuladas</h2>
                    <svg :class="{ 'rotate-180': open }" class="size-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open" x-collapse class="mt-4 space-y-4">
                    @php $prest = $datos['prestaciones']; @endphp

                    <h4 class="text-sm font-semibold text-gray-700">Vacunas</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="px-3 py-2">Vacuna</th>
                                    <th class="px-3 py-2 text-center">Grupo</th>
                                    <th class="px-3 py-2 text-center">D. M</th>
                                    <th class="px-3 py-2 text-center">D. F</th>
                                    <th class="px-3 py-2 text-center">F. M</th>
                                    <th class="px-3 py-2 text-center">F. F</th>
                                    <th class="px-3 py-2 text-center font-semibold">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($prest['vacunas'] as $v)
                                    <tr class="border-b">
                                        <td class="px-3 py-2">{{ $v['tipo_vacuna'] }}</td>
                                        <td class="px-3 py-2 text-center text-xs">{{ $v['grupo_etareo'] }}</td>
                                        <td class="px-3 py-2 text-center">{{ $v['dentro_m'] }}</td>
                                        <td class="px-3 py-2 text-center">{{ $v['dentro_f'] }}</td>
                                        <td class="px-3 py-2 text-center">{{ $v['fuera_m'] }}</td>
                                        <td class="px-3 py-2 text-center">{{ $v['fuera_f'] }}</td>
                                        <td class="px-3 py-2 text-center font-semibold">{{ $v['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-700">Micronutrientes</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="border-b text-left"><th class="px-3 py-2">Tipo</th><th class="px-3 py-2 text-center">Total</th></tr></thead>
                            <tbody>
                                @foreach ($prest['micronutrientes'] as $tipo => $total)
                                    <tr class="border-b"><td class="px-3 py-2">{{ str_replace('_', ' ', ucfirst($tipo)) }}</td><td class="px-3 py-2 text-center font-semibold">{{ $total }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-700">Control de Crecimiento</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="border-b text-left"><th class="px-3 py-2">Grupo</th><th class="px-3 py-2 text-center">Nuevos M</th><th class="px-3 py-2 text-center">Nuevos F</th><th class="px-3 py-2 text-center">Repet. M</th><th class="px-3 py-2 text-center">Repet. F</th></tr></thead>
                            <tbody>
                                @foreach ($prest['crecimiento'] as $ge => $c)
                                    <tr class="border-b"><td class="px-3 py-2">{{ str_replace('_', ' ', $ge) }}</td><td class="px-3 py-2 text-center">{{ $c['nuevos_m'] ?? 0 }}</td><td class="px-3 py-2 text-center">{{ $c['nuevos_f'] ?? 0 }}</td><td class="px-3 py-2 text-center">{{ $c['repetidos_m'] ?? 0 }}</td><td class="px-3 py-2 text-center">{{ $c['repetidos_f'] ?? 0 }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-700">Recién Nacidos</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="border-b text-left"><th class="px-3 py-2">Indicador</th><th class="px-3 py-2 text-center">Total</th></tr></thead>
                            <tbody>
                                @foreach ($prest['recien_nacidos'] as $ind => $total)
                                    <tr class="border-b"><td class="px-3 py-2">{{ str_replace('_', ' ', ucfirst($ind)) }}</td><td class="px-3 py-2 text-center font-semibold">{{ $total }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <h4 class="text-sm font-semibold text-gray-700">Puerperio</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead><tr class="border-b text-left"><th class="px-3 py-2">Control</th><th class="px-3 py-2 text-center">Total</th></tr></thead>
                            <tbody>
                                @foreach ($prest['puerperio'] as $tc => $total)
                                    <tr class="border-b"><td class="px-3 py-2">{{ $tc }}</td><td class="px-3 py-2 text-center font-semibold">{{ $total }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Sección 4: Cobertura de Programas --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-bold text-gray-900">4. Cobertura de Programas</h2>
                @php $cobertura = $datos['cobertura']; @endphp
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="px-3 py-2">Programa</th>
                                <th class="px-3 py-2 text-center">Meta INE</th>
                                <th class="px-3 py-2 text-center">Pob. Real</th>
                                <th class="px-3 py-2 text-center">Atendidos</th>
                                <th class="px-3 py-2 text-center">Cob. INE %</th>
                                <th class="px-3 py-2 text-center">Cob. Real %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cobertura as $prog)
                                <tr class="border-b">
                                    <td class="px-3 py-2 font-medium">{{ $prog['nombre'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $prog['meta'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $prog['real'] }}</td>
                                    <td class="px-3 py-2 text-center font-semibold">{{ $prog['atendidos'] }}</td>
                                    <td class="px-3 py-2 text-center {{ $prog['cob_ine'] >= 80 ? 'text-green-600' : ($prog['cob_ine'] >= 50 ? 'text-yellow-600' : 'text-red-500') }}">{{ $prog['cob_ine'] }}%</td>
                                    <td class="px-3 py-2 text-center {{ $prog['cob_real'] >= 80 ? 'text-green-600' : ($prog['cob_real'] >= 50 ? 'text-yellow-600' : 'text-red-500') }}">{{ $prog['cob_real'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h4 class="mb-2 mt-4 text-sm font-semibold text-gray-700">Tasas de Deserción</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="px-3 py-2">Indicador</th>
                                <th class="px-3 py-2 text-center">1ra Dosis</th>
                                <th class="px-3 py-2 text-center">Última Dosis</th>
                                <th class="px-3 py-2 text-center">Tasa Deserción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datos['desercion'] as $d)
                                <tr class="border-b">
                                    <td class="px-3 py-2 font-medium">{{ $d['indicador'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $d['primera'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $d['ultima'] }}</td>
                                    <td class="px-3 py-2 text-center font-semibold {{ $d['tasa'] > 10 ? 'text-red-500' : ($d['tasa'] > 0 ? 'text-yellow-600' : 'text-green-600') }}">{{ $d['tasa'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Sección 5: Ceros Justificados --}}
            <div x-data="{ open: false }" class="rounded-xl border border-gray-200 bg-white p-6">
                <button @click="open = !open" class="flex w-full items-center justify-between text-left">
                    <h2 class="text-lg font-bold text-gray-900">5. Ceros Justificados</h2>
                    <svg :class="{ 'rotate-180': open }" class="size-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open" x-collapse class="mt-4">
                    @if (count($datos['ceros_justificados']) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead><tr class="border-b text-left"><th class="px-3 py-2">Mes</th><th class="px-3 py-2">Indicador</th><th class="px-3 py-2">Motivo</th><th class="px-3 py-2">Detalle</th></tr></thead>
                                <tbody>
                                    @foreach ($datos['ceros_justificados'] as $j)
                                        <tr class="border-b"><td class="px-3 py-2">{{ $j['mes'] }}</td><td class="px-3 py-2">{{ str_replace('_', ' ', $j['indicador']) }}</td><td class="px-3 py-2">{{ ucfirst(str_replace('_', ' ', $j['motivo'])) }}</td><td class="px-3 py-2 text-gray-500">{{ $j['detalle'] ?? '—' }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No hay ceros justificados en este período.</p>
                    @endif
                </div>
            </div>

            {{-- Sección 6: Observaciones Narrativas --}}
            <div x-data="{ open: false }" class="rounded-xl border border-gray-200 bg-white p-6">
                <button @click="open = !open" class="flex w-full items-center justify-between text-left">
                    <h2 class="text-lg font-bold text-gray-900">6. Observaciones Narrativas</h2>
                    <svg :class="{ 'rotate-180': open }" class="size-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open" x-collapse class="mt-4">
                    @if (count($datos['observaciones']) > 0)
                        <div class="space-y-3">
                            @foreach ($datos['observaciones'] as $obs)
                                <div>
                                    <p class="text-sm font-semibold text-gray-700">Mes de {{ $obs['mes'] }}:</p>
                                    <p class="text-sm text-gray-600">{{ $obs['texto'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No hay observaciones narrativas en este período.</p>
                    @endif
                </div>
            </div>

            {{-- Sección 7: Firma --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-bold text-gray-900">7. Firma</h2>
                <div class="py-8 text-center">
                    <div class="mx-auto w-64 border-b-2 border-gray-400"></div>
                    <p class="mt-2 text-sm font-semibold text-gray-700">
                        {{ $datos['encabezado']['responsable'] }}
                    </p>
                    <p class="text-xs text-gray-500">
                        Responsable — {{ $datos['encabezado']['centro_nombre'] }}
                    </p>
                    <p class="mt-2 text-xs text-gray-400">
                        {{ $datos['encabezado']['municipio'] }}, {{ $datos['encabezado']['fecha_generacion'] }}
                    </p>
                </div>
            </div>
        @elseif ($centroSaludId === 0)
            <div class="rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-800">
                Seleccione un centro de salud para generar el informe.
            </div>
        @endif

        {{-- Comentarios --}}
        <livewire:comentarios pagina="cai" />
    </div>
</div>
