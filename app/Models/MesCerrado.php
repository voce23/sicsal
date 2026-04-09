<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MesCerrado extends Model
{
    protected $table = 'meses_cerrados';

    protected $fillable = [
        'centro_salud_id', 'mes', 'anio',
        'cerrado_por', 'fecha_cierre',
        'reabierto_por', 'fecha_reapertura',
    ];

    protected function casts(): array
    {
        return [
            'fecha_cierre' => 'datetime',
            'fecha_reapertura' => 'datetime',
        ];
    }

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }

    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    public function reabiertoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reabierto_por');
    }
}
