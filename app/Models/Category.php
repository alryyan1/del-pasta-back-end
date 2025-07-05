<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $image_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meal> $meals
 * @property-read int|null $meals_count
 * @method static \Illuminate\Database\Eloquent\Builder|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Category whereName($value)
 * @mixin \Eloquent
 */
class Category extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    
    protected $fillable = [
        'name',
        'image_url',
    ];
    
    protected $with = ['meals'];
    
    /**
     * Get the meals for the category.
     */
    public function meals()
    {
        return $this->hasMany(Meal::class);
    }
    
    /**
     * Append computed attributes to JSON.
     */
    protected $appends = ['full_image_url'];
    
    /**
     * Get the full image URL as a computed attribute.
     */
    public function getFullImageUrlAttribute()
    {
        if ($this->image_url) {
            return url('images/' . $this->image_url);
        }
        
        return null;
    }
}
