<?php

namespace App\Concerns;

use App\Models\CentroSalud;
use Livewire\Attributes\Url;

/**
 * Trait compartido para páginas que necesitan un selector de centro de salud.
 *
 * - Para admin/registrador: usa su centro asignado fijo.
 * - Para superadmin: muestra un selector con todos los centros activos.
 */
trait HasCentroSelector
{
    #[Url]
    public int $centroSaludId = 0;

    public function mountHasCentroSelector(): void
    {
        $user = auth()->user();

        if ($this->centroSaludId === 0) {
            $this->centroSaludId = $user?->centro_salud_id ?? 1;
        }

        // Forzar centro asignado para no-superadmins
        if ($user && ! $user->hasRole('superadmin') && $user->centro_salud_id) {
            $this->centroSaludId = (int) $user->centro_salud_id;
        }
    }

    /**
     * Re-valida el centro cuando cambia por URL o selector.
     * Evita que un no-superadmin vea datos de otro centro.
     */
    public function updatingCentroSaludId(int $value): void
    {
        $user = auth()->user();

        if ($user && ! $user->hasRole('superadmin') && $user->centro_salud_id) {
            $this->centroSaludId = (int) $user->centro_salud_id;
        }
    }

    public function getCentrosDisponiblesProperty(): array
    {
        $user = auth()->user();

        if (! $user || ! $user->hasRole('superadmin')) {
            return [];
        }

        return CentroSalud::where('activo', true)
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->toArray();
    }

    public function getEsSuperadminProperty(): bool
    {
        $user = auth()->user();

        return $user && $user->hasRole('superadmin');
    }

    protected function getCentroId(): int
    {
        return $this->centroSaludId;
    }
}
