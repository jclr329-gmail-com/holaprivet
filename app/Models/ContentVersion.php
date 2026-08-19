<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentVersion extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    protected function casts(): array
    {
        return ['imported_at' => 'datetime'];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ImportLog::class, 'version_id');
    }
}
