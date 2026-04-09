<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestReferencia extends Model
{
    protected $table = 'prest_referencias';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio', 'tipo',
        'masculino', 'femenino',
    ];

    protected function casts(): array
    {
        return [
            'masculino' => 'integer',
            'femenino' => 'integer',
        ];
    }

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }
}
