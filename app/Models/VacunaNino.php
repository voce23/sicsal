<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacunaNino extends Model
{
    protected $table = 'vacunas_ninos';

    protected $fillable = [
        'persona_id', 'tipo_vacuna', 'fecha_aplicacion',
        'dentro_fuera', 'lote', 'aplicado_por', 'mes', 'anio',
    ];

    protected function casts(): array
    {
        return [
            'fecha_aplicacion' => 'date',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function aplicadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aplicado_por');
    }
}
