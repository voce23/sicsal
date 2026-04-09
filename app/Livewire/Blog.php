<?php

namespace App\Livewire;

use App\Models\Post;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Blog extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $busqueda = '';

    #[Url(as: 'cat')]
    public string $categoria = '';

    #[Url(as: 'tag')]
    public string $etiqueta = '';

    public string $vista = 'rejilla'; // 'rejilla' | 'lista'

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingCategoria(): void
    {
        $this->resetPage();
    }

    public function updatingEtiqueta(): void
    {
        $this->resetPage();
    }

    public function setCategoria(string $cat): void
    {
        $this->categoria = $this->categoria === $cat ? '' : $cat;
        $this->resetPage();
    }

    public function setEtiqueta(string $tag): void
    {
        $this->etiqueta = $this->etiqueta === $tag ? '' : $tag;
        $this->resetPage();
    }

    public function setVista(string $v): void
    {
        $this->vista = $v;
    }

    public function render()
    {
        $query = Post::publicados();

        if ($this->busqueda) {
            $query->where(function ($q) {
                $q->where('titulo', 'like', "%{$this->busqueda}%")
                    ->orWhere('extracto', 'like', "%{$this->busqueda}%");
            });
        }

        if ($this->categoria) {
            $query->where('categoria', $this->categoria);
        }

        if ($this->etiqueta) {
            $query->whereJsonContains('etiquetas', $this->etiqueta);
        }

        $posts = $query->paginate(9);

        // Sidebar data
        // reorder() evita que scopePublicados inyecte ORDER BY publicado_at en una
        // query con GROUP BY, lo que viola el modo MySQL ONLY_FULL_GROUP_BY.
        $categorias = Post::publicados()
            ->selectRaw('categoria, count(*) as total')
            ->groupBy('categoria')
            ->reorder()
            ->pluck('total', 'categoria');

        $recientes = Post::publicados()->limit(5)->get(['id', 'titulo', 'slug', 'publicado_at', 'imagen_portada']);

        // Tag cloud: flatten all etiquetas arrays
        $todasEtiquetas = Post::publicados()
            ->whereNotNull('etiquetas')
            ->pluck('etiquetas')
            ->flatten()
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(20);

        return view('livewire.blog', [
            'posts' => $posts,
            'categorias' => $categorias,
            'recientes' => $recientes,
            'todasEtiquetas' => $todasEtiquetas,
        ])->layout('layouts.public', ['title' => 'Blog de Salud — SIMUES']);
    }
}
