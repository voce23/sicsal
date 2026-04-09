<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\EstiloExcel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CerosSheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    use EstiloExcel;

    public function __construct(private array $ceros) {}

    public function title(): string
    {
        return 'Ceros Justific.';
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 30, 'C' => 24, 'D' => 45];
    }

    public function array(): array
    {
        $rows = [
            ['CEROS JUSTIFICADOS DEL PERÍODO', '', '', ''],
            ['Mes', 'Indicador', 'Motivo', 'Detalle'],
        ];

        if (empty($this->ceros)) {
            $rows[] = ['—', 'Sin ceros justificados en este período', '', ''];
        } else {
            foreach ($this->ceros as $j) {
                $rows[] = [
                    $j['mes'],
                    str_replace('_', ' ', $j['indicador']),
                    ucfirst(str_replace('_', ' ', $j['motivo'])),
                    $j['detalle'] ?? '—',
                ];
            }
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $sheet = $e->sheet->getDelegate();

                $this->estiloTitulo($sheet, 'A1:D1', '');
                $this->alturaFila($sheet, 1, 24);

                $this->estiloHeader($sheet, 'A2:D2');
                $this->alturaFila($sheet, 2, 18);

                $nFilas = empty($this->ceros) ? 1 : count($this->ceros);
                for ($i = 0; $i < $nFilas; $i++) {
                    $fila = 3 + $i;
                    $this->estiloDato($sheet, "A{$fila}:D{$fila}", $i + 1);
                    $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    // Columna detalle con wrap text
                    $sheet->getStyle("D{$fila}")->getAlignment()->setWrapText(true);
                    $this->alturaFila($sheet, $fila, 30);
                }

                $ultimaFila = 2 + $nFilas;
                $this->bordeExterno($sheet, 'A2:D' . $ultimaFila);
                $this->fijarFila($sheet, 'A3');
                $sheet->getStyle('A1:D' . $ultimaFila)->getFont()->setName('Calibri');
            },
        ];
    }
}
