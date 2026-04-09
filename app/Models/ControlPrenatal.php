<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlPrenatal extends Model
{
    protected $table = 'controles_prenatales';

    protected $fillable = [
        'embarazo_id', 'numero_control', 'fecha',
        'semanas_gestacion', 'dentro_fuera', 'grupo_etareo', 'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function embarazo(): BelongsTo
    {
        return $this->belongsTo(Embarazo::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
