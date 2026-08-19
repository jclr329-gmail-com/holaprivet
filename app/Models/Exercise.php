<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    protected function casts(): array
    {
        return ['is_calque_trap' => 'boolean'];
    }

    public function options(): HasMany
    {
        return $this->hasMany(ExerciseOption::class)->orderBy('letter');
    }
}
