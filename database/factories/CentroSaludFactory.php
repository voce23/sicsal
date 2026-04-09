<?php

namespace Database\Factories;

use App\Models\CentroSalud;
use App\Models\Municipio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CentroSalud>
 */
class CentroSaludFactory extends Factory
{
    protected $model = CentroSalud::class;

    public function definition(): array
    {
        return [
            'municipio_id'  => Municipio::factory(),
            'nombre'        => fake()->company(),
            'codigo_snis'   => null,
            'subsector'     => 'Público',
            'red_salud'     => null,
            'poblacion_ine' => 0,
            'activo'        => true,
        ];
    }
}
