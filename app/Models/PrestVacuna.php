<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestVacuna extends Model
{
    protected $table = 'prest_vacunas';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio', 'tipo_vacuna', 'grupo_etareo',
        'dentro_m', 'dentro_f', 'fuera_m', 'fuera_f',
    ];

    protected function casts(): array
    {
        return [
            'dentro_m' => 'integer', 'dentro_f' => 'integer',
            'fuera_m' => 'integer', 'fuera_f' => 'integer',
        ];
    }

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }
}
