<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuffetPersonOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'buffet_package_id',
        'label_ar',
        'label_en',
        'min_persons',
        'max_persons',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function buffetPackage()
    {
        return $this->belongsTo(BuffetPackage::class);
    }

    public function juiceRule()
    {
        // Assuming one rule per person option for simplicity in this direction
        return $this->hasOne(BuffetJuiceRule::class);
    }
}