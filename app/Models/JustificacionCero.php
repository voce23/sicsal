<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JustificacionCero extends Model
{
    protected $table = 'justificaciones_cero';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio', 'indicador',
        'motivo', 'detalle', 'registrado_por',
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
