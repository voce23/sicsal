<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ComunidadesConsolidadoSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(private array $datos) {}

    public function title(): string
    {
        return 'INE vs Real';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, 'B' => 8, 'C' => 8, 'D' => 9,
            'E' => 8, 'F' => 8, 'G' => 9,
            'H' => 10, 'I' => 10,
        ];
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = ['CONSOLIDADO META INE vs. POBLACIÓN REAL'];
        $rows[] = ['Centro: ' . ($this->datos['centro']->nombre ?? '')];
        $rows[] = ['Grupo', 'INE H', 'INE M', 'INE Total', 'Real H', 'Real M', 'Real Total', 'Diferencia', 'Cobertura'];

        $sumIneM = $sumIneF = $sumIneT = $sumRealM = $sumRealF = $sumRealT = 0;
        foreach ($this->datos['consolidado'] as $row) {
            $rows[] = [
                $row['label'],
                $row['ine_m'], $row['ine_f'], $row['ine_total'],
                $row['real_m'], $row['real_f'], $row['real_total'],
                ($row['diferencia'] >= 0 ? '+' : '') . $row['diferencia'],
                $row['cobertura'] . '%',
            ];
            $sumIneM += $row['ine_m'];
            $sumIneF += $row['ine_f'];
            $sumIneT += $row['ine_total'];
            $sumRealM += $row['real_m'];
            $sumRealF += $row['real_f'];
            $sumRealT += $row['real_total'];
        }

        $dif = $sumRealT - $sumIneT;
        $pct = $sumIneT > 0 ? round($sumRealT / $sumIneT * 100, 1) : 0;
        $rows[] = [
            'TOTAL',
            $sumIneM, $sumIneF, $sumIneT,
            $sumRealM, $sumRealF, $sumRealT,
            ($dif >= 0 ? '+' : '') . $dif,
            $pct . '%',
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $numFilas = count($this->datos['consolidado']);
        $headerRow = 3;
        $lastDataRow = $headerRow + $numFilas;
        $totalRow = $lastDataRow + 1;

        $sheet->getStyle("A{$headerRow}:I{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A{$headerRow}:I{$totalRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("B{$headerRow}:I{$totalRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Color coverage cells
        for ($r = $headerRow + 1; $r <= $lastDataRow; $r++) {
            $val = $sheet->getCell("I{$r}")->getValue();
            $num = (float) str_replace('%', '', $val);
            $color = $num >= 100 ? '059669' : ($num >= 80 ? 'D97706' : 'DC2626');
            $sheet->getStyle("I{$r}")->getFont()->getColor()->setRGB($color);
        }

        return [
            1 => ['font' => ['bold' => true, 'size' => 13]],
            2 => ['font' => ['italic' => true, 'size' => 10]],
            $totalRow => ['font' => ['bold' => true]],
        ];
    }
}
