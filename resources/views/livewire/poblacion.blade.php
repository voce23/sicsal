@php
    $pir = $piramideData;
    $mig = $this->migracion;
    $cobertura = $this->cobertura;
@endphp

<div>
    {{-- Header --}}
    <section class="border-b border-gray-200 bg-gradient-to-r from-teal-700 to-cyan-600 text-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <h1 class="text-2xl font-extrabold sm:text-3xl">Población</h1>
            <p class="mt-1 text-teal-100">Análisis demográfico — Gestión {{ $anio }}</p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6">
        <x-centro-selector />

        {{-- Tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex gap-6">
                <button wire:click="$set('tab', 'piramide')"
                    class="border-b-2 px-1 pb-3 text-sm font-medium transition {{ $tab === 'piramide' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    Pirámide Poblacional
                </button>
                <button wire:click="$set('tab', 'ine-vs-real')"
                    class="border-b-2 px-1 pb-3 text-sm font-medium transition {{ $tab === 'ine-vs-real' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    INE vs. Población Real
                </button>
            </nav>
        </div>

        {{-- ═══════════════════════════════════════════════ --}}
        {{-- TAB: PIRÁMIDE --}}
        {{-- ═══════════════════════════════════════════════ --}}
        @if ($tab === 'piramide')
            <div class="rounded-xl border border-gray-200 bg-white p-6"
                 wire:key="piramide-charts-{{ $centroSaludId }}"
                 data-pir='@json($pir)'
                 x-data
                 x-init="$nextTick(() => {
                     const d = JSON.parse($el.dataset.pir);
                     window._crearPiramide('_ine',  'piramideIne',  d.labels, d.ineM,  d.ineF);
                     window._crearPiramide('_real', 'piramideReal', d.labels, d.realM, d.realF);
                 })">
                <h2 class="mb-4 text-lg font-bold text-gray-900">Pirámide Poblacional — INE vs. Real {{ $anio }}</h2>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-center text-sm font-semibold text-gray-700">Población INE</h3>
                        <canvas id="piramideIne" height="300"></canvas>
                    </div>
                    <div>
                        <h3 class="mb-2 text-center text-sm font-semibold text-gray-700">Población Real</h3>
                        <canvas id="piramideReal" height="300"></canvas>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-bold text-gray-900">Comparación numérica</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="px-3 py-2">Grupo</th>
                                <th class="px-3 py-2 text-center text-blue-600">INE M</th>
                                <th class="px-3 py-2 text-center text-pink-600">INE F</th>
                                <th class="px-3 py-2 text-center text-blue-600">Real M</th>
                                <th class="px-3 py-2 text-center text-pink-600">Real F</th>
                                <th class="px-3 py-2 text-center">Dif M</th>
                                <th class="px-3 py-2 text-center">Dif F</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pir['tabla'] as $fila)
                                <tr class="border-b">
                                    <td class="px-3 py-2 font-medium">{{ $fila['grupo'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $fila['ine_m'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $fila['ine_f'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $fila['real_m'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $fila['real_f'] }}</td>
                                    <td class="px-3 py-2 text-center {{ $fila['dif_m'] < 0 ? 'text-red-500' : 'text-green-600' }}">
                                        {{ $fila['dif_m'] >= 0 ? '+' : '' }}{{ $fila['dif_m'] }}
                                    </td>
                                    <td class="px-3 py-2 text-center {{ $fila['dif_f'] < 0 ? 'text-red-500' : 'text-green-600' }}">
                                        {{ $fila['dif_f'] >= 0 ? '+' : '' }}{{ $fila['dif_f'] }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="border-t-2 font-bold">
                                <td class="px-3 py-2">TOTAL</td>
                                <td class="px-3 py-2 text-center">{{ array_sum($pir['ineM']) }}</td>
                                <td class="px-3 py-2 text-center">{{ array_sum($pir['ineF']) }}</td>
                                <td class="px-3 py-2 text-center">{{ array_sum($pir['realM']) }}</td>
                                <td class="px-3 py-2 text-center">{{ array_sum($pir['realF']) }}</td>
                                <td class="px-3 py-2 text-center">{{ array_sum($pir['realM']) - array_sum($pir['ineM']) }}</td>
                                <td class="px-3 py-2 text-center">{{ array_sum($pir['realF']) - array_sum($pir['ineF']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        {{-- ═══════════════════════════════════════════════ --}}
        {{-- TAB: INE vs REAL --}}
        {{-- ═══════════════════════════════════════════════ --}}
        @else
            {{-- Período selector --}}
            <div class="flex flex-wrap gap-2">
                @foreach ([
                    '1' => 'Ene', '2' => 'Feb', '3' => 'Mar', '4' => 'Abr',
                    '5' => 'May', '6' => 'Jun', '7' => 'Jul', '8' => 'Ago',
                    '9' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic',
                    'cai1' => 'CAI 1', 'cai2' => 'CAI 2', 'gestion' => 'Gestión',
                ] as $val => $label)
                    <button wire:click="$set('periodo', '{{ $val }}')"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ $periodo === (string)$val ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Migración --}}
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6">
                <h2 class="mb-4 text-lg font-bold text-gray-900">Contexto de Migración — {{ $this->nombrePeriodo }}</h2>
                <div class="space-y-2 font-mono text-sm">
                    <div class="flex justify-between border-b border-gray-200 pb-1">
                        <span>Mujeres 15-49 años activas (MEF):</span>
                        <span class="font-bold">{{ number_format($mig['mef_activas']) }}</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-200 pb-1">
                        <span>Mujeres 15-49 años migradas:</span>
                        <span class="font-bold">{{ number_format($mig['mef_migradas']) }} <span class="text-xs text-gray-400">({{ $mig['pct_mef'] }}%)</span></span>
                    </div>
                    <div class="flex justify-between border-b border-gray-200 pb-1">
                        <span>Hombres 15-49 años migrados:</span>
                        <span class="font-bold">{{ number_format($mig['hombres_migrados']) }}</span>
                    </div>
                    <div class="flex justify-between pb-1">
                        <span>Total personas migradas:</span>
                        <span class="font-bold">{{ number_format($mig['total_migrantes']) }} <span class="text-xs text-gray-400">({{ $mig['pct_migrantes'] }}% del padrón)</span></span>
                    </div>
                </div>
                @if ($mig['pct_migrantes'] > 10)
                    <div class="mt-3 rounded border border-amber-300 bg-amber-100 p-2 text-xs text-amber-700">
                        Este indicador explica la baja cobertura en:
                        <strong>Control prenatal</strong> · <strong>Partos</strong> · <strong>Planificación familiar</strong> · <strong>Vacunación</strong>
                    </div>
                @endif
            </div>

            {{-- Tabla de cobertura dual --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-bold text-gray-900">Tabla de Cobertura Dual — {{ $this->nombrePeriodo }}</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="px-3 py-2">Programa</th>
                                <th class="px-3 py-2 text-center">Meta INE</th>
                                <th class="px-3 py-2 text-center">Pob. Real Activa</th>
                                <th class="px-3 py-2 text-center">Atendidos</th>
                                <th class="px-3 py-2 text-center">Cob. INE %</th>
                                <th class="px-3 py-2 text-center">Cob. Real %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cobertura as $fila)
                                <tr class="border-b">
                                    <td class="px-3 py-2 font-medium">{{ $fila['nombre'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $fila['meta_ine'] }}</td>
                                    <td class="px-3 py-2 text-center">{{ $fila['pob_real'] }}</td>
                                    <td class="px-3 py-2 text-center font-semibold">{{ $fila['atendidos'] }}</td>
                                    <td class="px-3 py-2 text-center {{ $fila['cob_ine'] >= 80 ? 'text-green-600' : ($fila['cob_ine'] >= 50 ? 'text-yellow-600' : 'text-red-500') }}">
                                        {{ $fila['cob_ine'] }}%
                                    </td>
                                    <td class="px-3 py-2 text-center {{ $fila['cob_real'] >= 80 ? 'text-green-600' : ($fila['cob_real'] >= 50 ? 'text-yellow-600' : 'text-red-500') }}">
                                        {{ $fila['cob_real'] }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Comentarios --}}
        <livewire:comentarios pagina="poblacion" />

        @script
        <script>
            window._crearPiramide = function(windowKey, canvasId, labels, dataM, dataF) {
                if (window[windowKey]) { window[windowKey].destroy(); window[windowKey] = null; }
                const ctx = document.getElementById(canvasId);
                if (!ctx) return;
                window[windowKey] = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [
                            { label: 'Masculino', data: dataM.map(v => -v), backgroundColor: 'rgba(59,130,246,0.7)', borderColor: 'rgba(59,130,246,1)', borderWidth: 1 },
                            { label: 'Femenino',  data: dataF,              backgroundColor: 'rgba(236,72,153,0.7)', borderColor: 'rgba(236,72,153,1)', borderWidth: 1 },
                        ],
                    },
                    options: {
                        indexAxis: 'y', responsive: true,
                        scales: {
                            x: { stacked: true, ticks: { callback: v => Math.abs(v) } },
                            y: { stacked: true, reverse: true }
                        },
                        plugins: { tooltip: { callbacks: { label: c => c.dataset.label + ': ' + Math.abs(c.parsed.x) } } },
                    },
                });
            };
        </script>
        @endscript
    </div>
</div>

