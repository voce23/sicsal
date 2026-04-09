<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    protected $fillable = [
        'titulo', 'slug', 'extracto', 'contenido',
        'imagen_portada', 'categoria', 'etiquetas',
        'publicado', 'publicado_at', 'autor_nombre', 'vistas',
    ];

    protected $casts = [
        'etiquetas'    => 'array',
        'publicado'    => 'boolean',
        'publicado_at' => 'datetime',
    ];

    // ── Categorías predefinidas ──
    public const CATEGORIAS = [
        'vacunacion'     => 'Vacunación PAI',
        'salud_materna'  => 'Salud Materna',
        'nutricion'      => 'Nutrición',
        'programas'      => 'Programas de Salud',
        'comunidad'      => 'Salud Comunitaria',
        'informes'       => 'Informes CAI',
        'general'        => 'General',
    ];

    // Colores de encabezado por categoría (clases Tailwind)
    public const COLORES_CATEGORIA = [
        'vacunacion'    => 'bg-cyan-600',
        'salud_materna' => 'bg-pink-600',
        'nutricion'     => 'bg-orange-500',
        'programas'     => 'bg-blue-600',
        'comunidad'     => 'bg-green-600',
        'informes'      => 'bg-purple-600',
        'general'       => 'bg-teal-600',
    ];

    // Gradientes de portada por defecto por categoría
    public const GRADIENTES = [
        'vacunacion'    => 'from-cyan-50 to-cyan-100',
        'salud_materna' => 'from-pink-50 to-rose-100',
        'nutricion'     => 'from-orange-50 to-amber-100',
        'programas'     => 'from-blue-50 to-indigo-100',
        'comunidad'     => 'from-green-50 to-emerald-100',
        'informes'      => 'from-purple-50 to-violet-100',
        'general'       => 'from-teal-50 to-cyan-100',
    ];

    // ── Scopes ──
    public function scopePublicados($query)
    {
        return $query->where('publicado', true)->orderByDesc('publicado_at');
    }

    // ── Helpers ──
    public static function generarSlug(string $titulo, ?int $excludeId = null): string
    {
        $base = Str::slug($titulo);
        $slug = $base;
        $i    = 1;
        while (
            static::where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

    public function labelCategoria(): string
    {
        return static::CATEGORIAS[$this->categoria] ?? ucfirst($this->categoria);
    }

    public function colorCategoria(): string
    {
        return static::COLORES_CATEGORIA[$this->categoria] ?? 'bg-teal-600';
    }

    public function gradientePortada(): string
    {
        return static::GRADIENTES[$this->categoria] ?? 'from-teal-50 to-cyan-100';
    }

    public function tiempoLectura(): int
    {
        $palabras = str_word_count(strip_tags($this->contenido ?? ''));
        return max(1, (int) ceil($palabras / 200));
    }
}
