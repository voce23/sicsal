<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestParto extends Model
{
    protected $table = 'prest_partos';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio', 'tipo', 'lugar',
        'atendido_por', 'grupo_etareo', 'cantidad',
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
