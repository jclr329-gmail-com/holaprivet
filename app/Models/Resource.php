<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    protected $fillable = ['categoria', 'titulo', 'nota', 'tipo', 'archivo',
                           'url', 'orden', 'visible'];

    protected function casts(): array
    {
        return ['visible' => 'boolean'];
    }
}
