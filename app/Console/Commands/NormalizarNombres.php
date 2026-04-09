<?php

namespace App\Console\Commands;

use App\Models\Persona;
use Illuminate\Console\Command;

class NormalizarNombres extends Command
{
    protected $signature   = 'sicsal:normalizar-nombres';
    protected $description = 'Convierte nombres y apellidos existentes a Title Case (Primera Letra Mayúscula)';

    public function handle(): int
    {
        $total      = Persona::count();
        $procesados = 0;

        $this->info("Normalizando {$total} personas...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Persona::chunk(200, function ($personas) use (&$procesados, $bar) {
            foreach ($personas as $persona) {
                $nuevoNombre   = mb_convert_case(mb_strtolower($persona->getRawOriginal('nombres')), MB_CASE_TITLE, 'UTF-8');
                $nuevoApellido = mb_convert_case(mb_strtolower($persona->getRawOriginal('apellidos')), MB_CASE_TITLE, 'UTF-8');

                if ($persona->getRawOriginal('nombres') !== $nuevoNombre
                    || $persona->getRawOriginal('apellidos') !== $nuevoApellido) {
                    $persona->timestamps = false;
                    $persona->updateQuietly([
                        'nombres'   => $nuevoNombre,
                        'apellidos' => $nuevoApellido,
                    ]);
                    $procesados++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Listo. {$procesados} registros actualizados.");

        return self::SUCCESS;
    }
}
