<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipio extends Model
{
    protected $fillable = ['nombre', 'departamento', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function centrosSalud(): HasMany
    {
        return $this->hasMany(CentroSalud::class);
    }
}
