<?php

namespace App\Exports\Sheets;

use App\Models\CentroSalud;
use App\Models\Comunidad;
use App\Models\Persona;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PadronComunidadSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    private ?Collection $personas = null;

    public function __construct(
        private Comunidad $comunidad,
        private ?CentroSalud $centro
    ) {}

    public function title(): string
    {
        return mb_substr($this->comunidad->nombre, 0, 31);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 28,
            'C' => 22,
            'D' => 11,
            'E' => 7,
            'F' => 22,
        ];
    }

    public function array(): array
    {
        $personas = $this->getPersonas();

        $rows   = [];
        $rows[] = ['PADRÓN COMUNAL — ' . mb_strtoupper($this->comunidad->nombre)];
        $rows[] = ['Centro: ' . ($this->centro->nombre ?? '')];
        $rows[] = ['#', 'Apellidos', 'Nombres', 'Sexo', 'Edad', 'Comunidad'];

        $n = 1;
        foreach ($personas as $persona) {
            $rows[] = [
                $n++,
                $persona->apellidos,
                $persona->nombres,
                $persona->sexo === 'M' ? 'Masculino' : 'Femenino',
                $persona->edad,
                $this->comunidad->nombre,
            ];
        }

        $rows[] = ['Total: ' . ($n - 1), '', '', '', '', ''];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $numPersonas = $this->getPersonas()->count();
        $headerRow   = 3;
        $lastDataRow = $headerRow + $numPersonas;
        $totalRow    = $lastDataRow + 1;

        // Encabezado de columnas — fondo ámbar oscuro
        $sheet->getStyle("A{$headerRow}:F{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B45309']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Bordes en área de datos
        if ($numPersonas > 0) {
            $sheet->getStyle("A{$headerRow}:F{$totalRow}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        // Centrar columnas numéricas y Sexo
        $sheet->getStyle("A{$headerRow}:A{$lastDataRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$headerRow}:F{$lastDataRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Filas alternas
        for ($r = $headerRow + 1; $r <= $lastDataRow; $r++) {
            if ($r % 2 === 0) {
                $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                ]);
            }
        }

        // Color diferenciado por sexo en columna D
        for ($r = $headerRow + 1; $r <= $lastDataRow; $r++) {
            $val = $sheet->getCell("D{$r}")->getValue();
            if ($val === 'Masculino') {
                $sheet->getStyle("D{$r}")->getFont()->getColor()->setRGB('1D4ED8');
            } elseif ($val === 'Femenino') {
                $sheet->getStyle("D{$r}")->getFont()->getColor()->setRGB('BE185D');
            }
        }

        // Panel congelado bajo el encabezado
        $sheet->freezePane('A' . ($headerRow + 1));

        return [
            1          => ['font' => ['bold' => true, 'size' => 13]],
            2          => ['font' => ['italic' => true, 'size' => 10]],
            $totalRow  => ['font' => ['bold' => true]],
        ];
    }

    private function getPersonas(): Collection
    {
        if ($this->personas === null) {
            $this->personas = Persona::where('comunidad_id', $this->comunidad->id)
                ->where('activo', true)
                ->where('estado', '!=', 'migrado')
                ->orderBy('apellidos')
                ->orderBy('nombres')
                ->get();
        }

        return $this->personas;
    }
}
