<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExerciseOption extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }
}
