<x-layouts.public>
    <x-slot:title>SIMUES — Red de Salud VII Capinota</x-slot:title>

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-teal-700 via-teal-600 to-cyan-600 text-white">
        <div class="absolute inset-0 opacity-10">
            <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>
        </div>

        <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24">
            <div>
                <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-medium uppercase tracking-wider">
                    Primer Nivel de Atención &mdash; Red de Salud VII
                </span>
                <h1 class="mt-4 text-[1.9rem] font-extrabold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                    Sistema de Información Municipal<br>de Establecimientos de Salud
                </h1>
                <p class="mt-4 text-lg text-teal-100 sm:text-xl">
                    Monitoreo de indicadores de salud, coberturas del PAI, producción de servicios
                    y gestión de información para la <strong class="text-white">Red de Salud VII — Capinota</strong>,
                    Departamento de Cochabamba, Bolivia.
                </p>
            </div>

            {{-- Stats --}}
            <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-4 sm:gap-6">
                <div class="rounded-xl bg-white/10 p-4 backdrop-blur">
                    <div class="text-3xl font-bold">{{ number_format($poblacionTotal, 0, ',', '.') }}</div>
                    <div class="text-sm text-teal-200">Población INE</div>
                </div>
                <div class="rounded-xl bg-white/10 p-4 backdrop-blur">
                    <div class="text-3xl font-bold">6</div>
                    <div class="text-sm text-teal-200">Centros de Salud</div>
                </div>
                <div class="rounded-xl bg-white/10 p-4 backdrop-blur">
                    <div class="text-3xl font-bold">6</div>
                    <div class="text-sm text-teal-200">Municipios Red VII</div>
                </div>
                <div class="rounded-xl bg-white/10 p-4 backdrop-blur">
                    <div class="text-3xl font-bold">31</div>
                    <div class="text-sm text-teal-200">Biológicos PAI</div>
                </div>
            </div>

            {{-- CTA para invitados --}}
            @guest
            <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-semibold text-teal-700 shadow-lg transition hover:bg-teal-50">
                    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                    </svg>
                    Crear cuenta gratuita
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
                    Iniciar sesión
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>
            @endguest
        </div>
    </section>

    {{-- Map Section --}}
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Red de Salud VII — Capinota</h2>
                <p class="text-gray-500">6 establecimientos de salud integrados al sistema.</p>
            </div>
            {{-- Selector de centro --}}
            @if ($centrosMapas->count() > 0)
            <div class="flex items-center gap-2">
                <label class="text-xs font-medium text-gray-500">Ver en mapa:</label>
                <select id="selector-centro"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-sm focus:border-teal-400 focus:outline-none">
                    <option value="all">Todos los centros</option>
                    @foreach ($centrosMapas as $cm)
                        <option value="{{ $cm['id'] }}" data-lat="{{ $cm['lat'] }}" data-lng="{{ $cm['lng'] }}">
                            {{ $cm['nombre'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Mapa --}}
            <div class="lg:col-span-2">
                <div id="mapa" class="h-64 w-full rounded-xl border border-gray-200 shadow-sm sm:h-96 lg:h-[460px]"></div>

                @if ($centrosMapas->count() === 0)
                    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                        Aún no se han registrado coordenadas GPS para los centros de salud.
                        Los administradores pueden ingresar las coordenadas desde el Panel → Configuración → Centros de Salud.
                    </div>
                @endif
            </div>

            {{-- Sidebar de centros --}}
            <div class="space-y-3">
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex size-2.5 rounded-full bg-teal-500"></span>
                        <span class="text-xs font-semibold uppercase tracking-wider text-teal-700">Centros — Capinota</span>
                    </div>
                    <ul class="space-y-1.5 text-sm">
                        @foreach ($centrosDb->sortBy('nombre') as $centro)
                            <li class="flex items-center gap-2 rounded-lg px-2 py-1.5 transition
                                @if ($centrosMapas->firstWhere('id', $centro->id)) hover:bg-teal-50 cursor-pointer @else opacity-60 @endif"
                                @if ($centrosMapas->firstWhere('id', $centro->id))
                                    onclick="centrarMapa({{ $centro->latitud }}, {{ $centro->longitud }}, '{{ addslashes($centro->nombre) }}')"
                                @endif>
                                <span class="flex size-2 shrink-0 rounded-full
                                    {{ $centrosMapas->firstWhere('id', $centro->id) ? 'bg-teal-500' : 'bg-gray-300' }}">
                                </span>
                                <div class="min-w-0">
                                    <div class="truncate font-medium text-gray-900">{{ $centro->nombre }}</div>
                                    <div class="text-xs text-gray-400">SNIS {{ $centro->codigo_snis }}
                                        @if (!$centrosMapas->firstWhere('id', $centro->id))
                                            · <span class="text-amber-500">Sin coordenadas</span>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4 border-t border-gray-100 pt-3">
                        @auth
                        <a href="/admin" class="inline-flex w-full items-center justify-center gap-1 rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-teal-700">
                            Ir al Panel de Administración
                        </a>
                        @else
                        <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center gap-1 rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-teal-700">
                            Acceder al sistema
                        </a>
                        @endauth
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Otros Municipios Red VII</h4>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (['Santivañez', 'Arque', 'Sicaya', 'Bolívar', 'Tacopaya'] as $mun)
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">{{ $mun }}</span>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-gray-400">Incorporación en fases posteriores.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Pirámides Poblacionales por Centro --}}
    <section class="border-t border-gray-200 bg-gray-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
            <div class="mb-8 text-center">
                <h2 class="text-2xl font-bold text-gray-900">Pirámides Poblacionales por Centro</h2>
                <p class="mt-2 text-sm text-gray-500">
                    Comparación entre la población estimada por el INE y la población real registrada en el padrón censal.
                </p>
            </div>

            <div class="grid gap-8 lg:grid-cols-2">
                @foreach ($piramides as $pir)
                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                        {{-- Header del centro --}}
                        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                            <div class="flex items-center gap-2">
                                <span class="flex size-2.5 rounded-full bg-teal-500"></span>
                                <h3 class="font-semibold text-gray-900">{{ $pir['nombre'] }}</h3>
                            </div>
                            <span class="text-xs text-gray-400">Pob. INE: {{ number_format($pir['totalIne'], 0, ',', '.') }} hab.</span>
                        </div>

                        <div class="grid grid-cols-2 gap-0 divide-x divide-gray-100">
                            {{-- Pirámide INE --}}
                            <div class="p-4">
                                <p class="mb-2 text-center text-xs font-semibold text-blue-600">Población INE</p>
                                <canvas id="pir-ine-{{ $pir['id'] }}" height="220"></canvas>
                            </div>

                            {{-- Pirámide Real o Próximamente --}}
                            <div class="p-4">
                                @if ($pir['tieneReal'])
                                    <p class="mb-2 text-center text-xs font-semibold text-teal-600">Población Real</p>
                                    <canvas id="pir-real-{{ $pir['id'] }}" height="220"></canvas>
                                @else
                                    <div class="flex h-full flex-col items-center justify-center gap-2 text-center">
                                        <svg class="size-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" />
                                        </svg>
                                        <div>
                                            <p class="text-xs font-semibold text-gray-500">Población Real</p>
                                            <p class="mt-1 text-xs text-gray-400">Próximamente disponible</p>
                                            <p class="text-xs text-gray-300">En fase de desarrollo</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Módulos del Sistema --}}
    <section class="border-t border-gray-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
            <div class="mb-8 text-center">
                <h2 class="text-2xl font-bold text-gray-900">Módulos del Sistema</h2>
                @guest
                <p class="mt-2 text-sm text-gray-500">
                    <a href="{{ route('register') }}" class="font-medium text-teal-600 hover:underline">Regístrate</a>
                    o
                    <a href="{{ route('login') }}" class="font-medium text-teal-600 hover:underline">inicia sesión</a>
                    para acceder a todos los módulos.
                </p>
                @endguest
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                    $modulos = [
                        [
                            'icon' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z',
                            'titulo' => 'FORM 301A — SNIS',
                            'desc' => 'Registro mensual de producción de servicios: consulta externa, vacunas, prenatales, partos, crecimiento, odontología, referencias y más.',
                            'href' => '/admin',
                            'auth' => true,
                        ],
                        [
                            'icon' => 'M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.25-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z',
                            'titulo' => 'PAI — Inmunizaciones',
                            'desc' => 'Coberturas, acceso, deserción e integralidad del Programa Ampliado de Inmunizaciones por biológico y grupo etáreo.',
                            'href' => '/admin',
                            'auth' => true,
                        ],
                        [
                            'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
                            'titulo' => 'Informe CAI',
                            'desc' => 'Generación automática de informes CAI con exportación a Excel, PDF y presentación PowerPoint de 33 diapositivas.',
                            'href' => '/cai',
                            'auth' => true,
                        ],
                        [
                            'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
                            'titulo' => 'Padrón Poblacional',
                            'desc' => 'Registro censal de personas por comunidad, pirámide poblacional, comparación INE vs realidad, contexto migratorio.',
                            'href' => '/poblacion',
                            'auth' => true,
                        ],
                        [
                            'icon' => 'M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z',
                            'titulo' => 'Coberturas y Tendencias',
                            'desc' => 'Monitoreo visual de coberturas por programa, tendencias mensuales, semáforos de alerta temprana y análisis comparativo.',
                            'href' => '/admin',
                            'auth' => true,
                        ],
                        [
                            'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
                            'titulo' => 'Blog',
                            'desc' => 'Publicaciones, boletines y actualizaciones del sistema de salud de la Red VII Capinota, acceso libre para la comunidad.',
                            'href' => '/blog',
                            'auth' => false,
                        ],
                    ];
                @endphp

                @foreach ($modulos as $mod)
                    @php $esProtegido = $mod['auth'] && !auth()->check(); @endphp
                    <a href="{{ $esProtegido ? route('login') : $mod['href'] }}"
                       class="group relative rounded-xl border border-gray-200 bg-gray-50 p-6 transition hover:border-teal-300 hover:bg-teal-50/50 hover:shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-teal-100 text-teal-600 transition group-hover:bg-teal-200">
                                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $mod['icon'] }}" />
                                </svg>
                            </div>
                            @if ($esProtegido)
                                <span class="flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                                    <svg class="size-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                    Requiere acceso
                                </span>
                            @endif
                        </div>
                        <h3 class="font-bold text-gray-900">{{ $mod['titulo'] }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ $mod['desc'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Llamada a la acción final (solo invitados) --}}
    @guest
    <section class="bg-linear-to-r from-teal-600 to-cyan-600 py-14 text-white">
        <div class="mx-auto max-w-3xl px-4 text-center sm:px-6">
            <h2 class="text-2xl font-bold sm:text-3xl">¿Eres personal de salud de la Red VII?</h2>
            <p class="mt-3 text-teal-100">
                Solicita acceso para gestionar los datos de tu centro, ver coberturas del PAI,
                generar informes CAI y más.
            </p>
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('register') }}" class="w-full rounded-xl bg-white px-6 py-3 text-sm font-semibold text-teal-700 shadow-lg transition hover:bg-teal-50 sm:w-auto">
                    Crear cuenta
                </a>
                <a href="{{ route('login') }}" class="w-full rounded-xl border border-white/30 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20 sm:w-auto">
                    Ya tengo cuenta — Iniciar sesión
                </a>
            </div>
        </div>
    </section>
    @endguest

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // ── Pirámides por centro ───────────────────────────────────────────
            const piramides = @json($piramides);

            function crearPiramide(canvasId, labels, dataM, dataF, colorM, colorF) {
                const ctx = document.getElementById(canvasId);
                if (!ctx) return;
                new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Masculino',
                                data: dataM.map(v => -v),
                                backgroundColor: colorM + '0.7)',
                                borderColor:     colorM + '1)',
                                borderWidth: 1,
                            },
                            {
                                label: 'Femenino',
                                data: dataF,
                                backgroundColor: colorF + '0.7)',
                                borderColor:     colorF + '1)',
                                borderWidth: 1,
                            },
                        ],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        animation: false,
                        scales: {
                            x: {
                                stacked: true,
                                ticks: { callback: v => Math.abs(v), font: { size: 9 } },
                                grid: { color: '#f3f4f6' },
                            },
                            y: {
                                stacked: true,
                                reverse: true,
                                ticks: { font: { size: 9 } },
                                grid: { display: false },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ctx.dataset.label + ': ' + Math.abs(ctx.parsed.x),
                                },
                            },
                        },
                    },
                });
            }

            piramides.forEach(function(pir) {
                // INE pyramid (azul/rosa)
                crearPiramide(
                    'pir-ine-' + pir.id,
                    pir.labels, pir.ineM, pir.ineF,
                    'rgba(59,130,246,', 'rgba(236,72,153,'
                );
                // Real pyramid (teal/amber) — solo si tiene datos
                if (pir.tieneReal) {
                    crearPiramide(
                        'pir-real-' + pir.id,
                        pir.labels, pir.realM, pir.realF,
                        'rgba(20,184,166,', 'rgba(245,158,11,'
                    );
                }
            });

            // ── Mapa ──────────────────────────────────────────────────────────
            const centrosMapas = @json($centrosMapas);

            // Centro inicial: primer centro con coordenadas o Bolivia como fallback
            const centroInicial = centrosMapas.length > 0
                ? [centrosMapas[0].lat, centrosMapas[0].lng]
                : [-17.75, -66.27];
            const zoomInicial = centrosMapas.length > 0 ? 14 : 11;

            const map = L.map('mapa').setView(centroInicial, zoomInicial);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(map);

            const markers = {};

            centrosMapas.forEach(function (c) {
                const marker = L.marker([c.lat, c.lng], {
                    icon: L.divIcon({
                        className: '',
                        html: '<div style="width:14px;height:14px;border-radius:50%;background:#14b8a6;border:3px solid #0d9488;box-shadow:0 2px 6px rgba(0,0,0,.3)"></div>',
                        iconSize: [14, 14],
                        iconAnchor: [7, 7],
                    }),
                }).addTo(map);

                marker.bindPopup(
                    '<div style="min-width:160px">' +
                    '<strong style="font-size:13px">' + c.nombre + '</strong><br>' +
                    '<span style="font-size:11px;color:#6b7280">SNIS: ' + c.snis + '</span><br>' +
                    '<span style="font-size:11px;color:#6b7280">Lat: ' + c.lat.toFixed(6) + ' | Lng: ' + c.lng.toFixed(6) + '</span>' +
                    '</div>'
                );
                marker.bindTooltip(c.nombre, { direction: 'top', offset: [0, -8] });
                markers[c.id] = marker;
            });

            // Si hay al menos 2 centros, ajustar zoom para mostrar todos
            if (centrosMapas.length > 1) {
                const bounds = L.latLngBounds(centrosMapas.map(c => [c.lat, c.lng]));
                map.fitBounds(bounds, { padding: [40, 40] });
            }

            // Selector de centro
            const selector = document.getElementById('selector-centro');
            if (selector) {
                selector.addEventListener('change', function () {
                    const val = this.value;
                    if (val === 'all') {
                        if (centrosMapas.length > 1) {
                            const bounds = L.latLngBounds(centrosMapas.map(c => [c.lat, c.lng]));
                            map.fitBounds(bounds, { padding: [40, 40] });
                        }
                    } else {
                        const opt = this.selectedOptions[0];
                        const lat = parseFloat(opt.dataset.lat);
                        const lng = parseFloat(opt.dataset.lng);
                        map.setView([lat, lng], 15);
                        if (markers[val]) markers[val].openPopup();
                    }
                });
            }

            // Función global para centrar desde el sidebar
            window.centrarMapa = function(lat, lng, nombre) {
                map.setView([lat, lng], 15);
                const found = centrosMapas.find(c => c.lat === lat && c.lng === lng);
                if (found && markers[found.id]) markers[found.id].openPopup();
            };
        });
    </script>
    @endpush
</x-layouts.public>
