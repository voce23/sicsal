<?php

namespace App\Livewire;

use App\Models\Post;
use Livewire\Component;

class PostView extends Component
{
    public Post $post;

    public function mount(string $slug): void
    {
        $this->post = Post::where('slug', $slug)
            ->where('publicado', true)
            ->firstOrFail();

        // Increment view counter without triggering observers
        Post::withoutTimestamps(function () {
            $this->post->increment('vistas');
        });
    }

    public function render()
    {
        $relacionados = Post::publicados()
            ->where('categoria', $this->post->categoria)
            ->where('id', '!=', $this->post->id)
            ->limit(3)
            ->get(['id', 'titulo', 'slug', 'imagen_portada', 'publicado_at', 'extracto']);

        return view('livewire.post-view', [
            'post'        => $this->post,
            'relacionados' => $relacionados,
        ])->layout('layouts.public', ['title' => $this->post->titulo . ' — SIMUES']);
    }
}
