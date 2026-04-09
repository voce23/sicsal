<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ComunidadesResumenSheet implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    public function __construct(private array $datos) {}

    public function title(): string
    {
        return 'Resumen';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22, 'B' => 6,
            'C' => 8, 'D' => 6, 'E' => 6,
            'F' => 6, 'G' => 6, 'H' => 6, 'I' => 6, 'J' => 6, 'K' => 7, 'L' => 6, 'M' => 6, 'N' => 6,
            'O' => 7,
        ];
    }

    public function array(): array
    {
        $grupos = $this->datos['grupos'];

        $rows = [];
        $rows[] = ['COMUNIDADES Y POBLACIÓN — RESUMEN'];
        $rows[] = ['Centro: '.($this->datos['centro']->nombre ?? ''), '', '', '', '', '', '', '', '', '', '', '', '', '', ''];

        // Header row 1
        $header1 = ['Comunidad', 'Km', 'Total', 'H', 'M'];
        foreach ($grupos as $label) {
            $header1[] = $label;
        }
        $header1[] = 'Migr.';
        $rows[] = $header1;

        // Data rows
        foreach ($this->datos['filas'] as $fila) {
            $row = [$fila['comunidad'], $fila['km'], $fila['total'], $fila['hombres'], $fila['mujeres']];
            foreach ($grupos as $key => $label) {
                $row[] = $fila[$key];
            }
            $row[] = $fila['migrantes'];
            $rows[] = $row;
        }

        // Totals
        $t = $this->datos['totales'];
        $totalRow = ['TOTAL REAL', '', $t['total'], $t['hombres'], $t['mujeres']];
        foreach ($grupos as $key => $label) {
            $totalRow[] = $t[$key];
        }
        $totalRow[] = $t['migrantes'];
        $rows[] = $totalRow;

        $rows[] = ['META INE', '', $this->datos['metaIne']];
        $dif = $this->datos['diferencia'];
        $pct = $this->datos['metaIne'] > 0 ? round($t['total'] / $this->datos['metaIne'] * 100, 1).'%' : '';
        $rows[] = ['DIFERENCIA', '', ($dif >= 0 ? '+' : '').$dif.' ('.$pct.')'];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $numFilas = count($this->datos['filas']);
        $headerRow = 3;
        $lastDataRow = $headerRow + $numFilas;
        $totalRow = $lastDataRow + 1;

        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 13]],
            2 => ['font' => ['italic' => true, 'size' => 10]],
            $headerRow => [
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            $totalRow => ['font' => ['bold' => true]],
            $totalRow + 1 => ['font' => ['bold' => true]],
            $totalRow + 2 => ['font' => ['bold' => true]],
        ];

        // Borders for data area
        $lastCol = chr(ord('A') + 14); // O
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$totalRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Center align data columns
        $sheet->getStyle("B{$headerRow}:{$lastCol}{$totalRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return $styles;
    }
}
