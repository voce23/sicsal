<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Defuncion extends Model
{
    protected $table = 'defunciones';

    protected $fillable = [
        'persona_id', 'centro_salud_id', 'nombres', 'apellidos',
        'fecha_nacimiento', 'sexo', 'comunidad_id', 'fecha_defuncion',
        'causa_defuncion', 'lugar', 'grupo_etareo_defuncion', 'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_defuncion' => 'date',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }

    public function comunidad(): BelongsTo
    {
        return $this->belongsTo(Comunidad::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    protected static function booted(): void
    {
        static::saving(function (Defuncion $defuncion) {
            // Calcular grupo etáreo automáticamente
            if ($defuncion->fecha_nacimiento && $defuncion->fecha_defuncion) {
                $defuncion->grupo_etareo_defuncion = self::calcularGrupoEtareo(
                    $defuncion->fecha_nacimiento,
                    $defuncion->fecha_defuncion
                );
            }

            // Asignar centro_salud_id desde el usuario autenticado si no está seteado
            if (! $defuncion->centro_salud_id) {
                $defuncion->centro_salud_id = auth()->user()?->centro_salud_id;
            }

            // Asignar registrado_por si no está seteado
            if (! $defuncion->registrado_por) {
                $defuncion->registrado_por = auth()->id();
            }
        });

        static::created(function (Defuncion $defuncion) {
            // Marcar la persona como inactiva en el padrón
            if ($defuncion->persona_id) {
                Persona::where('id', $defuncion->persona_id)
                    ->update(['activo' => false]);
            }
        });
    }

    public static function calcularGrupoEtareo(mixed $fechaNacimiento, mixed $fechaDefuncion): string
    {
        $nacimiento = Carbon::parse($fechaNacimiento);
        $defuncion = Carbon::parse($fechaDefuncion);
        $dias = $nacimiento->diffInDays($defuncion);
        $anios = $nacimiento->diffInYears($defuncion);

        if ($dias < 28) {
            return 'neonatal';
        }
        if ($dias < 365) {
            return 'infantil_menor_1';
        }
        if ($anios < 5) {
            return 'menor_5';
        }
        if ($anios < 60) {
            return '5_a_59';
        }

        return 'adulto_mayor';
    }
}
