<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestInternacion extends Model
{
    protected $table = 'prest_internaciones';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio', 'indicador',
        'cantidad',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
        ];
    }

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }
}
