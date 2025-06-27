<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::firstOrCreate(['name' => 'أطباق رئيسية'], ['image_url' => 'main_dishes.jpg']);
        Category::firstOrCreate(['name' => 'سلطات'], ['image_url' => 'salads.jpg']);
        Category::firstOrCreate(['name' => 'حلويات'], ['image_url' => 'desserts.jpg']);
        Category::firstOrCreate(['name' => 'مقبلات'], ['image_url' => 'appetizers.jpg']); // Example extra category
    }
}