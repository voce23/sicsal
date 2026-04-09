<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservacionMensual extends Model
{
    protected $table = 'observaciones_mensuales';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio', 'texto', 'registrado_por',
    ];

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
