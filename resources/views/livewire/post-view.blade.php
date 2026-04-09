<div>
    {{-- ── Hero ── --}}
    <section class="relative overflow-hidden
        {{ $post->imagen_portada ? '' : 'bg-linear-to-br ' . $post->gradientePortada() }}">

        @if($post->imagen_portada)
            <div class="absolute inset-0">
                <img src="{{ asset('storage/' . $post->imagen_portada) }}"
                    alt="{{ $post->titulo }}"
                    class="size-full object-cover">
                <div class="absolute inset-0 bg-black/55"></div>
            </div>
        @endif

        <div class="relative mx-auto max-w-4xl px-4 py-14 sm:px-6">
            {{-- Breadcrumb --}}
            <nav class="mb-4 flex items-center gap-2 text-sm {{ $post->imagen_portada ? 'text-white/80' : 'text-teal-700' }}">
                <a href="{{ route('blog') }}" class="hover:underline">Blog</a>
                <span>/</span>
                <a href="{{ route('blog') }}?cat={{ $post->categoria }}" class="hover:underline">{{ $post->labelCategoria() }}</a>
            </nav>

            {{-- Título --}}
            <h1 class="text-2xl font-extrabold leading-tight sm:text-4xl
                {{ $post->imagen_portada ? 'text-white' : 'text-gray-800' }}">
                {{ $post->titulo }}
            </h1>

            {{-- Meta --}}
            <div class="mt-4 flex flex-wrap items-center gap-4 text-sm
                {{ $post->imagen_portada ? 'text-white/80' : 'text-gray-500' }}">
                <span class="rounded-full {{ $post->colorCategoria() }} px-3 py-1 text-xs font-semibold text-white">
                    {{ $post->labelCategoria() }}
                </span>
                <span>{{ $post->autor_nombre }}</span>
                <span>{{ $post->publicado_at?->format('d \d\e F \d\e Y') }}</span>
                <span>{{ $post->tiempoLectura() }} min de lectura</span>
                <span>{{ number_format($post->vistas) }} vistas</span>
            </div>

            {{-- Extracto --}}
            @if($post->extracto)
                <p class="mt-4 max-w-2xl text-base leading-relaxed
                    {{ $post->imagen_portada ? 'text-white/90' : 'text-gray-600' }}">
                    {{ $post->extracto }}
                </p>
            @endif
        </div>
    </section>

    {{-- ── Cuerpo ── --}}
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <div class="flex flex-col gap-10 lg:flex-row">

            {{-- Artículo --}}
            <article class="min-w-0 flex-1">

                {{-- Contenido HTML --}}
                @if($post->contenido)
                    <div class="prose prose-teal max-w-none
                        prose-headings:font-bold prose-headings:text-gray-800
                        prose-a:text-teal-600 prose-a:underline
                        prose-img:rounded-xl prose-img:shadow-sm
                        prose-blockquote:border-teal-500 prose-blockquote:bg-teal-50 prose-blockquote:px-4 prose-blockquote:py-2 prose-blockquote:rounded-r-lg
                        prose-code:rounded prose-code:bg-gray-100 prose-code:px-1 prose-code:text-sm
                        prose-pre:rounded-xl prose-pre:bg-gray-900 prose-pre:text-gray-100">
                        {!! $post->contenido !!}
                    </div>
                @else
                    <p class="text-gray-400 italic">Este artículo no tiene contenido aún.</p>
                @endif

                {{-- Etiquetas --}}
                @if($post->etiquetas && count($post->etiquetas))
                    <div class="mt-8 flex flex-wrap items-center gap-2 border-t border-gray-200 pt-6">
                        <span class="text-sm font-medium text-gray-500">Etiquetas:</span>
                        @foreach($post->etiquetas as $tag)
                            <a href="{{ route('blog') }}?tag={{ urlencode($tag) }}"
                                class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600 hover:bg-teal-50 hover:text-teal-700 transition">
                                {{ $tag }}
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Compartir / Volver --}}
                <div class="mt-8 flex items-center gap-4 border-t border-gray-200 pt-6">
                    <a href="{{ route('blog') }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 transition hover:border-teal-500 hover:text-teal-700">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                        </svg>
                        Volver al blog
                    </a>
                </div>

                {{-- Comentarios --}}
                <div class="mt-10">
                    <livewire:comentarios pagina="blog-{{ $post->slug }}" />
                </div>

            </article>

            {{-- Sidebar --}}
            <aside class="w-full lg:w-72 shrink-0 space-y-5">

                {{-- Info del artículo --}}
                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <div class="bg-linear-to-r from-teal-600 to-cyan-500 px-4 py-3">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-white">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Sobre este artículo
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-50 px-4 py-2">
                        <div class="flex items-center justify-between py-2.5">
                            <span class="text-xs text-gray-500">Categoría</span>
                            <a href="{{ route('blog') }}?cat={{ $post->categoria }}"
                                class="rounded-full {{ $post->colorCategoria() }} px-2.5 py-0.5 text-xs font-semibold text-white hover:opacity-80 transition">
                                {{ $post->labelCategoria() }}
                            </a>
                        </div>
                        <div class="flex items-center justify-between py-2.5">
                            <span class="text-xs text-gray-500">Autor</span>
                            <span class="text-xs font-medium text-gray-700">{{ $post->autor_nombre }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2.5">
                            <span class="text-xs text-gray-500">Publicado</span>
                            <span class="text-xs font-medium text-gray-700">{{ $post->publicado_at?->format('d M Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2.5">
                            <span class="text-xs text-gray-500">Lectura</span>
                            <span class="text-xs font-medium text-gray-700">{{ $post->tiempoLectura() }} min</span>
                        </div>
                        <div class="flex items-center justify-between py-2.5">
                            <span class="text-xs text-gray-500">Vistas</span>
                            <span class="text-xs font-medium text-gray-700">{{ number_format($post->vistas) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Artículos relacionados --}}
                @if($relacionados->isNotEmpty())
                    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                        <div class="bg-linear-to-r from-teal-600 to-cyan-500 px-4 py-3">
                            <h3 class="flex items-center gap-2 text-sm font-bold text-white">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                </svg>
                                Artículos relacionados
                            </h3>
                        </div>
                        <ul class="divide-y divide-gray-50 p-3">
                            @foreach($relacionados as $r)
                                <li class="flex gap-3 py-3 first:pt-0 last:pb-0">
                                    @if($r->imagen_portada)
                                        <img src="{{ asset('storage/' . $r->imagen_portada) }}"
                                            alt="{{ $r->titulo }}"
                                            class="size-16 shrink-0 rounded-xl object-cover shadow-sm">
                                    @else
                                        <div class="flex size-16 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-teal-50 to-cyan-100">
                                            <svg class="size-7 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('blog.post', $r->slug) }}"
                                            class="line-clamp-2 text-sm font-semibold leading-snug text-gray-700 hover:text-teal-700 transition">
                                            {{ $r->titulo }}
                                        </a>
                                        @if($r->extracto)
                                            <p class="mt-0.5 line-clamp-1 text-xs text-gray-400">{{ $r->extracto }}</p>
                                        @endif
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

                {{-- Volver --}}
                <a href="{{ route('blog') }}"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-600 shadow-sm transition hover:border-teal-400 hover:text-teal-700">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    Volver al blog
                </a>

            </aside>
        </div>
    </div>
</div>
