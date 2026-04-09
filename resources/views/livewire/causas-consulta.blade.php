@php
    $datos   = $this->datos;
    $grupos  = [
        'menor_6m'   => '<6m',
        '6m_menor_1' => '6m–1a',
        '1_4'        => '1–4a',
        '5_9'        => '5–9a',
        '10_14'      => '10–14a',
        '15_19'      => '15–19a',
        '20_39'      => '20–39a',
        '40_49'      => '40–49a',
        '50_59'      => '50–59a',
        'mayor_60'   => '≥60a',
    ];
    $grupoCols = array_keys($grupos);
    $meses = [
        1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril',
        5=>'Mayo', 6=>'Junio', 7=>'Julio', 8=>'Agosto',
        9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre',
    ];
@endphp

<div>
    {{-- Header --}}
    <section class="border-b border-gray-200 bg-linear-to-r from-rose-700 to-red-500 text-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <div>
                <h1 class="text-2xl font-extrabold sm:text-3xl">10 Principales Causas de Consulta Externa</h1>
                <p class="mt-1 text-rose-100">Diagnósticos más frecuentes por grupo etáreo y sexo</p>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">

        {{-- Filtros --}}
        <div class="flex flex-wrap items-end gap-4">
            @if(count($this->centros) > 1)
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Centro de Salud</label>
                    <select wire:model.live="centroSaludId"
                        class="rounded-lg border-gray-300 text-sm shadow-sm">
                        @foreach($this->centros as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Año</label>
                <select wire:model.live="anio" class="rounded-lg border-gray-300 text-sm shadow-sm">
                    @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Mes</label>
                <select wire:model.live="mes" class="rounded-lg border-gray-300 text-sm shadow-sm">
                    <option value="0">— Gestión completa —</option>
                    @foreach($meses as $num => $nombre)
                        <option value="{{ $num }}">{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($datos && count($datos['causas']) > 0)

            {{-- Tarjetas resumen --}}
            <div class="grid grid-cols-3 gap-4 sm:grid-cols-3">
                <div class="rounded-xl bg-rose-50 p-4 text-center">
                    <p class="text-3xl font-extrabold text-rose-700">{{ number_format($datos['grand_total']) }}</p>
                    <p class="mt-1 text-xs text-gray-600">Total consultas (top 10)</p>
                </div>
                <div class="rounded-xl bg-blue-50 p-4 text-center">
                    <p class="text-3xl font-extrabold text-blue-700">{{ number_format($datos['grand_total_m']) }}</p>
                    <p class="mt-1 text-xs text-gray-600">Masculino</p>
                </div>
                <div class="rounded-xl bg-pink-50 p-4 text-center">
                    <p class="text-3xl font-extrabold text-pink-700">{{ number_format($datos['grand_total_f']) }}</p>
                    <p class="mt-1 text-xs text-gray-600">Femenino</p>
                </div>
            </div>

            {{-- Tabla principal (scroll horizontal en móvil) --}}
            <div class="rounded-xl border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-base font-bold text-gray-900">
                        {{ $datos['periodo_label'] }} · {{ $datos['centro'] }}
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            {{-- Fila 1: grupos etáreos --}}
                            <tr class="border-b bg-rose-700 text-white">
                                <th class="px-2 py-2 text-center" rowspan="2">N°</th>
                                <th class="px-3 py-2 text-left" rowspan="2" style="min-width:180px">Diagnóstico</th>
                                @foreach($grupos as $g => $label)
                                    <th class="px-1 py-1 text-center font-semibold" colspan="2">{{ $label }}</th>
                                @endforeach
                                <th class="px-2 py-2 text-center" rowspan="2">Tot. M</th>
                                <th class="px-2 py-2 text-center" rowspan="2">Tot. F</th>
                                <th class="px-2 py-2 text-center font-bold" rowspan="2">Total<br>Parcial</th>
                                <th class="px-2 py-2 text-center font-bold" rowspan="2">%</th>
                            </tr>
                            {{-- Fila 2: M / F por grupo --}}
                            <tr class="border-b bg-rose-600 text-white">
                                @foreach($grupos as $g => $label)
                                    <th class="px-1 py-1 text-center" style="min-width:26px">M</th>
                                    <th class="px-1 py-1 text-center" style="min-width:26px">F</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($datos['causas'] as $i => $causa)
                                <tr class="border-b {{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-rose-50 transition-colors">
                                    <td class="px-2 py-2 text-center font-bold text-rose-600">{{ $causa['rank'] }}</td>
                                    <td class="px-3 py-2 font-medium text-gray-800">{{ $causa['diagnostico'] }}</td>
                                    @foreach($grupoCols as $g)
                                        <td class="px-1 py-2 text-center text-gray-700">{{ $causa['grupos'][$g]['m'] ?? 0 ?: '–' }}</td>
                                        <td class="px-1 py-2 text-center text-gray-700">{{ $causa['grupos'][$g]['f'] ?? 0 ?: '–' }}</td>
                                    @endforeach
                                    <td class="px-2 py-2 text-center text-blue-700 font-medium">{{ $causa['total_m'] }}</td>
                                    <td class="px-2 py-2 text-center text-pink-700 font-medium">{{ $causa['total_f'] }}</td>
                                    <td class="px-2 py-2 text-center font-bold text-gray-900">{{ $causa['total'] }}</td>
                                    <td class="px-2 py-2 text-center font-semibold">
                                        <span class="inline-block rounded-full px-2 py-0.5
                                            {{ $causa['porcentaje'] >= 20 ? 'bg-rose-100 text-rose-700' :
                                               ($causa['porcentaje'] >= 10 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-700') }}">
                                            {{ $causa['porcentaje'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Filas vacías hasta 10 --}}
                            @for($i = count($datos['causas']); $i < 10; $i++)
                                <tr class="border-b {{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                    <td class="px-2 py-2 text-center text-gray-300">{{ $i + 1 }}</td>
                                    <td class="px-3 py-2 text-gray-300">—</td>
                                    @foreach($grupoCols as $g)
                                        <td class="px-1 py-2"></td><td class="px-1 py-2"></td>
                                    @endforeach
                                    <td></td><td></td><td></td><td></td>
                                </tr>
                            @endfor

                            {{-- Fila TOTAL --}}
                            <tr class="border-t-2 border-rose-700 bg-rose-700 text-white font-bold">
                                <td class="px-2 py-2"></td>
                                <td class="px-3 py-2">TOTAL</td>
                                @foreach($grupoCols as $g)
                                    <td class="px-1 py-2 text-center">
                                        {{ array_sum(array_map(fn($c) => $c['grupos'][$g]['m'] ?? 0, $datos['causas'])) }}
                                    </td>
                                    <td class="px-1 py-2 text-center">
                                        {{ array_sum(array_map(fn($c) => $c['grupos'][$g]['f'] ?? 0, $datos['causas'])) }}
                                    </td>
                                @endforeach
                                <td class="px-2 py-2 text-center">{{ $datos['grand_total_m'] }}</td>
                                <td class="px-2 py-2 text-center">{{ $datos['grand_total_f'] }}</td>
                                <td class="px-2 py-2 text-center text-lg">{{ $datos['grand_total'] }}</td>
                                <td class="px-2 py-2 text-center">100%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Barras visuales --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-sm font-bold text-gray-700">Distribución relativa</h3>
                <div class="space-y-3">
                    @foreach($datos['causas'] as $causa)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="font-medium text-gray-700">
                                    <span class="mr-1 inline-flex size-5 items-center justify-center rounded-full bg-rose-600 text-xs font-bold text-white">{{ $causa['rank'] }}</span>
                                    {{ str()->limit($causa['diagnostico'], 60) }}
                                </span>
                                <span class="font-bold text-rose-700">{{ $causa['porcentaje'] }}% <span class="text-gray-500">({{ $causa['total'] }})</span></span>
                            </div>
                            <div class="h-4 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-4 rounded-full bg-linear-to-r from-rose-600 to-red-400 transition-all duration-500"
                                     style="width: {{ $causa['porcentaje'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        @elseif($centroSaludId > 0)
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-6 text-center">
                <p class="text-sm font-medium text-blue-700">No hay causas de consulta externa registradas para el período seleccionado.</p>
                <p class="mt-1 text-xs text-blue-500">Los datos se ingresan en el panel administrativo → Prestaciones Mensuales → Causas Consulta Externa.</p>
            </div>
        @else
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                Seleccione un centro de salud para ver los datos.
            </div>
        @endif

        {{-- Comentarios --}}
        <livewire:comentarios pagina="causas-consulta" />
    </div>
</div>
