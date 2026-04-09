<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\EstiloExcel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PrestacionesSheet implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    use EstiloExcel;

    // Índices de filas de cada sección (llenados en array())
    private array $secciones = [];

    public function __construct(private array $prestaciones) {}

    public function title(): string
    {
        return 'Prestaciones';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, 'B' => 14, 'C' => 10,
            'D' => 10, 'E' => 10, 'F' => 10, 'G' => 10,
        ];
    }

    public function array(): array
    {
        $rows = [];
        $fila = 1;

        // ── Vacunas ──
        $this->secciones['vacunas_titulo'] = $fila;
        $rows[] = ['VACUNACIONES', '', '', '', '', '', ''];
        $fila++;
        $this->secciones['vacunas_header'] = $fila;
        $rows[] = ['Vacuna', 'Grupo Etáreo', 'Dentro M', 'Dentro F', 'Fuera M', 'Fuera F', 'Total'];
        $fila++;
        $this->secciones['vacunas_inicio'] = $fila;
        foreach ($this->prestaciones['vacunas'] as $v) {
            $rows[] = [
                $v['tipo_vacuna'],
                $v['grupo_etareo'],
                $v['dentro_m'], $v['dentro_f'],
                $v['fuera_m'],  $v['fuera_f'],
                $v['total'],
            ];
            $fila++;
        }
        $this->secciones['vacunas_fin'] = $fila - 1;

        // ── Micronutrientes ──
        $rows[] = ['', '', '', '', '', '', ''];
        $fila++;
        $this->secciones['micro_titulo'] = $fila;
        $rows[] = ['MICRONUTRIENTES', '', '', '', '', '', ''];
        $fila++;
        $this->secciones['micro_header'] = $fila;
        $rows[] = ['Tipo', 'Cantidad', '', '', '', '', ''];
        $fila++;
        $this->secciones['micro_inicio'] = $fila;
        foreach ($this->prestaciones['micronutrientes'] as $tipo => $total) {
            $rows[] = [str_replace('_', ' ', ucwords($tipo, '_')), $total, '', '', '', '', ''];
            $fila++;
        }
        $this->secciones['micro_fin'] = $fila - 1;

        // ── Crecimiento ──
        $rows[] = ['', '', '', '', '', '', ''];
        $fila++;
        $this->secciones['crec_titulo'] = $fila;
        $rows[] = ['CONTROL DE CRECIMIENTO INFANTIL', '', '', '', '', '', ''];
        $fila++;
        $this->secciones['crec_header'] = $fila;
        $rows[] = ['Grupo', 'Nuevos M', 'Nuevos F', 'Repetidos M', 'Repetidos F', '', ''];
        $fila++;
        $this->secciones['crec_inicio'] = $fila;
        foreach ($this->prestaciones['crecimiento'] as $ge => $c) {
            $rows[] = [
                str_replace(['_', 'dentro', 'fuera', 'menor', 'anios'], [' ', 'dentro', 'fuera', '< ', 'años'], $ge),
                $c['nuevos_m'] ?? 0,    $c['nuevos_f'] ?? 0,
                $c['repetidos_m'] ?? 0, $c['repetidos_f'] ?? 0,
                '', '',
            ];
            $fila++;
        }
        $this->secciones['crec_fin'] = $fila - 1;

        // ── Recién Nacidos ──
        $rows[] = ['', '', '', '', '', '', ''];
        $fila++;
        $this->secciones['rn_titulo'] = $fila;
        $rows[] = ['RECIÉN NACIDOS', '', '', '', '', '', ''];
        $fila++;
        $this->secciones['rn_header'] = $fila;
        $rows[] = ['Indicador', 'Total', '', '', '', '', ''];
        $fila++;
        $this->secciones['rn_inicio'] = $fila;
        foreach ($this->prestaciones['recien_nacidos'] as $ind => $total) {
            $rows[] = [str_replace('_', ' ', ucwords($ind, '_')), $total, '', '', '', '', ''];
            $fila++;
        }
        $this->secciones['rn_fin'] = $fila - 1;

        // ── Puerperio ──
        $rows[] = ['', '', '', '', '', '', ''];
        $fila++;
        $this->secciones['puer_titulo'] = $fila;
        $rows[] = ['PUERPERIO', '', '', '', '', '', ''];
        $fila++;
        $this->secciones['puer_header'] = $fila;
        $rows[] = ['Control', 'Total', '', '', '', '', ''];
        $fila++;
        $this->secciones['puer_inicio'] = $fila;
        foreach ($this->prestaciones['puerperio'] as $tc => $total) {
            $rows[] = [str_replace('_', ' ', $tc), $total, '', '', '', '', ''];
            $fila++;
        }
        $this->secciones['puer_fin'] = $fila - 1;

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $sheet = $e->sheet->getDelegate();
                $s = $this->secciones;

                $bloques = [
                    ['tit' => 'vacunas_titulo',  'hdr' => 'vacunas_header', 'ini' => 'vacunas_inicio', 'fin' => 'vacunas_fin', 'cols' => 'A:G', 'ncols' => 7],
                    ['tit' => 'micro_titulo',     'hdr' => 'micro_header',   'ini' => 'micro_inicio',   'fin' => 'micro_fin',   'cols' => 'A:B', 'ncols' => 2],
                    ['tit' => 'crec_titulo',      'hdr' => 'crec_header',    'ini' => 'crec_inicio',    'fin' => 'crec_fin',    'cols' => 'A:E', 'ncols' => 5],
                    ['tit' => 'rn_titulo',        'hdr' => 'rn_header',      'ini' => 'rn_inicio',      'fin' => 'rn_fin',      'cols' => 'A:B', 'ncols' => 2],
                    ['tit' => 'puer_titulo',      'hdr' => 'puer_header',    'ini' => 'puer_inicio',    'fin' => 'puer_fin',    'cols' => 'A:B', 'ncols' => 2],
                ];

                foreach ($bloques as $bloque) {
                    $colUlt = chr(ord('A') + $bloque['ncols'] - 1);
                    $filaTit = $s[$bloque['tit']];
                    $filaHdr = $s[$bloque['hdr']];
                    $filaIni = $s[$bloque['ini']];
                    $filaFin = $s[$bloque['fin']];

                    $this->estiloSeccion($sheet, "A{$filaTit}:{$colUlt}{$filaTit}");
                    $this->alturaFila($sheet, $filaTit, 20);

                    $this->estiloHeader($sheet, "A{$filaHdr}:{$colUlt}{$filaHdr}");
                    $this->alturaFila($sheet, $filaHdr, 18);

                    for ($i = 0; $i <= ($filaFin - $filaIni); $i++) {
                        $fila = $filaIni + $i;
                        $this->estiloDato($sheet, "A{$fila}:{$colUlt}{$fila}", $i + 1);
                        if ($bloque['ncols'] > 1) {
                            $sheet->getStyle("B{$fila}:{$colUlt}{$fila}")->getAlignment()
                                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }
                        $this->alturaFila($sheet, $fila, 14);
                    }

                    if ($filaFin >= $filaHdr) {
                        $this->bordeExterno($sheet, "A{$filaHdr}:{$colUlt}{$filaFin}");
                    }
                }

                $this->fijarFila($sheet, 'A3');
                $ultimaFila = $s['puer_fin'];
                $sheet->getStyle('A1:G'.$ultimaFila)->getFont()->setName('Calibri');
            },
        ];
    }
}
