<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id', 'total_cents', 'currency', 'status'];

    public function items()   { return $this->hasMany(OrderItem::class); }
    public function pagos()   { return $this->hasMany(Payment::class); }
    public function usuario() { return $this->belongsTo(User::class, 'user_id'); }
}
