<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestPrenatal extends Model
{
    protected $table = 'prest_prenatales';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio', 'tipo_control', 'grupo_etareo',
        'dentro', 'fuera',
    ];

    protected function casts(): array
    {
        return ['dentro' => 'integer', 'fuera' => 'integer'];
    }

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }
}
