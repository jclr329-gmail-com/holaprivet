<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WallWord extends Model
{
    protected $fillable = ['word', 'translation_ru', 'kind', 'price_cents',
        'grid_x', 'grid_y', 'grid_w', 'grid_h', 'status', 'reserved_until',
        'audio_id', 'vocabulary_id'];

    protected function casts(): array
    {
        return ['reserved_until' => 'datetime'];
    }

    public function propiedad()
    {
        return $this->hasOne(WallOwnership::class, 'word_id')
            ->where('status', '!=', 'caducada')->latestOfMany();
    }
}
