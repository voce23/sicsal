@php $datos = $this->datos; $grupos = $datos['grupos']; @endphp

<div>
    {{-- Header --}}
    <section class="border-b border-gray-200 bg-gradient-to-r from-teal-700 to-cyan-600 text-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-extrabold sm:text-3xl">Comunidades</h1>
                    <p class="mt-1 text-teal-100">Detalle poblacional por comunidad y grupo etáreo</p>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6">
        <x-centro-selector />

        {{-- Tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex gap-6">
                <button wire:click="$set('tab', 'resumen')"
                    class="border-b-2 px-1 pb-3 text-sm font-medium transition {{ $tab === 'resumen' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    Resumen Poblacional
                </button>
                <button wire:click="$set('tab', 'planilla')"
                    class="border-b-2 px-1 pb-3 text-sm font-medium transition {{ $tab === 'planilla' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    Planilla de Campo
                </button>
            </nav>
        </div>

        {{-- ═══════════════════════════════════════════════ --}}
        {{-- TAB: RESUMEN --}}
        {{-- ═══════════════════════════════════════════════ --}}
        @if ($tab === 'resumen')
            {{-- TABLA 1: RESUMEN POR COMUNIDAD --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-bold text-gray-900">Resumen por Comunidad</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 text-left">
                                <th class="px-2 py-2" rowspan="2">Comunidad</th>
                                <th class="px-2 py-2 text-center" rowspan="2">Km</th>
                                <th class="bg-blue-50 px-2 py-1 text-center" colspan="3">Demografía</th>
                                <th class="bg-green-50 px-2 py-1 text-center" colspan="9">Grupos etáreos</th>
                                <th class="bg-amber-50 px-2 py-1 text-center" rowspan="2">Migr.</th>
                            </tr>
                            <tr class="border-b text-center text-xs">
                                <th class="bg-blue-50 px-2 py-1">Total</th>
                                <th class="bg-blue-50 px-2 py-1">H</th>
                                <th class="bg-blue-50 px-2 py-1">M</th>
                                @foreach ($grupos as $key => $label)
                                    <th class="bg-green-50 px-2 py-1">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($datos['filas'] as $i => $fila)
                                <tr class="{{ $i % 2 === 0 ? 'bg-gray-50' : '' }} border-b">
                                    <td class="px-2 py-1.5 font-medium">{{ $fila['comunidad'] }}</td>
                                    <td class="px-2 py-1.5 text-center text-gray-500">{{ $fila['km'] }}</td>
                                    <td class="bg-blue-50/50 px-2 py-1.5 text-center font-semibold">{{ $fila['total'] }}</td>
                                    <td class="bg-blue-50/50 px-2 py-1.5 text-center">{{ $fila['hombres'] }}</td>
                                    <td class="bg-blue-50/50 px-2 py-1.5 text-center">{{ $fila['mujeres'] }}</td>
                                    @foreach ($grupos as $key => $label)
                                        <td class="bg-green-50/50 px-2 py-1.5 text-center">{{ $fila[$key] }}</td>
                                    @endforeach
                                    <td class="bg-amber-50/50 px-2 py-1.5 text-center">{{ $fila['migrantes'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="15" class="px-3 py-4 text-center text-gray-500">Sin comunidades registradas</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 font-bold">
                                <td class="px-2 py-2" colspan="2">TOTAL REAL</td>
                                <td class="bg-blue-50 px-2 py-2 text-center">{{ $datos['totales']['total'] }}</td>
                                <td class="bg-blue-50 px-2 py-2 text-center">{{ $datos['totales']['hombres'] }}</td>
                                <td class="bg-blue-50 px-2 py-2 text-center">{{ $datos['totales']['mujeres'] }}</td>
                                @foreach ($grupos as $key => $label)
                                    <td class="bg-green-50 px-2 py-2 text-center">{{ $datos['totales'][$key] }}</td>
                                @endforeach
                                <td class="bg-amber-50 px-2 py-2 text-center">{{ $datos['totales']['migrantes'] }}</td>
                            </tr>
                            <tr>
                                <td class="px-2 py-2 font-medium" colspan="2">META INE</td>
                                <td class="px-2 py-2 text-center font-semibold">{{ number_format($datos['metaIne']) }}</td>
                                <td colspan="12"></td>
                            </tr>
                            <tr>
                                <td class="px-2 py-2 font-medium" colspan="2">DIFERENCIA</td>
                                <td class="px-2 py-2 text-center font-semibold {{ $datos['diferencia'] < 0 ? 'text-red-500' : 'text-green-600' }}">
                                    {{ $datos['diferencia'] >= 0 ? '+' : '' }}{{ number_format($datos['diferencia']) }}
                                    @if ($datos['metaIne'] > 0)
                                        <span class="text-xs text-gray-400">({{ round($datos['totales']['total'] / $datos['metaIne'] * 100, 1) }}%)</span>
                                    @endif
                                </td>
                                <td colspan="12"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- TABLA 2: DETALLE POR SEXO Y GRUPO ETÁREO --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-bold text-gray-900">Detalle por Sexo y Grupo Etáreo</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 text-left">
                                <th class="px-2 py-2" rowspan="2">Comunidad</th>
                                @foreach ($grupos as $key => $label)
                                    <th class="bg-green-50 px-1 py-1 text-center text-xs" colspan="2">{{ $label }}</th>
                                @endforeach
                                <th class="bg-amber-50 px-1 py-1 text-center text-xs" colspan="2">Migr.</th>
                                <th class="bg-blue-50 px-1 py-1 text-center text-xs" colspan="2">Total</th>
                            </tr>
                            <tr class="border-b text-center text-xs">
                                @for ($i = 0; $i < count($grupos); $i++)
                                    <th class="bg-green-50 px-1 py-1">H</th>
                                    <th class="bg-green-50 px-1 py-1">M</th>
                                @endfor
                                <th class="bg-amber-50 px-1 py-1">H</th>
                                <th class="bg-amber-50 px-1 py-1">M</th>
                                <th class="bg-blue-50 px-1 py-1">H</th>
                                <th class="bg-blue-50 px-1 py-1">M</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($datos['detalle'] as $i => $row)
                                <tr class="{{ $i % 2 === 0 ? 'bg-gray-50' : '' }} border-b">
                                    <td class="px-2 py-1.5 font-medium whitespace-nowrap">{{ $row['comunidad'] }}</td>
                                    @foreach ($grupos as $key => $label)
                                        <td class="bg-green-50/50 px-1 py-1.5 text-center">{{ $row['datos'][$key]['M'] }}</td>
                                        <td class="bg-green-50/50 px-1 py-1.5 text-center">{{ $row['datos'][$key]['F'] }}</td>
                                    @endforeach
                                    <td class="bg-amber-50/50 px-1 py-1.5 text-center">{{ $row['datos']['migrantes']['M'] }}</td>
                                    <td class="bg-amber-50/50 px-1 py-1.5 text-center">{{ $row['datos']['migrantes']['F'] }}</td>
                                    <td class="bg-blue-50/50 px-1 py-1.5 text-center font-semibold">{{ $row['datos']['total']['M'] }}</td>
                                    <td class="bg-blue-50/50 px-1 py-1.5 text-center font-semibold">{{ $row['datos']['total']['F'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ (count($grupos) + 2) * 2 + 1 }}" class="px-3 py-4 text-center text-gray-500">Sin datos</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 font-bold">
                                <td class="px-2 py-2">TOTAL</td>
                                @foreach ($grupos as $key => $label)
                                    <td class="bg-green-50 px-1 py-2 text-center">{{ $datos['detalleTotales'][$key]['M'] }}</td>
                                    <td class="bg-green-50 px-1 py-2 text-center">{{ $datos['detalleTotales'][$key]['F'] }}</td>
                                @endforeach
                                <td class="bg-amber-50 px-1 py-2 text-center">{{ $datos['detalleTotales']['migrantes']['M'] }}</td>
                                <td class="bg-amber-50 px-1 py-2 text-center">{{ $datos['detalleTotales']['migrantes']['F'] }}</td>
                                <td class="bg-blue-50 px-1 py-2 text-center">{{ $datos['detalleTotales']['total']['M'] }}</td>
                                <td class="bg-blue-50 px-1 py-2 text-center">{{ $datos['detalleTotales']['total']['F'] }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- TABLA 3: INE vs REAL CONSOLIDADO --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-bold text-gray-900">Consolidado Meta INE vs. Población Real</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 text-left">
                                <th class="px-2 py-2" rowspan="2">Grupo Etáreo</th>
                                <th class="bg-purple-50 px-2 py-1 text-center" colspan="3">Meta INE</th>
                                <th class="bg-blue-50 px-2 py-1 text-center" colspan="3">Población Real</th>
                                <th class="bg-orange-50 px-2 py-1 text-center" rowspan="2">Diferencia</th>
                                <th class="bg-emerald-50 px-2 py-1 text-center" rowspan="2">Cobertura %</th>
                            </tr>
                            <tr class="border-b text-center text-xs">
                                <th class="bg-purple-50 px-2 py-1">H</th>
                                <th class="bg-purple-50 px-2 py-1">M</th>
                                <th class="bg-purple-50 px-2 py-1">Total</th>
                                <th class="bg-blue-50 px-2 py-1">H</th>
                                <th class="bg-blue-50 px-2 py-1">M</th>
                                <th class="bg-blue-50 px-2 py-1">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $sumIneM=0; $sumIneF=0; $sumIneT=0; $sumRealM=0; $sumRealF=0; $sumRealT=0; @endphp
                            @forelse ($datos['consolidado'] as $i => $row)
                                @php
                                    $sumIneM += $row['ine_m']; $sumIneF += $row['ine_f']; $sumIneT += $row['ine_total'];
                                    $sumRealM += $row['real_m']; $sumRealF += $row['real_f']; $sumRealT += $row['real_total'];
                                @endphp
                                <tr class="{{ $i % 2 === 0 ? 'bg-gray-50' : '' }} border-b">
                                    <td class="px-2 py-1.5 font-medium">{{ $row['label'] }}</td>
                                    <td class="bg-purple-50/50 px-2 py-1.5 text-center">{{ $row['ine_m'] }}</td>
                                    <td class="bg-purple-50/50 px-2 py-1.5 text-center">{{ $row['ine_f'] }}</td>
                                    <td class="bg-purple-50/50 px-2 py-1.5 text-center font-semibold">{{ $row['ine_total'] }}</td>
                                    <td class="bg-blue-50/50 px-2 py-1.5 text-center">{{ $row['real_m'] }}</td>
                                    <td class="bg-blue-50/50 px-2 py-1.5 text-center">{{ $row['real_f'] }}</td>
                                    <td class="bg-blue-50/50 px-2 py-1.5 text-center font-semibold">{{ $row['real_total'] }}</td>
                                    <td class="bg-orange-50/50 px-2 py-1.5 text-center font-semibold {{ $row['diferencia'] < 0 ? 'text-red-500' : 'text-green-600' }}">
                                        {{ $row['diferencia'] >= 0 ? '+' : '' }}{{ $row['diferencia'] }}
                                    </td>
                                    <td class="bg-emerald-50/50 px-2 py-1.5 text-center font-semibold {{ $row['cobertura'] >= 100 ? 'text-green-600' : ($row['cobertura'] >= 80 ? 'text-amber-600' : 'text-red-500') }}">
                                        {{ $row['cobertura'] }}%
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-3 py-4 text-center text-gray-500">Sin datos de Meta INE</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 font-bold">
                                <td class="px-2 py-2">TOTAL</td>
                                <td class="bg-purple-50 px-2 py-2 text-center">{{ $sumIneM }}</td>
                                <td class="bg-purple-50 px-2 py-2 text-center">{{ $sumIneF }}</td>
                                <td class="bg-purple-50 px-2 py-2 text-center">{{ $sumIneT }}</td>
                                <td class="bg-blue-50 px-2 py-2 text-center">{{ $sumRealM }}</td>
                                <td class="bg-blue-50 px-2 py-2 text-center">{{ $sumRealF }}</td>
                                <td class="bg-blue-50 px-2 py-2 text-center">{{ $sumRealT }}</td>
                                <td class="bg-orange-50 px-2 py-2 text-center {{ ($sumRealT - $sumIneT) < 0 ? 'text-red-500' : 'text-green-600' }}">
                                    {{ ($sumRealT - $sumIneT) >= 0 ? '+' : '' }}{{ $sumRealT - $sumIneT }}
                                </td>
                                <td class="bg-emerald-50 px-2 py-2 text-center {{ $sumIneT > 0 && round($sumRealT / $sumIneT * 100, 1) >= 100 ? 'text-green-600' : 'text-amber-600' }}">
                                    {{ $sumIneT > 0 ? round($sumRealT / $sumIneT * 100, 1) : 0 }}%
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        {{-- ═══════════════════════════════════════════════ --}}
        {{-- TAB: PLANILLA DE CAMPO --}}
        {{-- ═══════════════════════════════════════════════ --}}
        @else
            @php $planilla = $this->planilla; $pGrupos = $planilla['grupos']; @endphp

            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-2 text-lg font-bold text-gray-900">
                    Planilla de Censo por Comunidad — Gestión {{ date('Y') }}
                </h2>
                <p class="mb-4 text-sm text-gray-500">Formato de campo para recojo de información censal (auto-generado desde datos del sistema).</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b-2 text-left">
                                <th class="px-2 py-2 text-sm" rowspan="2">Comunidad</th>
                                <th class="bg-blue-50 px-1 py-1 text-center" colspan="2">Pob. Total</th>
                                @foreach ($pGrupos as $key => $cfg)
                                    <th class="bg-green-50 px-1 py-1 text-center" colspan="2">{{ $cfg['label'] }}</th>
                                @endforeach
                                <th class="bg-pink-50 px-1 py-1 text-center" rowspan="2">Embar.</th>
                            </tr>
                            <tr class="border-b text-center">
                                <th class="bg-blue-50 px-1 py-1">M</th>
                                <th class="bg-blue-50 px-1 py-1">F</th>
                                @foreach ($pGrupos as $key => $cfg)
                                    <th class="bg-green-50 px-1 py-1">M</th>
                                    <th class="bg-green-50 px-1 py-1">F</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($planilla['filas'] as $i => $fila)
                                <tr class="{{ $i % 2 === 0 ? 'bg-gray-50' : '' }} border-b">
                                    <td class="px-2 py-1.5 font-medium text-sm whitespace-nowrap">{{ $fila['comunidad'] }}</td>
                                    <td class="bg-blue-50/50 px-1 py-1.5 text-center font-semibold">{{ $fila['total']['M'] }}</td>
                                    <td class="bg-blue-50/50 px-1 py-1.5 text-center font-semibold">{{ $fila['total']['F'] }}</td>
                                    @foreach ($pGrupos as $key => $cfg)
                                        <td class="bg-green-50/50 px-1 py-1.5 text-center">{{ $fila[$key]['M'] }}</td>
                                        <td class="bg-green-50/50 px-1 py-1.5 text-center">{{ $fila[$key]['F'] }}</td>
                                    @endforeach
                                    <td class="bg-pink-50/50 px-1 py-1.5 text-center font-semibold">{{ $fila['embarazadas'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ 3 + count($pGrupos) * 2 }}" class="px-3 py-4 text-center text-gray-500">Sin comunidades registradas</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 font-bold text-sm">
                                <td class="px-2 py-2">TOTAL</td>
                                <td class="bg-blue-50 px-1 py-2 text-center">{{ $planilla['totales']['total']['M'] ?? 0 }}</td>
                                <td class="bg-blue-50 px-1 py-2 text-center">{{ $planilla['totales']['total']['F'] ?? 0 }}</td>
                                @foreach ($pGrupos as $key => $cfg)
                                    <td class="bg-green-50 px-1 py-2 text-center">{{ $planilla['totales'][$key]['M'] ?? 0 }}</td>
                                    <td class="bg-green-50 px-1 py-2 text-center">{{ $planilla['totales'][$key]['F'] ?? 0 }}</td>
                                @endforeach
                                <td class="bg-pink-50 px-1 py-2 text-center">{{ $planilla['totales']['embarazadas'] ?? 0 }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif

        {{-- Comentarios --}}
        <livewire:comentarios pagina="comunidades" />
    </div>
</div>
