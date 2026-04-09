<div>
    {{-- ── Hero ── --}}
    <section class="bg-linear-to-r from-teal-700 to-cyan-600 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
            <h1 class="text-3xl font-extrabold sm:text-4xl">Blog de Salud</h1>
            <p class="mt-2 text-teal-100">Noticias, artículos y recursos sobre salud comunitaria en Capinota</p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <div class="flex flex-col gap-8 lg:flex-row">

            {{-- ── Contenido principal ── --}}
            <main class="flex-1 min-w-0">

                {{-- Barra de filtros + toggle vista --}}
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3">

                    {{-- Categorías pill --}}
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="setCategoria('')"
                            class="rounded-full px-3 py-1 text-xs font-semibold transition
                                {{ $categoria === '' ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-teal-50' }}">
                            Todos
                        </button>
                        @foreach(\App\Models\Post::CATEGORIAS as $key => $label)
                            <button wire:click="setCategoria('{{ $key }}')"
                                class="rounded-full px-3 py-1 text-xs font-semibold transition
                                    {{ $categoria === $key ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-teal-50' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Toggle rejilla / lista --}}
                    <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-white p-1">
                        <button wire:click="setVista('rejilla')" title="Rejilla"
                            class="rounded p-1.5 transition {{ $vista === 'rejilla' ? 'bg-teal-600 text-white' : 'text-gray-400 hover:text-teal-600' }}">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                        </button>
                        <button wire:click="setVista('lista')" title="Lista"
                            class="rounded p-1.5 transition {{ $vista === 'lista' ? 'bg-teal-600 text-white' : 'text-gray-400 hover:text-teal-600' }}">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Etiqueta activa --}}
                @if($etiqueta)
                    <div class="mb-4 flex items-center gap-2">
                        <span class="text-sm text-gray-500">Etiqueta:</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-3 py-0.5 text-xs font-medium text-teal-800">
                            {{ $etiqueta }}
                            <button wire:click="setEtiqueta('{{ $etiqueta }}')" class="ml-1 text-teal-600 hover:text-teal-900">&times;</button>
                        </span>
                    </div>
                @endif

                {{-- Sin resultados --}}
                @if($posts->isEmpty())
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
                        <svg class="mx-auto size-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="mt-4 text-gray-500">No se encontraron artículos con los filtros seleccionados.</p>
                        <button wire:click="$set('busqueda', ''); $set('categoria', ''); $set('etiqueta', '')"
                            class="mt-3 text-sm text-teal-600 hover:underline">Limpiar filtros</button>
                    </div>

                {{-- REJILLA --}}
                @elseif($vista === 'rejilla')
                    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($posts as $post)
                            <article class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">

                                {{-- Imagen --}}
                                <a href="{{ route('blog.post', $post->slug) }}" class="block aspect-video overflow-hidden">
                                    @if($post->imagen_portada)
                                        <img src="{{ asset('storage/' . $post->imagen_portada) }}"
                                            alt="{{ $post->titulo }}"
                                            class="size-full object-cover transition duration-300 group-hover:scale-105">
                                    @else
                                        <div class="flex size-full items-center justify-center bg-linear-to-br {{ $post->gradientePortada() }}">
                                            <svg class="size-12 text-teal-400 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </a>

                                <div class="flex flex-1 flex-col p-4">
                                    {{-- Categoría + tiempo lectura --}}
                                    <div class="mb-2 flex items-center gap-2">
                                        <span class="rounded-full {{ $post->colorCategoria() }} px-2 py-0.5 text-xs font-semibold text-white">
                                            {{ $post->labelCategoria() }}
                                        </span>
                                        <span class="text-xs text-gray-400">{{ $post->tiempoLectura() }} min lectura</span>
                                    </div>

                                    {{-- Título --}}
                                    <h2 class="flex-1 text-base font-bold leading-snug text-gray-800 group-hover:text-teal-700">
                                        <a href="{{ route('blog.post', $post->slug) }}">{{ $post->titulo }}</a>
                                    </h2>

                                    {{-- Extracto --}}
                                    @if($post->extracto)
                                        <p class="mt-2 line-clamp-2 text-sm text-gray-500">{{ $post->extracto }}</p>
                                    @endif

                                    {{-- Footer --}}
                                    <div class="mt-3 flex items-center justify-between text-xs text-gray-400">
                                        <span>{{ $post->autor_nombre }}</span>
                                        <span>{{ $post->publicado_at?->format('d M Y') }}</span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                {{-- LISTA --}}
                @else
                    <div class="space-y-4">
                        @foreach($posts as $post)
                            <article class="group flex gap-4 overflow-hidden rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md">

                                {{-- Miniatura --}}
                                <a href="{{ route('blog.post', $post->slug) }}" class="shrink-0">
                                    @if($post->imagen_portada)
                                        <img src="{{ asset('storage/' . $post->imagen_portada) }}"
                                            alt="{{ $post->titulo }}"
                                            class="size-24 rounded-lg object-cover sm:size-32">
                                    @else
                                        <div class="flex size-24 items-center justify-center rounded-lg bg-linear-to-br {{ $post->gradientePortada() }} sm:size-32">
                                            <svg class="size-8 text-teal-400 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </a>

                                <div class="flex-1 min-w-0">
                                    <div class="mb-1 flex flex-wrap items-center gap-2">
                                        <span class="rounded-full {{ $post->colorCategoria() }} px-2 py-0.5 text-xs font-semibold text-white">
                                            {{ $post->labelCategoria() }}
                                        </span>
                                        <span class="text-xs text-gray-400">{{ $post->tiempoLectura() }} min lectura</span>
                                        <span class="text-xs text-gray-400">{{ $post->publicado_at?->format('d M Y') }}</span>
                                    </div>
                                    <h2 class="font-bold text-gray-800 group-hover:text-teal-700">
                                        <a href="{{ route('blog.post', $post->slug) }}">{{ $post->titulo }}</a>
                                    </h2>
                                    @if($post->extracto)
                                        <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ $post->extracto }}</p>
                                    @endif
                                    <div class="mt-2 flex items-center gap-3 text-xs text-gray-400">
                                        <span>{{ $post->autor_nombre }}</span>
                                        <span>{{ number_format($post->vistas) }} vistas</span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif

                {{-- Paginación --}}
                @if($posts->hasPages())
                    <div class="mt-8">
                        {{ $posts->links() }}
                    </div>
                @endif

            </main>

            {{-- ── Barra lateral ── --}}
            <aside class="w-full lg:w-72 shrink-0 space-y-5">

                {{-- Búsqueda --}}
                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <div class="border-b border-gray-100 bg-linear-to-r from-teal-600 to-cyan-500 px-4 py-3">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-white">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Buscar
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="relative">
                            <input type="search" wire:model.live.debounce.400ms="busqueda"
                                placeholder="Buscar artículos..."
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-9 pr-3 text-sm transition focus:border-teal-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-100">
                            <svg class="absolute left-3 top-3 size-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Categorías con conteo --}}
                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <div class="border-b border-gray-100 bg-linear-to-r from-teal-600 to-cyan-500 px-4 py-3">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-white">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            Categorías
                        </h3>
                    </div>
                    <ul class="divide-y divide-gray-50 p-2">
                        @foreach(\App\Models\Post::CATEGORIAS as $key => $label)
                            <li>
                                <button wire:click="setCategoria('{{ $key }}')"
                                    class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm transition
                                        {{ $categoria === $key
                                            ? 'bg-teal-50 font-semibold text-teal-700'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-teal-600' }}">
                                    <span class="flex items-center gap-2">
                                        @if($categoria === $key)
                                            <span class="size-1.5 rounded-full bg-teal-500"></span>
                                        @else
                                            <span class="size-1.5 rounded-full bg-gray-300"></span>
                                        @endif
                                        {{ $label }}
                                    </span>
                                    <span class="rounded-full {{ $categoria === $key ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-500' }} px-2 py-0.5 text-xs font-medium">
                                        {{ $categorias[$key] ?? 0 }}
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Artículos recientes --}}
                @if($recientes->isNotEmpty())
                    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                        <div class="border-b border-gray-100 bg-linear-to-r from-teal-600 to-cyan-500 px-4 py-3">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-white">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Artículos recientes
                            </h3>
                        </div>
                        <ul class="divide-y divide-gray-50 p-3">
                            @foreach($recientes as $r)
                                <li class="flex gap-3 py-2.5 first:pt-0 last:pb-0">
                                    @if($r->imagen_portada)
                                        <img src="{{ asset('storage/' . $r->imagen_portada) }}"
                                            alt="{{ $r->titulo }}"
                                            class="size-14 shrink-0 rounded-xl object-cover shadow-sm">
                                    @else
                                        <div class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-teal-50 to-cyan-100">
                                            <svg class="size-6 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('blog.post', $r->slug) }}"
                                            class="line-clamp-2 text-sm font-semibold leading-snug text-gray-700 hover:text-teal-700 transition">
                                            {{ $r->titulo }}
                                        </a>
                                        <p class="mt-1 flex items-center gap-1 text-xs text-gray-400">
                                            <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            {{ $r->publicado_at?->format('d M Y') }}
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Nube de etiquetas --}}
                @if($todasEtiquetas->isNotEmpty())
                    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                        <div class="border-b border-gray-100 bg-linear-to-r from-teal-600 to-cyan-500 px-4 py-3">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-white">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                </svg>
                                Etiquetas
                            </h3>
                        </div>
                        <div class="flex flex-wrap gap-2 p-4">
                            @foreach($todasEtiquetas as $tag => $count)
                                <button wire:click="setEtiqueta('{{ $tag }}')"
                                    class="rounded-full border px-3 py-1 text-xs font-medium transition
                                        {{ $etiqueta === $tag
                                            ? 'border-teal-500 bg-teal-500 text-white shadow-sm'
                                            : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700' }}">
                                    {{ $tag }}
                                    <span class="ml-0.5 opacity-70">({{ $count }})</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

            </aside>
        </div>
    </div>
</div>
