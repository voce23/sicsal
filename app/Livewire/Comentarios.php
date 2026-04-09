<?php

namespace App\Livewire;

use App\Models\Comentario;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Comentarios extends Component
{
    public string $pagina;

    #[Validate('required|string|max:100')]
    public string $nombre = '';

    #[Validate('required|string|min:3|max:2000')]
    public string $contenido = '';

    #[Validate('required|integer')]
    public ?int $captchaRespuesta = null;

    public int $captchaA = 0;
    public int $captchaB = 0;

    public bool $exito = false;

    public function mount(string $pagina): void
    {
        $this->pagina = $pagina;
        $this->generarCaptcha();
    }

    public function generarCaptcha(): void
    {
        $this->captchaA = random_int(1, 15);
        $this->captchaB = random_int(1, 15);
        $this->captchaRespuesta = null;
    }

    public function publicar(): void
    {
        $this->validate();

        if ((int) $this->captchaRespuesta !== ($this->captchaA + $this->captchaB)) {
            $this->addError('captchaRespuesta', 'La respuesta del captcha es incorrecta.');
            $this->generarCaptcha();
            return;
        }

        Comentario::create([
            'pagina' => $this->pagina,
            'nombre' => strip_tags($this->nombre),
            'contenido' => strip_tags($this->contenido),
        ]);

        $this->reset(['nombre', 'contenido']);
        $this->generarCaptcha();
        $this->exito = true;
    }

    public function getComentariosProperty()
    {
        return Comentario::where('pagina', $this->pagina)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.comentarios');
    }
}
