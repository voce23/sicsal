<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestMicronutriente extends Model
{
    protected $table = 'prest_micronutrientes';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio', 'tipo', 'cantidad',
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
