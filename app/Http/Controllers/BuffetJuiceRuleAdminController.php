<?php

namespace App\Http\Controllers;

use App\Models\BuffetJuiceRule;
use App\Models\BuffetPersonOption;
use Illuminate\Http\Request;

class BuffetJuiceRuleAdminController extends Controller
{
    /**
     * Store or update the juice rule for a specific person option.
     * This is an "upsert" operation.
     */
    public function storeOrUpdate(Request $request, BuffetPersonOption $personOption)
    {
        $validated = $request->validate([
            'description_ar' => 'required|string',
            'description_en' => 'nullable|string',
        ]);

        $juiceRule = BuffetJuiceRule::updateOrCreate(
            [
                'buffet_person_option_id' => $personOption->id,
                'buffet_package_id' => $personOption->buffet_package_id,
            ],
            [
                'description_ar' => $validated['description_ar'],
                'description_en' => $validated['description_en'],
            ]
        );

        return response()->json($juiceRule);
    }
}