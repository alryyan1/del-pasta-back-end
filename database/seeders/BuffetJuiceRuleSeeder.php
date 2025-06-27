<?php

namespace Database\Seeders;

use App\Models\BuffetJuiceRule;
use App\Models\BuffetPersonOption;
use Illuminate\Database\Seeder;

class BuffetJuiceRuleSeeder extends Seeder
{
    public function run(): void
    {
        $option1 = BuffetPersonOption::where('label_ar', '10 - 12 شخص')->first();
        $option2 = BuffetPersonOption::where('label_ar', '20 - 22 شخص')->first();
        $option3 = BuffetPersonOption::where('label_ar', '30 - 32 شخص')->first();

        if ($option1) {
            BuffetJuiceRule::firstOrCreate(
                ['buffet_person_option_id' => $option1->id],
                [
                    'buffet_package_id' => $option1->buffet_package_id,
                    'description_ar' => 'يتم توفير تشكيلة عصائر طازجة تكفي لـ ١٢ شخص.'
                ]
            );
        }
        if ($option2) {
            BuffetJuiceRule::firstOrCreate(
                ['buffet_person_option_id' => $option2->id],
                [
                    'buffet_package_id' => $option2->buffet_package_id,
                    'description_ar' => 'يتم توفير تشكيلة عصائر طازجة تكفي لـ ٢٢ شخص.'
                ]
            );
        }
        if ($option3) {
            BuffetJuiceRule::firstOrCreate(
                ['buffet_person_option_id' => $option3->id],
                [
                    'buffet_package_id' => $option3->buffet_package_id,
                    'description_ar' => 'يتم توفير تشكيلة عصائر طازجة تكفي لـ ٣٢ شخص.'
                ]
            );
        }
    }
}