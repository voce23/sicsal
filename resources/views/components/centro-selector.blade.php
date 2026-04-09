{{-- Selector de centro de salud: visible solo para superadmin --}}
@if ($this->esSuperadmin && count($this->centrosDisponibles) > 0)
    <div class="flex flex-wrap items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-700 dark:bg-amber-900/20">
        <span class="text-sm font-medium text-amber-800 dark:text-amber-300">
            <x-heroicon-s-building-office class="mr-1 inline h-4 w-4" />
            Centro de Salud:
        </span>
        <select
            wire:model.live="centroSaludId"
            class="rounded-lg border-gray-300 bg-white px-3 py-1 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
        >
            <option value="0">— Seleccione un centro —</option>
            @foreach ($this->centrosDisponibles as $id => $nombre)
                <option value="{{ $id }}">{{ $nombre }}</option>
            @endforeach
        </select>
    </div>
@endif
