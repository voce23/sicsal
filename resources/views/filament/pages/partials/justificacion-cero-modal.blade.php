{{-- Modal de Justificación de cero --}}
<div x-data="{ open: false, indicador: '' }"
     x-on:abrir-justificacion-cero.window="open = true; indicador = $event.detail.indicador; $wire.abrirJustificacion($event.detail.indicador)"
     x-on:close-modal.window="if ($event.detail.id === 'justificacion-cero') open = false">

    <template x-if="open">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="mx-4 w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                <h3 class="text-lg font-semibold">Registrando cero en indicador</h3>
                <p class="mt-1 text-sm text-gray-500">¿Cuál es el motivo de no tener actividad este mes?</p>

                <div class="mt-4 space-y-2">
                    @php
                        $motivosOpciones = [
                            'no_hay_poblacion_activa_padron' => 'No hay población activa registrada en el padrón',
                            'poblacion_migrada_temporal' => 'La población registrada migró temporalmente',
                            'atendida_otro_centro' => 'Fue atendida en otro centro de salud',
                            'no_se_presento_razon_desconocida' => 'No se presentó a consulta (razón desconocida)',
                            'otro' => 'Otro motivo',
                        ];
                    @endphp
                    @foreach ($motivosOpciones as $value => $label)
                        <label class="flex cursor-pointer items-start gap-2 rounded border px-3 py-2 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700">
                            <input type="radio" wire:model="justMotivo" value="{{ $value }}" class="mt-0.5">
                            <span class="text-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div x-show="$wire.justMotivo === 'otro'" class="mt-3">
                    <input type="text" wire:model="justDetalle"
                           class="w-full rounded border px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700"
                           placeholder="Detalle del motivo..." maxlength="300">
                </div>

                @error('justMotivo')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror

                <div class="mt-4 flex justify-end">
                    <button wire:click="guardarJustificacion"
                            class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                        Confirmar y guardar
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
