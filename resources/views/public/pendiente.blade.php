<x-layouts.public>
    <x-slot:title>Cuenta pendiente de aprobación — SIMUES</x-slot:title>

    <div class="flex min-h-[60vh] items-center justify-center px-4 py-16">
        <div class="mx-auto max-w-md text-center">
            {{-- Ícono --}}
            <div class="mb-6 flex justify-center">
                <span class="flex size-20 items-center justify-center rounded-full bg-amber-100 text-5xl">
                    &#x23F3;
                </span>
            </div>

            <h1 class="mb-3 text-2xl font-bold text-gray-900">Solicitud recibida</h1>
            <p class="mb-6 text-gray-600">
                Tu cuenta ha sido creada, pero necesita ser <strong>aprobada por el administrador</strong>
                antes de que puedas acceder al sistema.<br><br>
                Recibirás acceso una vez que el responsable de tu centro de salud active tu cuenta.
            </p>

            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                Si ya pasaron varios días y aún no tienes acceso, contacta al
                administrador de tu Red de Salud.
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100"
                >
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</x-layouts.public>
