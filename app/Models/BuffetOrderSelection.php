<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuffetOrderSelection extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'buffet_step_id',
        'meal_id',
        // 'quantity', // if added to migration
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function buffetStep()
    {
        return $this->belongsTo(BuffetStep::class);
    }

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }
    public function buffetOrder()
    {
        return $this->belongsTo(BuffetOrder::class);
    }
}