<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuffetJuiceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'buffet_package_id',
        'buffet_person_option_id',
        'description_ar',
        'description_en',
    ];

    public function buffetPackage()
    {
        return $this->belongsTo(BuffetPackage::class);
    }

    public function buffetPersonOption()
    {
        return $this->belongsTo(BuffetPersonOption::class);
    }
}