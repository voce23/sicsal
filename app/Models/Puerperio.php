<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Puerperio extends Model
{
    protected $fillable = [
        'parto_id', 'control_48h', 'control_7d', 'control_28d', 'control_42d',
    ];

    protected function casts(): array
    {
        return [
            'control_48h' => 'date',
            'control_7d' => 'date',
            'control_28d' => 'date',
            'control_42d' => 'date',
        ];
    }

    public function parto(): BelongsTo
    {
        return $this->belongsTo(Parto::class);
    }
}
