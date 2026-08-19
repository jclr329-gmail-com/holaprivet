<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Piece extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'characters'   => 'array',
            'in_campaign'  => 'boolean',
            'printable'    => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function sections(): HasMany     { return $this->hasMany(PieceSection::class)->orderBy('number'); }
    public function vocabulary(): HasMany   { return $this->hasMany(Vocabulary::class)->orderBy('position'); }
    public function lines(): HasMany        { return $this->hasMany(DialogueLine::class)->orderBy('position'); }
    public function phrases(): HasMany      { return $this->hasMany(Phrase::class)->orderBy('position'); }
    public function exercises(): HasMany    { return $this->hasMany(Exercise::class)->orderBy('position'); }
    public function links(): HasMany        { return $this->hasMany(CrossLink::class, 'from_piece_id')->orderBy('position'); }
}
