<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrecimientoInfantil extends Model
{
    protected $table = 'crecimiento_infantil';

    protected $fillable = [
        'persona_id', 'fecha', 'peso_kg', 'talla_cm',
        'perimetro_cefalico_cm', 'clasificacion', 'tipo_control',
        'dentro_fuera', 'mes', 'anio', 'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'peso_kg' => 'decimal:2',
            'talla_cm' => 'decimal:2',
            'perimetro_cefalico_cm' => 'decimal:2',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
