<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestOdontologia extends Model
{
    protected $table = 'prest_odontologia';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio', 'procedimiento', 'grupo_etareo',
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
