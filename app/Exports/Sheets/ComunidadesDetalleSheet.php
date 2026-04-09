<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ComunidadesDetalleSheet implements FromArray, WithTitle, WithStyles
{
    public function __construct(private array $datos) {}

    public function title(): string
    {
        return 'Detalle Sexo';
    }

    public function array(): array
    {
        $grupos = $this->datos['grupos'];
        $rows = [];
        $rows[] = ['DETALLE POR SEXO Y GRUPO ETÁREO'];
        $rows[] = ['Centro: ' . ($this->datos['centro']->nombre ?? '')];

        // Header row 1 (group labels spanning 2 cols each)
        $header1 = ['Comunidad'];
        foreach ($grupos as $label) {
            $header1[] = $label;
            $header1[] = '';
        }
        $header1[] = 'Migrantes';
        $header1[] = '';
        $header1[] = 'Total';
        $header1[] = '';
        $rows[] = $header1;

        // Header row 2 (H/M alternating)
        $header2 = [''];
        $colCount = count($grupos) + 2; // +migrantes +total
        for ($i = 0; $i < $colCount; $i++) {
            $header2[] = 'H';
            $header2[] = 'M';
        }
        $rows[] = $header2;

        // Data
        foreach ($this->datos['detalle'] as $row) {
            $dataRow = [$row['comunidad']];
            foreach ($grupos as $key => $label) {
                $dataRow[] = $row['datos'][$key]['M'];
                $dataRow[] = $row['datos'][$key]['F'];
            }
            $dataRow[] = $row['datos']['migrantes']['M'];
            $dataRow[] = $row['datos']['migrantes']['F'];
            $dataRow[] = $row['datos']['total']['M'];
            $dataRow[] = $row['datos']['total']['F'];
            $rows[] = $dataRow;
        }

        // Totals
        $dt = $this->datos['detalleTotales'];
        $totalRow = ['TOTAL'];
        foreach ($grupos as $key => $label) {
            $totalRow[] = $dt[$key]['M'];
            $totalRow[] = $dt[$key]['F'];
        }
        $totalRow[] = $dt['migrantes']['M'];
        $totalRow[] = $dt['migrantes']['F'];
        $totalRow[] = $dt['total']['M'];
        $totalRow[] = $dt['total']['F'];
        $rows[] = $totalRow;

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $grupos = $this->datos['grupos'];
        $numFilas = count($this->datos['detalle']);
        $headerRow1 = 3;
        $headerRow2 = 4;
        $lastDataRow = $headerRow2 + $numFilas;
        $totalRow = $lastDataRow + 1;
        $numCols = 1 + (count($grupos) + 2) * 2; // comunidad + (grupos+mig+total)*2

        $lastColLetter = $this->colLetter($numCols - 1);

        // Merge group header cells (row 4)
        $col = 1; // start after Comunidad (col 0 = A)
        foreach ($grupos as $label) {
            $l1 = $this->colLetter($col);
            $l2 = $this->colLetter($col + 1);
            $sheet->mergeCells("{$l1}{$headerRow1}:{$l2}{$headerRow1}");
            $col += 2;
        }
        // Migrantes merge
        $l1 = $this->colLetter($col);
        $l2 = $this->colLetter($col + 1);
        $sheet->mergeCells("{$l1}{$headerRow1}:{$l2}{$headerRow1}");
        $col += 2;
        // Total merge
        $l1 = $this->colLetter($col);
        $l2 = $this->colLetter($col + 1);
        $sheet->mergeCells("{$l1}{$headerRow1}:{$l2}{$headerRow1}");

        $sheet->getColumnDimension('A')->setWidth(22);
        for ($i = 1; $i < $numCols; $i++) {
            $sheet->getColumnDimension($this->colLetter($i))->setWidth(5);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '166534']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        $sheet->getStyle("A{$headerRow1}:{$lastColLetter}{$headerRow2}")->applyFromArray($headerStyle);

        $sheet->getStyle("A{$headerRow1}:{$lastColLetter}{$totalRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("B{$headerRow2}:{$lastColLetter}{$totalRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [
            1 => ['font' => ['bold' => true, 'size' => 13]],
            2 => ['font' => ['italic' => true, 'size' => 10]],
            $totalRow => ['font' => ['bold' => true]],
        ];
    }

    private function colLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26) - 1;
        }
        return $letter;
    }
}
