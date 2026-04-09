<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Selectores --}}
        <x-filament::section>
            <x-slot name="heading">Configuración de descarga</x-slot>
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Centro de Salud</label>
                    <select wire:model.live="centroSaludId"
                        class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="0">— Seleccionar —</option>
                        @foreach ($this->getCentros() as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Año</label>
                    <select wire:model.live="anio" class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @for ($y = date('Y'); $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Período CAI</label>
                    <select wire:model.live="periodo" class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="cai1">CAI 1 (Ene–Abr)</option>
                        <option value="cai2">CAI 2 (Ene–Ago)</option>
                        <option value="gestion">Gestión completa</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </x-filament::section>

        {{-- Informe CAI --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-bar-square class="size-5 text-teal-500" />
                    Informe C.A.I.
                </div>
            </x-slot>
            <x-slot name="description">Coberturas, vacunas, salud materna, servicios generales</x-slot>

            <div class="flex flex-wrap gap-3">
                {{ $this->descargarCaiExcelAction }}
                {{ $this->descargarCaiPdfAction }}
                {{ $this->descargarCaiPptxAction }}
            </div>
        </x-filament::section>

        {{-- 10 Principales Causas de Consulta Externa --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-document-magnifying-glass class="size-5 text-rose-500" />
                    10 Principales Causas de Consulta Externa
                </div>
            </x-slot>
            <x-slot name="description">Diagnósticos más frecuentes, desglosados por edad y sexo, con total parcial y porcentaje</x-slot>

            <div class="mb-4 flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Mes</label>
                    <select wire:model.live="mesCausas"
                        class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="0">— Gestión completa —</option>
                        @foreach ([1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'] as $num => $nombre)
                            <option value="{{ $num }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                {{ $this->descargarCausasExcelAction }}
                {{ $this->descargarCausasPdfAction }}
            </div>
        </x-filament::section>

        {{-- Padrón Comunal --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-clipboard-document-list class="size-5 text-amber-500" />
                    Padrón por Comunidad
                </div>
            </x-slot>
            <x-slot name="description">Listado alfabético de personas activas organizadas por comunidad — útil para reuniones comunitarias</x-slot>

            <div class="flex flex-wrap gap-3">
                {{ $this->descargarPadronExcelAction }}
            </div>
        </x-filament::section>

        {{-- Comunidades y Población --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-user-group class="size-5 text-cyan-500" />
                    Comunidades y Población
                </div>
            </x-slot>
            <x-slot name="description">Resumen poblacional por comunidad y grupo etáreo</x-slot>

            <div class="flex flex-wrap gap-3">
                {{ $this->descargarComunidadesExcelAction }}
                {{ $this->descargarComunidadesPdfAction }}
            </div>
        </x-filament::section>

        @if (! $centroSaludId)
            <x-filament::section>
                <div class="text-center text-sm text-warning-600 dark:text-warning-400">
                    Seleccione un Centro de Salud para habilitar las descargas.
                </div>
            </x-filament::section>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
