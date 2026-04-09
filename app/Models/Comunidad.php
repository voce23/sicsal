<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comunidad extends Model
{
    protected $table = 'comunidades';

    protected $fillable = [
        'centro_salud_id', 'nombre', 'distancia_km',
        'latitud', 'longitud', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo'       => 'boolean',
            'distancia_km' => 'decimal:2',
            'latitud'      => 'decimal:7',
            'longitud'     => 'decimal:7',
        ];
    }

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class);
    }
}
