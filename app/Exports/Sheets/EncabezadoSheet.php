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

class EncabezadoSheet implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    use EstiloExcel;

    public function __construct(private array $encabezado, private array $mesesCerrados) {}

    public function title(): string
    {
        return 'Encabezado';
    }

    public function columnWidths(): array
    {
        return ['A' => 28, 'B' => 42];
    }

    public function array(): array
    {
        $rows = [
            // Fila 1: título principal (se fusiona en AfterSheet)
            ['INFORME CAI — ' . strtoupper($this->encabezado['periodo_nombre']), ''],
            // Fila 2: vacía separadora
            ['', ''],
            // Fila 3: subtítulo sección
            ['DATOS DEL ESTABLECIMIENTO', ''],
            ['Establecimiento',  $this->encabezado['centro_nombre']],
            ['Código SNIS',      $this->encabezado['codigo_snis']],
            ['Red de Salud',     $this->encabezado['red_salud']],
            ['Municipio',        $this->encabezado['municipio']],
            ['Departamento',     $this->encabezado['departamento']],
            ['Subsector',        $this->encabezado['subsector']],
            ['Población INE',    $this->encabezado['poblacion_ine']],
            ['Responsable',      $this->encabezado['responsable']],
            ['Fecha generación', $this->encabezado['fecha_generacion']],
            ['', ''],
            ['ESTADO DE MESES DEL PERÍODO', ''],
            ['Mes', 'Estado'],
        ];

        foreach ($this->mesesCerrados as $mc) {
            $rows[] = [$mc['mes'], $mc['cerrado'] ? '✓ Cerrado' : '○ Abierto'];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $sheet = $e->sheet->getDelegate();

                // Fila 1: título principal
                $this->estiloTitulo($sheet, 'A1:B1', '');
                $sheet->getStyle('A1')->getFont()->setSize(14);
                $this->alturaFila($sheet, 1, 28);

                // Fila 3: sección datos establecimiento
                $this->estiloSeccion($sheet, 'A3:B3');
                $this->alturaFila($sheet, 3, 20);

                // Filas 4-12: key-value datos
                for ($i = 4; $i <= 12; $i++) {
                    $this->estiloEtiqueta($sheet, "A{$i}");
                    $this->estiloValor($sheet, "B{$i}");
                    $this->alturaFila($sheet, $i, 16);
                }

                // Borde externo bloque datos
                $this->bordeExterno($sheet, 'A4:B12');

                // Fila 14: sección meses
                $filaSecMeses = 14;
                $this->estiloSeccion($sheet, "A{$filaSecMeses}:B{$filaSecMeses}");
                $this->alturaFila($sheet, $filaSecMeses, 20);

                // Fila 15: header meses
                $filaHdrMeses = 15;
                $this->estiloHeader($sheet, "A{$filaHdrMeses}:B{$filaHdrMeses}");
                $this->alturaFila($sheet, $filaHdrMeses, 16);

                // Filas datos meses
                $inicio = 16;
                foreach ($this->mesesCerrados as $idx => $mc) {
                    $fila = $inicio + $idx;
                    $this->estiloDato($sheet, "A{$fila}:B{$fila}", $idx + 1, true);

                    // Color especial si cerrado
                    if ($mc['cerrado']) {
                        $sheet->getStyle("B{$fila}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['argb' => 'FF' . self::GREEN_OK]],
                        ]);
                    }
                    $this->alturaFila($sheet, $fila, 15);
                }

                $ultimaFila = $inicio + count($this->mesesCerrados) - 1;
                $this->bordeExterno($sheet, "A{$filaHdrMeses}:B{$ultimaFila}");

                $sheet->getStyle('A1:B' . ($ultimaFila))->getFont()->setName('Calibri');
            },
        ];
    }
}
