<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WallOwnership extends Model
{
    protected $table = 'wall_ownerships';

    protected $fillable = ['word_id', 'user_id', 'display_name', 'dedication',
        'moderation', 'moderated_at', 'moderated_note', 'starts_at',
        'expires_at', 'grace_until', 'status', 'renewed_from_id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'expires_at' => 'datetime',
                'grace_until' => 'datetime', 'moderated_at' => 'datetime'];
    }

    public function palabra() { return $this->belongsTo(WallWord::class, 'word_id'); }
    public function usuario() { return $this->belongsTo(User::class, 'user_id'); }
}
