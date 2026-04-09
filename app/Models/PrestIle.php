<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrestIle extends Model
{
    protected $table = 'prest_ile';

    protected $fillable = ['centro_salud_id', 'mes', 'anio', 'indicador', 'cantidad'];
}
