<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodOrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'food_order_id',
        'meal_id',
        'quantity',
        'price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:3',
    ];

    /**
     * Get the order that this item belongs to.
     */
    public function foodOrder()
    {
        return $this->belongsTo(FoodOrder::class);
    }

    /**
     * Get the meal associated with this order item.
     */
    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }
}