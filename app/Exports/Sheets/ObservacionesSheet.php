<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\EstiloExcel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ObservacionesSheet implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    use EstiloExcel;

    public function __construct(private array $observaciones) {}

    public function title(): string
    {
        return 'Observaciones';
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 90];
    }

    public function array(): array
    {
        $rows = [
            ['OBSERVACIONES NARRATIVAS DEL PERÍODO', ''],
            ['Mes', 'Texto'],
        ];

        if (empty($this->observaciones)) {
            $rows[] = ['—', 'Sin observaciones narrativas para este período.'];
        } else {
            foreach ($this->observaciones as $o) {
                $rows[] = [$o['mes'], $o['texto']];
            }
        }

        return $rows;
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

                $nFilas = empty($this->observaciones) ? 1 : count($this->observaciones);
                for ($i = 0; $i < $nFilas; $i++) {
                    $fila = 3 + $i;
                    $this->estiloDato($sheet, "A{$fila}:B{$fila}", $i + 1);
                    $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_TOP);
                    $sheet->getStyle("B{$fila}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                    // Altura dinámica basada en longitud del texto
                    $texto = $this->observaciones[$i]['texto'] ?? '';
                    $lineas = max(3, (int) ceil(mb_strlen($texto) / 120));
                    $this->alturaFila($sheet, $fila, $lineas * 14);
                }

                $ultimaFila = 2 + $nFilas;
                $this->bordeExterno($sheet, 'A2:B'.$ultimaFila);
                $sheet->getStyle('A1:B'.$ultimaFila)->getFont()->setName('Calibri');
            },
        ];
    }
}
