<?php

namespace App\Exports;

use App\Exports\Sheets\Concerns\EstiloExcel;
use App\Helpers\CausasConsultaHelper;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CausasConsultaExternaExport implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    use EstiloExcel;

    /** Columnas: N°(A), Diagnóstico(B), 10 grupos × 2 sexos (C–V), TotalM(W), TotalF(X), Total(Y), %(Z) */
    private const GRUPOS = [
        'menor_6m'   => '<6m',
        '6m_menor_1' => '6m-1a',
        '1_4'        => '1-4a',
        '5_9'        => '5-9a',
        '10_14'      => '10-14a',
        '15_19'      => '15-19a',
        '20_39'      => '20-39a',
        '40_49'      => '40-49a',
        '50_59'      => '50-59a',
        'mayor_60'   => '≥60a',
    ];

    // Col índices (0-based): A=0, B=1, C=2 ... Z=25
    private const COL_NUM   = 0;  // A
    private const COL_DIAG  = 1;  // B
    // C(2)..V(21) = 20 columnas de grupos (c/grupo: M=par, F=impar)
    private const COL_TOT_M = 22; // W
    private const COL_TOT_F = 23; // X
    private const COL_TOT   = 24; // Y
    private const COL_PCT   = 25; // Z

    public function __construct(private array $datos) {}

    public function title(): string
    {
        return '10 Causas Consulta';
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 5, 'B' => 42];
        $col    = ord('C');
        foreach (self::GRUPOS as $label) {
            $widths[chr($col)]     = 6; // M
            $widths[chr($col + 1)] = 6; // F
            $col += 2;
        }
        $widths['W'] = 8;
        $widths['X'] = 8;
        $widths['Y'] = 8;
        $widths['Z'] = 7;

        return $widths;
    }

    public function array(): array
    {
        $causas     = $this->datos['causas'];
        $grandTotal = $this->datos['grand_total'];
        $grupoCols  = array_keys(self::GRUPOS);

        // ── Fila 1: título ──────────────────────────────────
        $rows[] = array_merge(
            ['10 PRINCIPALES CAUSAS DE CONSULTA EXTERNA'],
            array_fill(0, 25, '')
        );

        // ── Fila 2: info período/centro ──────────────────────
        $rows[] = array_merge(
            [$this->datos['centro'] . '  ·  ' . $this->datos['periodo_label']],
            array_fill(0, 25, '')
        );

        // ── Fila 3: encabezado grupo etáreo (top-level) ──────
        $headerTop = ['', ''];
        foreach (self::GRUPOS as $label) {
            $headerTop[] = $label;
            $headerTop[] = '';
        }
        $headerTop[] = '';
        $headerTop[] = '';
        $headerTop[] = '';
        $headerTop[] = '';
        $rows[] = $headerTop;

        // ── Fila 4: sub-encabezado M/F ────────────────────────
        $headerSub = ['N°', 'Diagnóstico'];
        foreach (self::GRUPOS as $label) {
            $headerSub[] = 'M';
            $headerSub[] = 'F';
        }
        $headerSub[] = 'Total M';
        $headerSub[] = 'Total F';
        $headerSub[] = 'Total';
        $headerSub[] = '%';
        $rows[] = $headerSub;

        // ── Filas 5-14: datos ─────────────────────────────────
        foreach ($causas as $causa) {
            $row = [$causa['rank'], $causa['diagnostico']];
            foreach ($grupoCols as $g) {
                $row[] = $causa['grupos'][$g]['m'] ?? 0;
                $row[] = $causa['grupos'][$g]['f'] ?? 0;
            }
            $row[] = $causa['total_m'];
            $row[] = $causa['total_f'];
            $row[] = $causa['total'];
            $row[] = $causa['porcentaje'] . '%';
            $rows[] = $row;
        }

        // Rellenar hasta 10 filas si hay menos causas
        $faltantes = 10 - count($causas);
        for ($i = 0; $i < $faltantes; $i++) {
            $rows[] = array_merge([count($causas) + $i + 1, '—'], array_fill(0, 24, ''));
        }

        // ── Fila total ────────────────────────────────────────
        $totalRow = ['', 'TOTAL'];
        foreach ($grupoCols as $g) {
            $sumM = array_sum(array_map(fn ($c) => $c['grupos'][$g]['m'] ?? 0, $causas));
            $sumF = array_sum(array_map(fn ($c) => $c['grupos'][$g]['f'] ?? 0, $causas));
            $totalRow[] = $sumM;
            $totalRow[] = $sumF;
        }
        $totalRow[] = $this->datos['grand_total_m'];
        $totalRow[] = $this->datos['grand_total_f'];
        $totalRow[] = $grandTotal;
        $totalRow[] = '100%';
        $rows[] = $totalRow;

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastCol = 'Z';
                $nDatos  = max(count($this->datos['causas']), 10);
                $filaTot = 5 + $nDatos; // fila TOTAL

                // ── Estilos de filas especiales ─────────────────────
                // Título
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF004D40']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(24);

                // Info período
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['size' => 10, 'italic' => true, 'color' => ['argb' => 'FF004D40']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFE0F2F1']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(16);

                // Encabezado grupo etáreo (fusionar cada par M/F)
                $col = ord('C');
                foreach (array_keys(self::GRUPOS) as $_) {
                    $colLetter  = chr($col);
                    $colLetter2 = chr($col + 1);
                    $sheet->mergeCells("{$colLetter}3:{$colLetter2}3");
                    $col += 2;
                }
                // Fusionar W/X para "Totales"
                $sheet->mergeCells('W3:X3');
                $sheet->setCellValue('W3', 'Totales');
                // Fusionar Y/Z vacío en fila 3 (no hay label necesario)
                $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 8, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF00897B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF004D40']]],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);

                // Sub-encabezado M/F
                $sheet->getStyle("A4:{$lastCol}4")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 8, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF00897B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF004D40']]],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(18);

                // Filas de datos
                for ($i = 0; $i < $nDatos; $i++) {
                    $f  = 5 + $i;
                    $bg = ($i % 2 === 0) ? 'FFFFFFFF' : 'FFF5F5F5';
                    $sheet->getStyle("A{$f}:{$lastCol}{$f}")->applyFromArray([
                        'font'      => ['size' => 8],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => $bg]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFB0BEC5']]],
                    ]);
                    // Diagnóstico alineado a la izquierda
                    $sheet->getStyle("B{$f}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                        ->setIndent(1);
                    $sheet->getRowDimension($f)->setRowHeight(14);
                }

                // Fila TOTAL
                $sheet->getStyle("A{$filaTot}:{$lastCol}{$filaTot}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF004D40']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF004D40']]],
                ]);
                $sheet->getStyle("B{$filaTot}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setIndent(1);
                $sheet->getRowDimension($filaTot)->setRowHeight(16);

                // Bordes externos
                $sheet->getStyle("A3:{$lastCol}{$filaTot}")->applyFromArray([
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF00897B']]],
                ]);

                // Freeze encabezados
                $sheet->freezePane('C5');

                // Orientación horizontal
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A3);
                $sheet->getPageSetup()->setFitToPage(true);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                // Fuente global
                $sheet->getStyle("A1:{$lastCol}{$filaTot}")->getFont()->setName('Calibri');
            },
        ];
    }
}
