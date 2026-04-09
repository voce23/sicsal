<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'apellidos',
        'usuario',
        'email',
        'password',
        'centro_salud_id',
        'activo',
        'ultimo_acceso',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acceso' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->activo && $this->hasAnyRole(['superadmin', 'admin', 'registrador']);
    }

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->name} {$this->apellidos}";
    }

    public function initials(): string
    {
        $parts = array_filter([trim($this->name ?? ''), trim($this->apellidos ?? '')]);

        return collect($parts)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
