<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Parto extends Model
{
    protected $fillable = [
        'embarazo_id', 'fecha_parto', 'tipo', 'lugar',
        'atendido_por', 'grupo_etareo', 'resultado', 'peso_rn_kg', 'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_parto' => 'date',
            'peso_rn_kg'  => 'decimal:3',
        ];
    }

    public function embarazo(): BelongsTo
    {
        return $this->belongsTo(Embarazo::class);
    }

    public function puerperio(): HasOne
    {
        return $this->hasOne(Puerperio::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
