<?php

namespace Database\Seeders;

use App\Models\Municipio;
use Illuminate\Database\Seeder;

class MunicipioSeeder extends Seeder
{
    public function run(): void
    {
        Municipio::firstOrCreate(
            ['nombre' => 'Capinota'],
            ['departamento' => 'Cochabamba', 'activo' => true]
        );
    }
}
