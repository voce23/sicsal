<div class="space-y-6">
    {{-- Encabezado --}}
    <div class="flex items-center gap-3">
        <div class="flex size-10 items-center justify-center rounded-full bg-gradient-to-br from-teal-500 to-cyan-500 shadow-md">
            <svg class="size-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" /></svg>
        </div>
        <div>
            <h2 class="text-lg font-bold text-gray-900">Conversación</h2>
            <p class="text-xs text-gray-400">{{ $this->comentarios->count() }} {{ $this->comentarios->count() === 1 ? 'comentario' : 'comentarios' }}</p>
        </div>
    </div>

    {{-- Formulario --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        @if ($exito)
            <div class="mb-4 flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                <svg class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                ¡Gracias! Tu comentario se publicó correctamente.
            </div>
        @endif

        <form wire:submit="publicar" class="space-y-4">
            <div class="flex items-start gap-3">
                <div class="mt-1 flex size-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-bold text-gray-400">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0" /></svg>
                </div>
                <div class="min-w-0 flex-1 space-y-3">
                    <input wire:model="nombre" id="nombre-{{ $pagina }}" type="text" maxlength="100" placeholder="¿Cómo te llamas?"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-[15px] transition placeholder:text-gray-400 focus:border-teal-400 focus:bg-white focus:ring-2 focus:ring-teal-400/20" />
                    @error('nombre') <p class="-mt-1 text-xs text-red-500">{{ $message }}</p> @enderror

                    <textarea wire:model="contenido" id="contenido-{{ $pagina }}" rows="4" maxlength="2000" placeholder="Escribe tu comentario o sugerencia..."
                        class="w-full resize-none rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-[15px] transition placeholder:text-gray-400 focus:border-teal-400 focus:bg-white focus:ring-2 focus:ring-teal-400/20"></textarea>
                    @error('contenido') <p class="-mt-1 text-xs text-red-500">{{ $message }}</p> @enderror

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-700">
                                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                {{ $captchaA }} + {{ $captchaB }} = ?
                            </span>
                            <input wire:model="captchaRespuesta" type="number" placeholder="?"
                                class="w-16 rounded-lg border-gray-200 bg-gray-50 px-3 py-1.5 text-center text-sm font-semibold transition focus:border-teal-400 focus:bg-white focus:ring-2 focus:ring-teal-400/20" />
                            @error('captchaRespuesta') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-cyan-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-teal-700 hover:to-cyan-700 hover:shadow-md active:scale-[0.98]">
                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                            Publicar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Lista de comentarios --}}
    <div class="space-y-4">
        @forelse ($this->comentarios as $c)
            <div class="group flex gap-3">
                @php
                    $colores = ['bg-teal-500', 'bg-cyan-500', 'bg-blue-500', 'bg-violet-500', 'bg-rose-500', 'bg-amber-500', 'bg-emerald-500'];
                    $color = $colores[ord(mb_strtolower(mb_substr($c->nombre, 0, 1))) % count($colores)];
                @endphp
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $color }} text-sm font-bold text-white shadow-sm">
                    {{ mb_strtoupper(mb_substr($c->nombre, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="rounded-2xl rounded-tl-md border border-gray-100 bg-white px-5 py-4 shadow-sm transition group-hover:shadow-md">
                        <div class="mb-1.5 flex items-baseline gap-2">
                            <span class="text-[15px] font-semibold text-gray-900">{{ $c->nombre }}</span>
                            <span class="text-xs text-gray-400">{{ $c->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-[15px] leading-relaxed text-gray-600">{{ $c->contenido }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-8 text-center">
                <svg class="mx-auto size-12 text-gray-200" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" /></svg>
                <p class="mt-3 text-sm font-medium text-gray-400">Aún no hay comentarios</p>
                <p class="mt-1 text-xs text-gray-300">¡Sé el primero en compartir tu opinión!</p>
            </div>
        @endforelse
    </div>
</div>
