<?php

namespace Database\Seeders;

use App\Models\BuffetPackage;
use App\Models\BuffetStep;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BuffetStepSeeder extends Seeder
{
    public function run(): void
    {
        $saverPackage = BuffetPackage::where('name_ar', 'الباقة التوفيرية الشاملة')->first();
        
        $mainDishesCategory = Category::where('name', 'أطباق رئيسية')->first();
        $saladsCategory = Category::where('name', 'سلطات')->first();
        $dessertsCategory = Category::where('name', 'حلويات')->first();

        if ($saverPackage && $mainDishesCategory && $saladsCategory && $dessertsCategory) {
            // Step 1: Main Dishes
            BuffetStep::firstOrCreate(
                ['buffet_package_id' => $saverPackage->id, 'step_number' => 1],
                [
                    'title_ar' => 'اختر الطبق الرئيسي',
                    'instructions_ar' => 'اختر نوع واحد من اللحم مع الرز',
                    'category_id' => $mainDishesCategory->id,
                    'min_selections' => 1,
                    'max_selections' => 1,
                ]
            );

            // Step 2: Salads
            BuffetStep::firstOrCreate(
                ['buffet_package_id' => $saverPackage->id, 'step_number' => 2],
                [
                    'title_ar' => 'اختر السلطات',
                    'instructions_ar' => 'اختر صنفين من السلطات',
                    'category_id' => $saladsCategory->id,
                    'min_selections' => 2,
                    'max_selections' => 2,
                ]
            );

            // Step 3: Desserts
            BuffetStep::firstOrCreate(
                ['buffet_package_id' => $saverPackage->id, 'step_number' => 3],
                [
                    'title_ar' => 'اختر الحلويات',
                    'instructions_ar' => 'اختر ثلاثة أصناف من الحلويات',
                    'category_id' => $dessertsCategory->id,
                    'min_selections' => 3,
                    'max_selections' => 3,
                ]
            );
        }
    }
}