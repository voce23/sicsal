<x-layouts::auth :title="__('Crear cuenta')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Crear cuenta')"
            :description="__('Completa tus datos para solicitar acceso al sistema')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            {{-- Nombres --}}
            <flux:input
                name="name"
                :label="__('Nombres')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="given-name"
                placeholder="Ej. María Elena"
            />

            {{-- Apellidos --}}
            <flux:input
                name="apellidos"
                :label="__('Apellidos')"
                :value="old('apellidos')"
                type="text"
                required
                autocomplete="family-name"
                placeholder="Ej. Gutierrez Flores"
            />

            {{-- Correo --}}
            <flux:input
                name="email"
                :label="__('Correo electrónico')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="correo@ejemplo.com"
            />

            {{-- Centro de salud --}}
            <div class="flex flex-col gap-1.5">
                <flux:label for="centro_salud_id">{{ __('Centro de Salud') }}</flux:label>
                <select
                    id="centro_salud_id"
                    name="centro_salud_id"
                    required
                    class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">— Selecciona tu centro —</option>
                    @foreach(\App\Models\CentroSalud::orderBy('nombre')->get() as $centro)
                        <option value="{{ $centro->id }}" {{ old('centro_salud_id') == $centro->id ? 'selected' : '' }}>
                            {{ $centro->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('centro_salud_id')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Contraseña --}}
            <flux:input
                name="password"
                :label="__('Contraseña')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Contraseña')"
                viewable
            />

            {{-- Confirmar contraseña --}}
            <flux:input
                name="password_confirmation"
                :label="__('Confirmar contraseña')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirmar contraseña')"
                viewable
            />

            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Solicitar acceso') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('¿Ya tienes cuenta?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Iniciar sesión') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
