<?php

namespace Database\Seeders;

use App\Models\CentroSalud;
use App\Models\Comunidad;
use Illuminate\Database\Seeder;

class ComunidadSeeder extends Seeder
{
    public function run(): void
    {
        $centro = CentroSalud::where('codigo_snis', '300183')->first();

        $comunidades = [
            ['nombre' => 'Hornoma',      'distancia_km' => 0],
            ['nombre' => 'Huaychoma',    'distancia_km' => 5],
            ['nombre' => 'Villcabamba',  'distancia_km' => 6],
            ['nombre' => 'Cocoma',       'distancia_km' => 24],
            ['nombre' => 'Tocohalla',    'distancia_km' => 48],
            ['nombre' => 'Challavilque', 'distancia_km' => 48],
            ['nombre' => 'Calacaja',     'distancia_km' => 72],
            ['nombre' => 'Siquimirani',  'distancia_km' => null],
        ];

        foreach ($comunidades as $datos) {
            Comunidad::firstOrCreate(
                ['centro_salud_id' => $centro->id, 'nombre' => $datos['nombre']],
                ['distancia_km' => $datos['distancia_km'], 'activo' => true]
            );
        }
    }
}
