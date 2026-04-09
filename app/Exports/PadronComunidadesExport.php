<?php

namespace App\Exports;

use App\Exports\Sheets\PadronComunidadSheet;
use App\Models\CentroSalud;
use App\Models\Comunidad;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PadronComunidadesExport implements WithMultipleSheets
{
    public function __construct(private int $centroSaludId) {}

    public function sheets(): array
    {
        $centro      = CentroSalud::find($this->centroSaludId);
        $comunidades = Comunidad::where('centro_salud_id', $this->centroSaludId)
            ->orderBy('nombre')
            ->get();

        $sheets = [];
        foreach ($comunidades as $comunidad) {
            $sheets[] = new PadronComunidadSheet($comunidad, $centro);
        }

        return $sheets;
    }
}
