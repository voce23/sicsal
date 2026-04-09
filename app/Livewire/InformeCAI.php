<?php

namespace App\Livewire;

use App\Concerns\HasCentroSelector;
use App\Exports\InformeCAIExport;
use App\Exports\PresentacionCAI;
use App\Helpers\CaiHelper;
use App\Models\CentroSalud;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class InformeCAI extends Component
{
    use HasCentroSelector;

    #[Url]
    public string $periodo = 'cai1';

    #[Url]
    public int $anio = 0;

    public function mount(): void
    {
        $this->anio = $this->anio ?: (int) date('Y');
        $this->mountHasCentroSelector();
    }

    public function getCentrosProperty(): array
    {
        return $this->centrosDisponibles;
    }

    public function getDatosProperty(): ?array
    {
        if ($this->centroSaludId === 0) {
            return null;
        }

        return CaiHelper::getDatosInforme($this->centroSaludId, $this->periodo, $this->anio);
    }

    public function getNombrePeriodoProperty(): string
    {
        return CaiHelper::getNombrePeriodo($this->periodo, $this->anio);
    }

    public function generarExcel()
    {
        if ($this->centroSaludId === 0) {
            return;
        }

        $datos = CaiHelper::getDatosInforme($this->centroSaludId, $this->periodo, $this->anio);
        $nombre = 'InformeCAI_' . str_replace(' ', '_', $datos['encabezado']['centro_nombre'])
            . '_' . $this->periodo . '_' . $this->anio . '.xlsx';

        return Excel::download(new InformeCAIExport($datos), $nombre);
    }

    public function generarPdf()
    {
        if ($this->centroSaludId === 0) {
            return;
        }

        $datos = CaiHelper::getDatosInforme($this->centroSaludId, $this->periodo, $this->anio);
        $nombre = 'InformeCAI_' . str_replace(' ', '_', $datos['encabezado']['centro_nombre'])
            . '_' . $this->periodo . '_' . $this->anio . '.pdf';

        $pdf = Pdf::loadView('pdf.informe-cai', ['datos' => $datos])
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 15)
            ->setOption('margin-bottom', 15)
            ->setOption('margin-left', 15)
            ->setOption('margin-right', 15);

        return response()->streamDownload(fn () => print($pdf->output()), $nombre);
    }

    public function generarPptx()
    {
        if ($this->centroSaludId === 0) {
            return;
        }

        $centro = CentroSalud::find($this->centroSaludId);
        $pptx = new PresentacionCAI($centro->municipio_id, $this->periodo, $this->anio);
        $tmpFile = $pptx->generate();

        $nombre = 'InformeCAI_Municipal_'
            . $this->periodo . '_' . $this->anio . '.pptx';

        return response()->streamDownload(function () use ($tmpFile) {
            readfile($tmpFile);
            @unlink($tmpFile);
        }, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ]);
    }

    public function render()
    {
        return view('livewire.informe-cai')
            ->layout('layouts.public', ['title' => 'C.A.I. — SIMUES']);
    }
}
