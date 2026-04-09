<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\EstiloExcel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CensoSheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    use EstiloExcel;

    public function __construct(private array $censo) {}

    public function title(): string
    {
        return 'Censo';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28, 'B' => 12, 'C' => 10,
            'D' => 10, 'E' => 10, 'F' => 10, 'G' => 12,
        ];
    }

    public function array(): array
    {
        $rows = [
            ['PADRÓN POBLACIONAL — CENSO POR COMUNIDAD', '', '', '', '', '', ''],
            ['Comunidad', 'Dist. (km)', 'Total', 'Hombres', 'Mujeres', '< 5 años', 'Migrantes'],
        ];

        foreach ($this->censo['comunidades'] as $com) {
            $rows[] = [
                $com['nombre'],
                $com['distancia_km'] !== null ? $com['distancia_km'] : '—',
                $com['total'],
                $com['hombres'],
                $com['mujeres'],
                $com['menor_5'],
                $com['migrantes'],
            ];
        }

        // Fila total
        $totales = array_reduce($this->censo['comunidades'], function ($carry, $com) {
            $carry['total']    += $com['total'];
            $carry['hombres']  += $com['hombres'];
            $carry['mujeres']  += $com['mujeres'];
            $carry['menor_5']  += $com['menor_5'];
            $carry['migrantes'] += $com['migrantes'];
            return $carry;
        }, ['total' => 0, 'hombres' => 0, 'mujeres' => 0, 'menor_5' => 0, 'migrantes' => 0]);

        $rows[] = ['TOTAL', '—', $totales['total'], $totales['hombres'], $totales['mujeres'], $totales['menor_5'], $totales['migrantes']];

        $rows[] = ['', '', '', '', '', '', ''];

        // Sección pirámide
        $rows[] = ['PIRÁMIDE POBLACIONAL — INE vs. REAL', '', '', '', '', '', ''];
        $rows[] = ['Grupo Etáreo', 'INE M', 'INE F', 'INE Total', 'Real M', 'Real F', 'Real Total'];

        foreach ($this->censo['piramide'] as $g) {
            $ineTotal  = $g['ine_m']  + $g['ine_f'];
            $realTotal = $g['real_m'] + $g['real_f'];
            $rows[] = [
                $g['label'],
                $g['ine_m'], $g['ine_f'], $ineTotal,
                $g['real_m'], $g['real_f'], $realTotal,
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $sheet = $e->sheet->getDelegate();

                $nComunidades = count($this->censo['comunidades']);
                $nPiramide    = count($this->censo['piramide']);

                // ── Sección 1: Censo ──
                $this->estiloTitulo($sheet, 'A1:G1', '');
                $this->alturaFila($sheet, 1, 24);

                $this->estiloHeader($sheet, 'A2:G2');
                $this->alturaFila($sheet, 2, 20);

                // Filas de comunidades
                for ($i = 0; $i < $nComunidades; $i++) {
                    $fila = 3 + $i;
                    $this->estiloDato($sheet, "A{$fila}:G{$fila}", $i + 1);
                    // Números centrados
                    $sheet->getStyle("B{$fila}:G{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $this->alturaFila($sheet, $fila, 15);
                }

                // Fila TOTAL
                $filaTotal = 3 + $nComunidades;
                $sheet->getStyle("A{$filaTotal}:G{$filaTotal}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF' . self::WHITE]],
                    'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . self::TEAL_DARK]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::TEAL]]],
                ]);
                $sheet->getStyle('A' . $filaTotal)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $this->alturaFila($sheet, $filaTotal, 16);

                $this->bordeExterno($sheet, 'A2:G' . $filaTotal);

                // ── Sección 2: Pirámide ──
                $filaVacia   = $filaTotal + 1;
                $filaTit2    = $filaTotal + 2;
                $filaHdr2    = $filaTit2 + 1;
                $inicioData2 = $filaHdr2 + 1;

                $this->estiloSeccion($sheet, "A{$filaTit2}:G{$filaTit2}");
                $this->alturaFila($sheet, $filaTit2, 20);

                $this->estiloHeader($sheet, "A{$filaHdr2}:G{$filaHdr2}");
                $this->alturaFila($sheet, $filaHdr2, 20);

                for ($i = 0; $i < $nPiramide; $i++) {
                    $fila = $inicioData2 + $i;
                    $this->estiloDato($sheet, "A{$fila}:G{$fila}", $i + 1);
                    $sheet->getStyle("B{$fila}:G{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $this->alturaFila($sheet, $fila, 15);
                }

                $ultimaFila = $inicioData2 + $nPiramide - 1;
                $this->bordeExterno($sheet, "A{$filaHdr2}:G{$ultimaFila}");

                $this->fijarFila($sheet, 'A3');
                $sheet->getStyle('A1:G' . $ultimaFila)->getFont()->setName('Calibri');
            },
        ];
    }
}
