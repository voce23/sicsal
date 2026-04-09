<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Persona extends Model
{
    protected $fillable = [
        'centro_salud_id', 'comunidad_id', 'nombres', 'apellidos',
        'fecha_nacimiento', 'sexo', 'ci', 'tipo_seguro', 'estado',
        'destino_migracion', 'fecha_migracion', 'grupo_etareo',
        'fecha_registro', 'observaciones', 'activo', 'created_by',
        'verificado', 'fecha_verificacion', 'verificado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_migracion' => 'date',
            'fecha_registro' => 'date',
            'activo' => 'boolean',
            'verificado' => 'boolean',
            'fecha_verificacion' => 'date',
        ];
    }

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }

    public function comunidad(): BelongsTo
    {
        return $this->belongsTo(Comunidad::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }

    public function embarazos(): HasMany
    {
        return $this->hasMany(Embarazo::class);
    }

    public function vacunas(): HasMany
    {
        return $this->hasMany(VacunaNino::class);
    }

    public function crecimiento(): HasMany
    {
        return $this->hasMany(CrecimientoInfantil::class);
    }

    public function micronutrientes(): HasMany
    {
        return $this->hasMany(MicronutrienteNino::class);
    }

    public function anticoncepciones(): HasMany
    {
        return $this->hasMany(Anticoncepcion::class);
    }

    public function defuncion(): HasOne
    {
        return $this->hasOne(Defuncion::class);
    }

    // Mutators
    protected function nombres(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8'),
        );
    }

    protected function apellidos(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8'),
        );
    }

    // Accessors
    public function getEdadAttribute(): int
    {
        return Carbon::parse($this->fecha_nacimiento)->age;
    }

    public function getEdadMesesAttribute(): int
    {
        return Carbon::parse($this->fecha_nacimiento)->diffInMonths(now());
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    public function getGrupoEtareoCalculadoAttribute(): string
    {
        $meses = $this->edad_meses;
        $anios = $this->edad;

        if ($meses < 6) {
            return 'menor_6m';
        }
        if ($meses < 12) {
            return '6m_menor_1';
        }
        if ($anios < 5) {
            return '1_4';
        }
        if ($anios < 10) {
            return '5_9';
        }
        if ($anios < 15) {
            return '10_14';
        }
        if ($anios < 20) {
            return '15_19';
        }
        if ($anios < 40) {
            return '20_39';
        }
        if ($anios < 50) {
            return '40_49';
        }
        if ($anios < 60) {
            return '50_59';
        }

        return 'mayor_60';
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeResidentes($query)
    {
        return $query->where('estado', 'residente');
    }

    public function scopeMigrados($query)
    {
        return $query->where('estado', 'migrado');
    }

    public function scopeVerificados($query)
    {
        return $query->where('verificado', true);
    }

    public function scopeSinVerificar($query)
    {
        return $query->where('verificado', false);
    }

    /** Padrón activo verificado (excluye migrados, fallecidos y no verificados) */
    public function scopePadronActivo($query)
    {
        return $query->where('activo', true)
            ->whereIn('estado', ['residente', 'temporal'])
            ->where('verificado', true);
    }

    public function scopeMenoresDe5($query)
    {
        return $query->where('fecha_nacimiento', '>=', now()->subYears(5));
    }

    public function scopeMujeresMef($query)
    {
        return $query->where('sexo', 'F')
            ->where('fecha_nacimiento', '<=', now()->subYears(15))
            ->where('fecha_nacimiento', '>=', now()->subYears(49));
    }
}
