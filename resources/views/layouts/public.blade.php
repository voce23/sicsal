<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SIMUES — Sistema de Información Municipal de Establecimientos de Salud' }}</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 36 36'><text y='32' font-size='32'>🏥</text></svg>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">

    {{-- Navbar --}}
    <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6">

            {{-- Logo --}}
            <a href="/" class="flex items-center gap-2">
                <span class="flex size-9 items-center justify-center rounded-lg bg-teal-600 text-lg font-bold text-white">S</span>
                <div class="leading-tight">
                    <span class="text-lg font-bold text-gray-900">SIMUES</span>
                    <span class="hidden text-xs text-gray-500 sm:block">Sistema de Información Municipal de Establecimientos de Salud</span>
                </div>
            </a>

            {{-- Links --}}
            <div class="flex items-center gap-1 sm:gap-3">
                @auth
                    @if(auth()->user()->activo)
                    {{-- Usuario aprobado: menú completo --}}
                    <a href="/comunidades" class="hidden rounded-md px-2 py-1 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-teal-600 sm:inline-flex {{ request()->is('comunidades') ? 'text-teal-600 bg-teal-50' : '' }}">Comunidades</a>
                    <a href="/poblacion" class="hidden rounded-md px-2 py-1 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-teal-600 sm:inline-flex {{ request()->is('poblacion') ? 'text-teal-600 bg-teal-50' : '' }}">Población</a>
                    <a href="/cai" class="hidden rounded-md px-2 py-1 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-teal-600 sm:inline-flex {{ request()->is('cai') ? 'text-teal-600 bg-teal-50' : '' }}">C.A.I.</a>
                    <a href="/causas-consulta" class="hidden rounded-md px-2 py-1 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:rose-600 sm:inline-flex {{ request()->is('causas-consulta') ? 'text-rose-600 bg-rose-50' : '' }}">Causas Consulta</a>
                    @endif
                    <a href="/blog" class="rounded-md px-2 py-1 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-teal-600 {{ request()->is('blog*') ? 'text-teal-600 bg-teal-50' : '' }}">Blog</a>

                    {{-- Menú usuario --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                            <span class="flex size-6 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                            <svg class="size-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-cloak
                             class="absolute right-0 mt-2 w-48 rounded-xl border border-gray-200 bg-white py-1 shadow-lg">
                            @if(auth()->user()->activo && auth()->user()->hasAnyRole(['superadmin', 'admin', 'registrador']))
                            <a href="/admin" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <svg class="size-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                </svg>
                                Panel de administración
                            </a>
                            <div class="my-1 border-t border-gray-100"></div>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                                    </svg>
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>

                @else
                    {{-- Invitado: solo Blog + acceso --}}
                    <a href="/blog" class="rounded-md px-2 py-1 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-teal-600 {{ request()->is('blog*') ? 'text-teal-600 bg-teal-50' : '' }}">Blog</a>

                    <a href="{{ route('login') }}" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                        Iniciar sesión
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="rounded-lg bg-teal-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition hover:bg-teal-700">
                            Registrarse
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </nav>

    {{-- Content --}}
    <main>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="border-t border-gray-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
            <div class="flex flex-col items-center justify-between gap-3 text-center text-sm text-gray-500 sm:flex-row sm:text-left">
                <div>
                    <p class="font-medium text-gray-700">SIMUES &mdash; Todos los derechos reservados</p>
                    <p class="mt-0.5 text-xs text-gray-400">Fuente de datos: Ministerio de Salud y Deportes &middot; SNIS-VE &middot; INE Bolivia</p>
                </div>
                <div class="flex items-center gap-3 text-xs text-gray-400">
                    <span>Red de Salud VII &mdash; Capinota</span>
                    <span>&middot;</span>
                    <span>SEDES Cochabamba &middot; Gestión {{ date('Y') }}</span>
                    <a href="/admin" class="text-gray-300 transition hover:text-gray-500" title="Acceso administrador">⚙</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @stack('scripts')
</body>
</html>
