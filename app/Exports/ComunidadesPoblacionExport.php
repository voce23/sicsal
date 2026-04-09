<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ComunidadesPoblacionExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private array $datos) {}

    public function sheets(): array
    {
        return [
            'Resumen' => new Sheets\ComunidadesResumenSheet($this->datos),
            'Detalle Sexo' => new Sheets\ComunidadesDetalleSheet($this->datos),
            'INE vs Real' => new Sheets\ComunidadesConsolidadoSheet($this->datos),
        ];
    }
}
