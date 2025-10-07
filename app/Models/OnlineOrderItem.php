<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'online_order_id',
        'meal_id',
        'quantity',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:3',
    ];

    public function order()
    {
        return $this->belongsTo(OnlineOrder::class, 'online_order_id');
    }

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }
}


