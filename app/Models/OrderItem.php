<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'word_id', 'price_cents'];

    public function palabra() { return $this->belongsTo(WallWord::class, 'word_id'); }
}
