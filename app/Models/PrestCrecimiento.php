<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestCrecimiento extends Model
{
    protected $table = 'prest_crecimiento';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio', 'grupo_etareo',
        'nuevos_m', 'nuevos_f', 'repetidos_m', 'repetidos_f',
    ];

    protected function casts(): array
    {
        return [
            'nuevos_m' => 'integer', 'nuevos_f' => 'integer',
            'repetidos_m' => 'integer', 'repetidos_f' => 'integer',
        ];
    }

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }
}
