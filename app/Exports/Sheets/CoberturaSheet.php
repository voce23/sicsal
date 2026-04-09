<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\EstiloExcel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CoberturaSheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    use EstiloExcel;

    public function __construct(private array $cobertura, private array $desercion) {}

    public function title(): string
    {
        return 'Cobertura';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 32, 'B' => 12, 'C' => 12,
            'D' => 12, 'E' => 13, 'F' => 13,
        ];
    }

    public function array(): array
    {
        $rows = [
            ['COBERTURA DE PROGRAMAS DE SALUD', '', '', '', '', ''],
            ['Programa / Vacuna', 'Meta INE', 'Pob. Real', 'Atendidos', 'Cob. INE %', 'Cob. Real %'],
        ];

        foreach ($this->cobertura as $prog) {
            $rows[] = [
                $prog['nombre'],
                $prog['meta'],
                $prog['real'],
                $prog['atendidos'],
                $prog['cob_ine'],
                $prog['cob_real'],
            ];
        }

        $rows[] = ['', '', '', '', '', ''];
        $rows[] = ['TASAS DE DESERCIÓN VACUNAL', '', '', '', '', ''];
        $rows[] = ['Indicador', '1ra Dosis', 'Última Dosis', 'Deserción', 'Tasa %', ''];

        foreach ($this->desercion as $d) {
            $rows[] = [$d['indicador'], $d['primera'], $d['ultima'], $d['primera'] - $d['ultima'], $d['tasa'], ''];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $sheet = $e->sheet->getDelegate();

                $nCob = count($this->cobertura);
                $nDes = count($this->desercion);

                // ── Sección 1: Cobertura ──
                $this->estiloTitulo($sheet, 'A1:F1', '');
                $this->alturaFila($sheet, 1, 24);

                $this->estiloHeader($sheet, 'A2:F2');
                $this->alturaFila($sheet, 2, 22);

                for ($i = 0; $i < $nCob; $i++) {
                    $fila = 3 + $i;
                    $prog = $this->cobertura[$i];

                    $this->estiloDato($sheet, "A{$fila}:F{$fila}", $i + 1);
                    $sheet->getStyle("B{$fila}:D{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Semáforo Cob. INE (col E)
                    $this->estiloSemaforo($sheet, "E{$fila}", (float) $prog['cob_ine']);
                    // Semáforo Cob. Real (col F)
                    $this->estiloSemaforo($sheet, "F{$fila}", (float) $prog['cob_real']);

                    // Agregar símbolo % al texto
                    $sheet->setCellValue("E{$fila}", $prog['cob_ine'] . '%');
                    $sheet->setCellValue("F{$fila}", $prog['cob_real'] . '%');

                    $this->alturaFila($sheet, $fila, 15);
                }

                $filaFinalCob = 2 + $nCob;
                $this->bordeExterno($sheet, 'A2:F' . $filaFinalCob);

                // ── Sección 2: Deserción ──
                $filaVacia  = $filaFinalCob + 1;
                $filaTit2   = $filaFinalCob + 2;
                $filaHdr2   = $filaTit2 + 1;
                $inicioData = $filaHdr2 + 1;

                $this->estiloSeccion($sheet, "A{$filaTit2}:E{$filaTit2}");
                $this->alturaFila($sheet, $filaTit2, 20);

                $this->estiloHeader($sheet, "A{$filaHdr2}:E{$filaHdr2}");
                $this->alturaFila($sheet, $filaHdr2, 18);

                for ($i = 0; $i < $nDes; $i++) {
                    $fila = $inicioData + $i;
                    $des  = $this->desercion[$i];

                    $this->estiloDato($sheet, "A{$fila}:E{$fila}", $i + 1);
                    $sheet->getStyle("B{$fila}:E{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Color tasa de deserción: ≤5% verde, ≤15% ámbar, >15% rojo
                    $tasa = (float) $des['tasa'];
                    [$bg, $fg] = match (true) {
                        $tasa <= 5  => [self::GREEN_BG,  self::GREEN_OK],
                        $tasa <= 15 => [self::AMBER_BG,  self::AMBER],
                        default     => [self::RED_BG,     self::RED],
                    };
                    $sheet->getStyle("E{$fila}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF' . $fg]],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . $bg]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->setCellValue("E{$fila}", $des['tasa'] . '%');

                    $this->alturaFila($sheet, $fila, 15);
                }

                $ultimaFila = $inicioData + $nDes - 1;
                $this->bordeExterno($sheet, "A{$filaHdr2}:E{$ultimaFila}");

                // Leyenda semáforo
                $filaLey = $ultimaFila + 2;
                $sheet->setCellValue("A{$filaLey}", 'Leyenda:');
                $sheet->getStyle("A{$filaLey}")->getFont()->setBold(true)->setSize(8);

                $leyenda = [
                    ['B', self::GREEN_BG, self::GREEN_OK, '≥ 95%'],
                    ['C', self::AMBER_BG, self::AMBER,    '80–94%'],
                    ['D', self::ORANGE_BG, self::ORANGE,  '50–79%'],
                    ['E', self::RED_BG,   self::RED,      '< 50%'],
                ];
                foreach ($leyenda as [$col, $bg, $fg, $lbl]) {
                    $sheet->setCellValue("{$col}{$filaLey}", $lbl);
                    $sheet->getStyle("{$col}{$filaLey}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 8, 'color' => ['argb' => 'FF' . $fg]],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . $bg]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::GRAY_MID]]],
                    ]);
                }

                $this->fijarFila($sheet, 'A3');
                $sheet->getStyle('A1:F' . $ultimaFila)->getFont()->setName('Calibri');
            },
        ];
    }
}
