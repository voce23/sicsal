<?php

namespace Database\Seeders;

use App\Models\CentroSalud;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $centro = CentroSalud::where('codigo_snis', '300183')->first();

        // Superadmin
        $superadmin = User::firstOrCreate(
            ['usuario' => 'superadmin'],
            [
                'name' => 'Administrador',
                'apellidos' => 'Sistema',
                'email' => 'admin@sicsal.bo',
                'password' => Hash::make(env('SEED_SUPERADMIN_PASS', 'Cambiar123!')),
                'centro_salud_id' => null,
                'activo' => true,
            ]
        );
        $superadmin->assignRole('superadmin');

        // Admin del C.S.A. HORNOMA
        $admin = User::firstOrCreate(
            ['usuario' => 'medico.hornoma'],
            [
                'name' => 'Eusebio',
                'apellidos' => 'Panozo Franco',
                'email' => 'medico@hornoma.bo',
                'password' => Hash::make(env('SEED_ADMIN_PASS', 'Cambiar123!')),
                'centro_salud_id' => $centro->id,
                'activo' => true,
            ]
        );
        $admin->assignRole('admin');

        // Registrador del C.S.A. HORNOMA
        $registrador = User::firstOrCreate(
            ['usuario' => 'registrador.hornoma'],
            [
                'name' => 'María',
                'apellidos' => 'Quispe Mamani',
                'email' => 'registrador@hornoma.bo',
                'password' => Hash::make(env('SEED_REGISTRADOR_PASS', 'Cambiar123!')),
                'centro_salud_id' => $centro->id,
                'activo' => true,
            ]
        );
        $registrador->assignRole('registrador');
    }
}
