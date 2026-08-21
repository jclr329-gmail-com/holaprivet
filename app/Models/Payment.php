<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['order_id', 'gateway', 'gateway_ref', 'amount_cents',
        'fee_cents', 'net_cents', 'status', 'paid_at', 'raw'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }
}
