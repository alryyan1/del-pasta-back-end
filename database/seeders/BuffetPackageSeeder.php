<?php

namespace Database\Seeders;

use App\Models\BuffetPackage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BuffetPackageSeeder extends Seeder
{
    public function run(): void
    {
        BuffetPackage::firstOrCreate(
            ['name_ar' => 'الباقة التوفيرية الشاملة'],
            [
                'name_en' => 'Saver Buffet Package',
                'description_ar' => 'اختيار اقتصادي ومتكامل لمناسباتكم.',
                'is_active' => true,
            ]
        );

        BuffetPackage::firstOrCreate(
            ['name_ar' => 'الباقة الخاصة'],
            [
                'name_en' => 'Special Buffet Package',
                'description_ar' => 'تجربة فاخرة مع تشكيلة أوسع من الأطباق.',
                'is_active' => true,
            ]
        );
    }
}