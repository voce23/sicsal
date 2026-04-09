<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SIMUES — Sistema de Información Municipal de Establecimientos de Salud' }}</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 36 36'><text y='32' font-size='32'>🏥</text></svg>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        /* Fix Leaflet tiles con Tailwind v4 preflight */
        .leaflet-container img { max-width: none !important; max-height: none !important; }
        .leaflet-tile-pane img  { max-width: none !important; height: auto !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">

    {{-- Navbar --}}
    <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white/90 backdrop-blur"
         x-data="{ open: false }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="flex h-14 items-center justify-between">

                {{-- Logo --}}
                <a href="/" class="flex shrink-0 items-center gap-2">
                    <span class="flex size-8 items-center justify-center rounded-lg bg-teal-600 text-sm font-bold text-white">S</span>
                    <div class="leading-tight">
                        <span class="text-base font-bold text-gray-900">SIMUES</span>
                        <span class="hidden text-[10px] text-gray-500 sm:block">Sist. Inf. Municipal de Establecimientos de Salud</span>
                    </div>
                </a>

                {{-- Links desktop --}}
                <div class="hidden items-center gap-1 sm:flex">
                    @auth
                        @if(auth()->user()->activo)
                            <a href="/comunidades" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-teal-600 {{ request()->is('comunidades') ? 'bg-teal-50 text-teal-600' : '' }}">Comunidades</a>
                            <a href="/poblacion"   class="rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-teal-600 {{ request()->is('poblacion')   ? 'bg-teal-50 text-teal-600' : '' }}">Población</a>
                            <a href="/cai"         class="rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-teal-600 {{ request()->is('cai')         ? 'bg-teal-50 text-teal-600' : '' }}">C.A.I.</a>
                            <a href="/causas-consulta" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:rose-600 {{ request()->is('causas-consulta') ? 'bg-rose-50 text-rose-600' : '' }}">Causas</a>
                        @endif
                        <a href="/blog" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-teal-600 {{ request()->is('blog*') ? 'bg-teal-50 text-teal-600' : '' }}">Blog</a>

                        {{-- Menú usuario --}}
                        <div x-data="{ drop: false }" @keydown.escape.window="drop = false" class="relative ml-1">
                            <button @click="drop = !drop" @keydown.escape="drop = false" class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                                <span class="flex size-6 items-center justify-center rounded-full bg-teal-600 text-xs font-bold text-white">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                                <span class="max-w-25 truncate">{{ auth()->user()->name }}</span>
                                <svg class="size-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                            </button>
                            <div x-show="drop" @click.outside="drop = false" x-cloak
                                 class="absolute right-0 mt-2 w-48 rounded-xl border border-gray-200 bg-white py-1 shadow-lg">
                                @if(auth()->user()->activo && auth()->user()->hasAnyRole(['superadmin','admin','registrador']))
                                <a href="/admin" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <svg class="size-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Z"/></svg>
                                    Panel admin
                                </a>
                                <div class="my-1 border-t border-gray-100"></div>
                                @endif
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/></svg>
                                        Cerrar sesión
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="/blog" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-teal-600 {{ request()->is('blog*') ? 'bg-teal-50 text-teal-600' : '' }}">Blog</a>
                        <a href="{{ route('login') }}" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">Ingresar</a>
                        @if(Route::has('register'))
                        <a href="{{ route('register') }}" class="rounded-lg bg-teal-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition hover:bg-teal-700">Registrarse</a>
                        @endif
                    @endauth
                </div>

                {{-- Botón hamburger (móvil) --}}
                <button @click="open = !open" class="rounded-lg p-2 text-gray-600 transition hover:bg-gray-100 sm:hidden" aria-label="Menú">
                    <svg x-show="!open" class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    <svg x-show="open"  class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Menú móvil desplegable --}}
        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="border-t border-gray-100 bg-white px-4 pb-4 pt-2 sm:hidden">
            <div class="flex flex-col gap-1">
                @auth
                    {{-- Info usuario --}}
                    <div class="mb-2 flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2">
                        <span class="flex size-8 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900">{{ auth()->user()->name }} {{ auth()->user()->apellidos }}</p>
                            <p class="text-xs text-gray-500">{{ auth()->user()->activo ? 'Cuenta activa' : 'Pendiente de aprobación' }}</p>
                        </div>
                    </div>

                    @if(auth()->user()->activo)
                        <a href="/comunidades"     @click="open=false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700 {{ request()->is('comunidades') ? 'bg-teal-50 text-teal-700' : '' }}">Comunidades</a>
                        <a href="/poblacion"        @click="open=false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700 {{ request()->is('poblacion')   ? 'bg-teal-50 text-teal-700' : '' }}">Población</a>
                        <a href="/cai"              @click="open=false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700 {{ request()->is('cai')         ? 'bg-teal-50 text-teal-700' : '' }}">C.A.I.</a>
                        <a href="/causas-consulta"  @click="open=false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-rose-50  hover:text-rose-700  {{ request()->is('causas-consulta') ? 'bg-rose-50 text-rose-700' : '' }}">Causas de Consulta</a>
                    @endif
                    <a href="/blog" @click="open=false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700 {{ request()->is('blog*') ? 'bg-teal-50 text-teal-700' : '' }}">Blog</a>

                    <div class="my-1 border-t border-gray-100"></div>
                    @if(auth()->user()->activo && auth()->user()->hasAnyRole(['superadmin','admin','registrador']))
                        <a href="/admin" @click="open=false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Panel de administración</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-medium text-red-600 hover:bg-red-50">Cerrar sesión</button>
                    </form>
                @else
                    <a href="/blog"              @click="open=false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-teal-50">Blog</a>
                    <a href="{{ route('login') }}" @click="open=false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">Iniciar sesión</a>
                    @if(Route::has('register'))
                    <a href="{{ route('register') }}" @click="open=false" class="rounded-lg bg-teal-600 px-3 py-2.5 text-center text-sm font-medium text-white hover:bg-teal-700">Registrarse</a>
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
