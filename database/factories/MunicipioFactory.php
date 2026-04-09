<?php

namespace Database\Factories;

use App\Models\Municipio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Municipio>
 */
class MunicipioFactory extends Factory
{
    protected $model = Municipio::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->city(),
            'departamento' => 'Cochabamba',
            'activo' => true,
        ];
    }
}
