<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuffetPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function personOptions()
    {
        return $this->hasMany(BuffetPersonOption::class);
    }

    public function steps()
    {
        return $this->hasMany(BuffetStep::class)->orderBy('step_number');
    }

    public function juiceRules()
    {
        return $this->hasMany(BuffetJuiceRule::class);
    }
}