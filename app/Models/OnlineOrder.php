<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_address',
        'notes',
        'total_price',
        'delivery_fee',
        'status',
        'order_type',
        'state',
        'area',
    ];

    protected $casts = [
        'total_price' => 'decimal:3',
        'delivery_fee' => 'decimal:3',
    ];

    public function items()
    {
        return $this->hasMany(OnlineOrderItem::class);
    }
}


