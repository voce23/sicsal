<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestConsultaExterna extends Model
{
    protected $table = 'prest_consulta_externa';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio', 'grupo_etareo',
        'primera_m', 'primera_f', 'nueva_m', 'nueva_f', 'repetida_m', 'repetida_f',
    ];

    protected function casts(): array
    {
        return [
            'primera_m' => 'integer', 'primera_f' => 'integer',
            'nueva_m' => 'integer', 'nueva_f' => 'integer',
            'repetida_m' => 'integer', 'repetida_f' => 'integer',
        ];
    }

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }
}
