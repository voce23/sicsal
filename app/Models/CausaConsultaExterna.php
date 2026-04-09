<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CausaConsultaExterna extends Model
{
    protected $table = 'causas_consulta_externa';

    protected $fillable = [
        'centro_salud_id',
        'mes',
        'anio',
        'posicion',
        'diagnostico',
        'grupo_etareo',
        'masculino',
        'femenino',
    ];

    protected $casts = [
        'mes' => 'integer',
        'anio' => 'integer',
        'masculino' => 'integer',
        'femenino' => 'integer',
    ];

    public static array $grupos = [
        'menor_6m' => '< 6 meses',
        '6m_menor_1' => '6m – 1 año',
        '1_4' => '1 – 4 años',
        '5_9' => '5 – 9 años',
        '10_14' => '10 – 14 años',
        '15_19' => '15 – 19 años',
        '20_39' => '20 – 39 años',
        '40_49' => '40 – 49 años',
        '50_59' => '50 – 59 años',
        'mayor_60' => '≥ 60 años',
    ];

    public static array $meses = [
        1 => 'Enero',    2 => 'Febrero', 3 => 'Marzo',     4 => 'Abril',
        5 => 'Mayo',     6 => 'Junio',   7 => 'Julio',     8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }
}
