<?php

namespace Database\Seeders;

use App\Models\CentroSalud;
use App\Models\Municipio;
use Illuminate\Database\Seeder;

class CentroSaludSeeder extends Seeder
{
    public function run(): void
    {
        $municipio = Municipio::where('nombre', 'Capinota')->first();

        CentroSalud::firstOrCreate(
            ['codigo_snis' => '300183'],
            [
                'municipio_id'  => $municipio->id,
                'nombre'        => 'C.S.A. HORNOMA',
                'subsector'     => 'Público',
                'red_salud'     => 'Capinota',
                'poblacion_ine' => 641,
                'activo'        => true,
            ]
        );
    }
}
