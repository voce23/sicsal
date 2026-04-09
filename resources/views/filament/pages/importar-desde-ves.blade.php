<x-filament-panels::page>

    {{-- ── Descripción ─────────────────────────────────────────────────────── --}}
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
        <div class="flex items-start gap-3">
            <x-heroicon-o-information-circle class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" />
            <div class="text-sm text-blue-800 dark:text-blue-200">
                <p class="font-semibold">¿Cómo funciona?</p>
                <ol class="mt-1 list-decimal pl-4 space-y-0.5">
                    <li>Cada centro de salud genera su archivo <strong>.ves</strong> desde el SNIS al cierre del mes.</li>
                    <li>Envía ese archivo por USB, correo o WhatsApp a la cabecera de sector.</li>
                    <li>En esta página suba el archivo y haga clic en <strong>Importar</strong>.</li>
                    <li>Los datos se consolidarán automáticamente en el SIMUES.</li>
                </ol>
            </div>
        </div>
    </div>

    {{-- ── Formulario ───────────────────────────────────────────────────────── --}}
    <div class="mt-6">
        {{ $this->form }}

        <div class="mt-4 flex gap-3">
            <x-filament::button
                wire:click="ejecutarImportacion"
                color="success"
                icon="heroicon-o-arrow-down-tray"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
                wire:target="ejecutarImportacion"
            >
                <span wire:loading.remove wire:target="ejecutarImportacion">Importar desde .ves</span>
                <span wire:loading wire:target="ejecutarImportacion">Importando, espere...</span>
            </x-filament::button>
        </div>
    </div>

    {{-- ── Resultado ────────────────────────────────────────────────────────── --}}
    @if ($resultado !== null)
        <div class="mt-6">
            @if ($resultado['exito'])
                {{-- Éxito --}}
                <div class="rounded-xl border border-green-200 bg-green-50 p-5 dark:border-green-800 dark:bg-green-900/20">
                    <div class="flex items-center gap-2 mb-4">
                        <x-heroicon-o-check-circle class="h-6 w-6 text-green-600 dark:text-green-400" />
                        <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">
                            Importación exitosa — {{ $resultado['total'] }} registros
                        </h3>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-5">
                        @foreach ($resultado['stats'] as $tipo => $cantidad)
                            @if ($cantidad > 0)
                                <div class="rounded-lg border border-green-200 bg-white p-3 text-center dark:border-green-700 dark:bg-green-950/40">
                                    <div class="text-2xl font-bold text-green-700 dark:text-green-300">
                                        {{ number_format($cantidad) }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                        {{ str_replace('_', ' ', ucfirst($tipo)) }}
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @else
                {{-- Error --}}
                <div class="rounded-xl border border-red-200 bg-red-50 p-5 dark:border-red-800 dark:bg-red-900/20">
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-o-x-circle class="h-6 w-6 text-red-600 dark:text-red-400" />
                        <h3 class="text-lg font-semibold text-red-800 dark:text-red-200">
                            Error en la importación
                        </h3>
                    </div>
                    <pre class="overflow-x-auto rounded bg-red-100 p-3 text-xs text-red-800 dark:bg-red-950/60 dark:text-red-200">{{ $resultado['output'] }}</pre>
                </div>
            @endif
        </div>
    @endif

    {{-- ── Estado de centros con código SNIS configurado ───────────────────── --}}
    <div class="mt-8">
        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">
            Centros de salud con código SNIS configurado
        </h3>
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Centro de Salud</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-400">Código SNIS</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-gray-600 dark:text-gray-400">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse (App\Models\CentroSalud::whereNotNull('codigo_snis')->where('activo', true)->orderBy('nombre')->get() as $centro)
                        <tr class="bg-white dark:bg-gray-900">
                            <td class="px-4 py-2 font-medium text-gray-800 dark:text-gray-200">
                                {{ $centro->nombre }}
                            </td>
                            <td class="px-4 py-2 text-center font-mono text-gray-600 dark:text-gray-400">
                                {{ $centro->codigo_snis }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                    <x-heroicon-m-check class="h-3 w-3" />
                                    Listo
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-400 dark:text-gray-500">
                                Ningún centro tiene código SNIS configurado. Configure el código en la sección
                                <a href="{{ route('filament.admin.resources.centro-saluds.index') }}" class="text-primary-600 underline">Centros de Salud</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-filament-panels::page>
