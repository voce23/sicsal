<?php

namespace App\Exports\Sheets\Concerns;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Métodos de estilo compartidos para todos los Sheets del Informe CAI.
 * Paleta institucional SIMUES (cyan/teal).
 */
trait EstiloExcel
{
    // ── Paleta ──
    protected const TEAL       = '00897B';
    protected const TEAL_DARK  = '004D40';
    protected const TEAL_LIGHT = 'E0F2F1';
    protected const CYAN       = '00ACC1';
    protected const CYAN_LIGHT = 'E0F7FA';
    protected const WHITE      = 'FFFFFF';
    protected const GRAY_BG    = 'F5F5F5';
    protected const GRAY_LIGHT = 'ECEFF1';
    protected const GRAY_MID   = 'B0BEC5';
    protected const BLACK      = '212121';
    protected const GREEN_OK   = '2E7D32';
    protected const GREEN_BG   = 'E8F5E9';
    protected const AMBER      = 'F57F17';
    protected const AMBER_BG   = 'FFFDE7';
    protected const ORANGE     = 'E65100';
    protected const ORANGE_BG  = 'FBE9E7';
    protected const RED        = 'C62828';
    protected const RED_BG     = 'FFEBEE';

    /** Aplica estilo de título principal (celda fusionada, fondo teal oscuro, texto blanco). */
    protected function estiloTitulo(Worksheet $sheet, string $rango, string $texto): void
    {
        $sheet->mergeCells($rango);
        $sheet->getStyle($rango)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF' . self::WHITE]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . self::TEAL_DARK]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $sheet->getRowDimension(explode(':', $rango)[0][1])->setRowHeight(22);
    }

    /** Aplica estilo de subtítulo de sección (fondo teal, texto blanco). */
    protected function estiloSeccion(Worksheet $sheet, string $rango): void
    {
        $sheet->mergeCells($rango);
        $sheet->getStyle($rango)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF' . self::WHITE]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . self::TEAL]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
    }

    /** Aplica estilo de fila de encabezado de tabla (fondo teal, texto blanco, centrado). */
    protected function estiloHeader(Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF' . self::WHITE]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . self::TEAL]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::TEAL_DARK]]],
        ]);
    }

    /** Aplica estilo de etiqueta clave (col A de secciones key-value). */
    protected function estiloEtiqueta(Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF' . self::TEAL_DARK]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . self::TEAL_LIGHT]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::GRAY_MID]]],
        ]);
    }

    /** Aplica estilo de valor en tabla key-value. */
    protected function estiloValor(Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->applyFromArray([
            'font'      => ['size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . self::WHITE]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::GRAY_MID]]],
        ]);
    }

    /** Aplica estilo de fila de datos con alternancia de color. */
    protected function estiloDato(Worksheet $sheet, string $rango, int $fila, bool $centrado = false): void
    {
        $bg = ($fila % 2 === 0) ? self::GRAY_BG : self::WHITE;
        $sheet->getStyle($rango)->applyFromArray([
            'font'      => ['size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . $bg]],
            'alignment' => [
                'horizontal' => $centrado ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'indent'     => $centrado ? 0 : 1,
            ],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::GRAY_MID]]],
        ]);
    }

    /**
     * Aplica color de semáforo según % de cobertura.
     * ≥ 95% verde, 80-94% ámbar, 50-79% naranja, < 50% rojo
     */
    protected function estiloSemaforo(Worksheet $sheet, string $celda, float $pct): void
    {
        [$bg, $fg] = match (true) {
            $pct >= 95 => [self::GREEN_BG,  self::GREEN_OK],
            $pct >= 80 => [self::AMBER_BG,  self::AMBER],
            $pct >= 50 => [self::ORANGE_BG, self::ORANGE],
            default    => [self::RED_BG,     self::RED],
        };

        $sheet->getStyle($celda)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF' . $fg]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF' . $bg]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::GRAY_MID]]],
        ]);
    }

    /** Aplica bordes externos alrededor de un bloque de celdas. */
    protected function bordeExterno(Worksheet $sheet, string $rango): void
    {
        $sheet->getStyle($rango)->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF' . self::TEAL]]],
        ]);
    }

    /** Fija la primera fila (freeze pane). */
    protected function fijarFila(Worksheet $sheet, string $celda = 'A2'): void
    {
        $sheet->freezePane($celda);
    }

    /** Configura ancho de columna. */
    protected function anchoColumna(Worksheet $sheet, string $col, float $ancho): void
    {
        $sheet->getColumnDimension($col)->setWidth($ancho);
    }

    /** Aplica altura a una fila. */
    protected function alturaFila(Worksheet $sheet, int $fila, float $alto): void
    {
        $sheet->getRowDimension($fila)->setRowHeight($alto);
    }
}
