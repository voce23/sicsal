<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestActividadComunidad extends Model
{
    protected $table = 'prest_actividades_comunidad';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio', 'tipo_actividad', 'cantidad',
    ];

    protected function casts(): array
    {
        return ['cantidad' => 'integer'];
    }

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }
}
