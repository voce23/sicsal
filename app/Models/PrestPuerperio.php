<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestPuerperio extends Model
{
    protected $table = 'prest_puerperio';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio', 'tipo_control', 'cantidad',
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
