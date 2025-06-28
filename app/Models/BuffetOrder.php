<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuffetOrder extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'base_price' => 'decimal:3',
        'delivery_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function buffetPackage()
    {
        return $this->belongsTo(BuffetPackage::class);
    }

    public function buffetPersonOption()
    {
        return $this->belongsTo(BuffetPersonOption::class);
    }

    public function selections()
    {
        return $this->hasMany(BuffetOrderSelection::class);
    }
}