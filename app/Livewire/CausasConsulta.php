<?php

namespace App\Livewire;

use App\Concerns\HasCentroSelector;
use App\Helpers\CausasConsultaHelper;
use App\Models\CentroSalud;
use Livewire\Attributes\Url;
use Livewire\Component;

class CausasConsulta extends Component
{
    use HasCentroSelector;

    #[Url]
    public int $anio = 0;

    #[Url]
    public int $mes = 0; // 0 = todo el año

    public function mount(): void
    {
        $this->anio = $this->anio ?: (int) date('Y');
        $this->mountHasCentroSelector();
    }

    public function getCentrosProperty(): array
    {
        return CentroSalud::where('activo', true)
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->toArray();
    }

    public function getDatosProperty(): ?array
    {
        if ($this->centroSaludId === 0) {
            return null;
        }

        return CausasConsultaHelper::getTop10($this->centroSaludId, $this->anio, $this->mes);
    }

    public function render()
    {
        return view('livewire.causas-consulta')
            ->layout('layouts.public', ['title' => '10 Causas Consulta Externa — SIMUES']);
    }
}
