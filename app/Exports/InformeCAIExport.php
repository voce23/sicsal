<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InformeCAIExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private array $datos) {}

    public function sheets(): array
    {
        return [
            'Encabezado' => new Sheets\EncabezadoSheet($this->datos['encabezado'], $this->datos['meses_cerrados']),
            'Migración' => new Sheets\MigracionSheet($this->datos['migracion']),
            'Censo' => new Sheets\CensoSheet($this->datos['censo']),
            'Cobertura' => new Sheets\CoberturaSheet($this->datos['cobertura'], $this->datos['desercion']),
            'Prestaciones' => new Sheets\PrestacionesSheet($this->datos['prestaciones']),
            'Ceros Justific.' => new Sheets\CerosSheet($this->datos['ceros_justificados']),
            'Observaciones' => new Sheets\ObservacionesSheet($this->datos['observaciones']),
        ];
    }
}
