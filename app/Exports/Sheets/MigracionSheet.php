<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\EstiloExcel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class MigracionSheet implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    use EstiloExcel;

    public function __construct(private array $migracion) {}

    public function title(): string
    {
        return 'Migración';
    }

    public function columnWidths(): array
    {
        return ['A' => 35, 'B' => 18];
    }

    public function array(): array
    {
        $m = $this->migracion;

        return [
            ['CONTEXTO DE MIGRACIÓN POBLACIONAL', ''],
            ['Indicador', 'Valor'],
            ['Padrón total',          $m['total_padron']],
            ['Residentes activos',    $m['residentes']],
            ['Migrantes',             $m['migrantes']],
            ['% Migrantes',           $m['pct_migrantes'].'%'],
            ['MEF activas (15-49 a)', $m['mef_activas']],
            ['MEF migradas',          $m['mef_migradas']],
            ['% MEF migradas',        $m['pct_mef_migradas'].'%'],
            ['Hombres migrados',      $m['hombres_migrados']],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $sheet = $e->sheet->getDelegate();

                $this->estiloTitulo($sheet, 'A1:B1', '');
                $this->alturaFila($sheet, 1, 24);

                $this->estiloHeader($sheet, 'A2:B2');
                $this->alturaFila($sheet, 2, 18);

                $totalFilas = 10;
                for ($i = 3; $i <= $totalFilas; $i++) {
                    $this->estiloEtiqueta($sheet, "A{$i}");
                    $this->estiloValor($sheet, "B{$i}");
                    // Alinear valores numéricos a la derecha
                    $sheet->getStyle("B{$i}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $this->alturaFila($sheet, $i, 16);
                }

                $this->bordeExterno($sheet, 'A2:B'.$totalFilas);
                $sheet->getStyle('A1:B'.$totalFilas)->getFont()->setName('Calibri');
            },
        ];
    }
}
