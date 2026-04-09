<?php

namespace Database\Seeders;

use App\Models\CentroSalud;
use App\Models\MetaIne;
use Illuminate\Database\Seeder;

class MetaIneSeeder extends Seeder
{
    public function run(): void
    {
        $centro = CentroSalud::where('codigo_snis', '300183')->first();

        $metas = [
            ['grupo_etareo' => 'menor_1',               'sexo' => 'ambos', 'cantidad' => 12],
            ['grupo_etareo' => '1_anio',                'sexo' => 'ambos', 'cantidad' => 12],
            ['grupo_etareo' => '2_anios',               'sexo' => 'ambos', 'cantidad' => 13],
            ['grupo_etareo' => '3_anios',               'sexo' => 'ambos', 'cantidad' => 13],
            ['grupo_etareo' => '4_anios',               'sexo' => 'ambos', 'cantidad' => 13],
            ['grupo_etareo' => '1_4',                   'sexo' => 'ambos', 'cantidad' => 50],
            ['grupo_etareo' => 'menor_5',               'sexo' => 'ambos', 'cantidad' => 62],
            ['grupo_etareo' => 'mayor_5',               'sexo' => 'ambos', 'cantidad' => 578],
            ['grupo_etareo' => '5_9',                   'sexo' => 'ambos', 'cantidad' => 67],
            ['grupo_etareo' => '10_14',                 'sexo' => 'ambos', 'cantidad' => 66],
            ['grupo_etareo' => '15_19',                 'sexo' => 'ambos', 'cantidad' => 66],
            ['grupo_etareo' => '20_39',                 'sexo' => 'ambos', 'cantidad' => 179],
            ['grupo_etareo' => '40_49',                 'sexo' => 'ambos', 'cantidad' => 63],
            ['grupo_etareo' => '50_59',                 'sexo' => 'ambos', 'cantidad' => 49],
            ['grupo_etareo' => 'mayor_60',              'sexo' => 'ambos', 'cantidad' => 88],
            ['grupo_etareo' => 'embarazos_esperados',   'sexo' => 'F',     'cantidad' => 15],
            ['grupo_etareo' => 'partos_esperados',      'sexo' => 'F',     'cantidad' => 12],
            ['grupo_etareo' => 'nacimientos_esperados', 'sexo' => 'ambos', 'cantidad' => 12],
            ['grupo_etareo' => 'adolescentes_10_19',    'sexo' => 'ambos', 'cantidad' => 132],
            ['grupo_etareo' => 'mujeres_menor_20',      'sexo' => 'F',     'cantidad' => 128],
            ['grupo_etareo' => '7_49',                  'sexo' => 'M',     'cantidad' => 209],
            ['grupo_etareo' => '7_49',                  'sexo' => 'F',     'cantidad' => 206],
            ['grupo_etareo' => 'mef_15_40',             'sexo' => 'F',     'cantidad' => 153],
            ['grupo_etareo' => 'dt_7_49',               'sexo' => 'M',     'cantidad' => 10],
            ['grupo_etareo' => 'dt_7_49',               'sexo' => 'F',     'cantidad' => 10],
        ];

        foreach ($metas as $meta) {
            MetaIne::firstOrCreate(
                [
                    'centro_salud_id' => $centro->id,
                    'anio'            => 2026,
                    'grupo_etareo'    => $meta['grupo_etareo'],
                    'sexo'            => $meta['sexo'],
                ],
                ['cantidad' => $meta['cantidad']]
            );
        }
    }
}
