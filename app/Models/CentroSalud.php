<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CentroSalud extends Model
{
    protected $table = 'centros_salud';

    protected $fillable = [
        'municipio_id', 'nombre', 'codigo_snis',
        'subsector', 'red_salud', 'poblacion_ine', 'activo',
        'latitud', 'longitud',
    ];

    protected function casts(): array
    {
        return [
            'activo'        => 'boolean',
            'poblacion_ine' => 'integer',
            'latitud'       => 'float',
            'longitud'      => 'float',
        ];
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function comunidades(): HasMany
    {
        return $this->hasMany(Comunidad::class);
    }

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class);
    }

    public function metasIne(): HasMany
    {
        return $this->hasMany(MetaIne::class);
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
