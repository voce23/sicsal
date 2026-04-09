<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Embarazo extends Model
{
    protected $fillable = [
        'persona_id', 'fecha_inicio', 'fecha_probable_parto',
        'semanas_gestacion_ingreso', 'estado', 'observaciones', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio'         => 'date',
            'fecha_probable_parto' => 'date',
            'activo'               => 'boolean',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function controles(): HasMany
    {
        return $this->hasMany(ControlPrenatal::class);
    }

    public function parto(): HasOne
    {
        return $this->hasOne(Parto::class);
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa');
    }
}
