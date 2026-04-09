<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            MunicipioSeeder::class,
            CentroSaludSeeder::class,
            ComunidadSeeder::class,
            MetaIneSeeder::class,
            UserSeeder::class,
        ]);
    }
}
