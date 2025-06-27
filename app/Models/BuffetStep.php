<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuffetStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'buffet_package_id',
        'step_number',
        'title_ar',
        'title_en',
        'instructions_ar',
        'instructions_en',
        'category_id',
        'min_selections',
        'max_selections',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Eager load category with meals for API response efficiency
    protected $with = ['category.meals'];


    public function buffetPackage()
    {
        return $this->belongsTo(BuffetPackage::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}