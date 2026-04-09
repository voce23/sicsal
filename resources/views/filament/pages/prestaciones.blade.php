<x-filament-panels::page>
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
        @foreach ($meses as $mes)
            <div class="relative flex flex-col rounded-xl border p-4 transition
                      @if ($mes['estado'] === 'cerrado') border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-900/20
                      @elseif ($mes['estado'] === 'parcial') border-yellow-300 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-900/20
                      @else border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800
                      @endif">

                @if ($mes['cerrado'])
                    <x-heroicon-s-lock-closed class="absolute right-2 top-2 h-4 w-4 text-green-600 dark:text-green-400" />
                @endif

                <div class="mb-3 text-center">
                    <span class="text-2xl font-bold
                        @if ($mes['estado'] === 'cerrado') text-green-700 dark:text-green-300
                        @elseif ($mes['estado'] === 'parcial') text-yellow-700 dark:text-yellow-300
                        @else text-gray-400 dark:text-gray-500
                        @endif">
                        {{ str_pad($mes['numero'], 2, '0', STR_PAD_LEFT) }}
                    </span>

                    <div class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $mes['nombre'] }}
                    </div>

                    <div class="mt-1 text-xs
                        @if ($mes['estado'] === 'cerrado') text-green-600 dark:text-green-400
                        @elseif ($mes['estado'] === 'parcial') text-yellow-600 dark:text-yellow-400
                        @else text-gray-400 dark:text-gray-500
                        @endif">
                        @if ($mes['estado'] === 'cerrado') Cerrado
                        @elseif ($mes['estado'] === 'parcial') Parcial
                        @else Sin datos
                        @endif
                    </div>
                </div>

                <div class="mt-auto flex flex-col gap-1">
                    @foreach ($mes['urls'] as $link)
                        <a href="{{ $link['url'] }}"
                           class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-100 hover:text-primary-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-primary-400">
                            <x-dynamic-component :component="$link['icon']" class="h-4 w-4" />
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
