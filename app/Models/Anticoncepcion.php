<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anticoncepcion extends Model
{
    protected $table = 'anticoncepcion';

    protected $fillable = [
        'persona_id', 'metodo', 'tipo_usuaria', 'fecha',
        'mes', 'anio', 'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
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
