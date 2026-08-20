<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Progress extends Model
{
    protected $table = 'progress';

    protected $fillable = [
        'user_id', 'piece_id', 'status', 'first_opened_at', 'completed_at',
        'open_count', 'answers_json', 'score_num', 'score_den',
    ];

    protected function casts(): array
    {
        return [
            'first_opened_at' => 'datetime',
            'completed_at'    => 'datetime',
        ];
    }
}
