<?php

namespace Database\Seeders;

use App\Models\BuffetPackage;
use App\Models\BuffetPersonOption;
use Illuminate\Database\Seeder;

class BuffetPersonOptionSeeder extends Seeder
{
    public function run(): void
    {
        $saverPackage = BuffetPackage::where('name_ar', 'الباقة التوفيرية الشاملة')->first();

        if ($saverPackage) {
            BuffetPersonOption::firstOrCreate(
                ['buffet_package_id' => $saverPackage->id, 'label_ar' => '10 - 12 شخص'],
                ['price' => 135.000, 'min_persons' => 10, 'max_persons' => 12]
            );
            BuffetPersonOption::firstOrCreate(
                ['buffet_package_id' => $saverPackage->id, 'label_ar' => '20 - 22 شخص'],
                ['price' => 160.000, 'min_persons' => 20, 'max_persons' => 22]
            );
            BuffetPersonOption::firstOrCreate(
                ['buffet_package_id' => $saverPackage->id, 'label_ar' => '30 - 32 شخص'],
                ['price' => 190.000, 'min_persons' => 30, 'max_persons' => 32]
            );
        }
    }
}