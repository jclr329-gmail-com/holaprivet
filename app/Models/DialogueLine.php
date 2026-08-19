<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialogueLine extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    protected function casts(): array
    {
        return ['is_break' => 'boolean'];
    }
}
