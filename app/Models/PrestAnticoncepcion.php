<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestAnticoncepcion extends Model
{
    protected $table = 'prest_anticoncepcion';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio', 'metodo',
        'tipo_usuaria', 'grupo_etareo', 'cantidad',
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
