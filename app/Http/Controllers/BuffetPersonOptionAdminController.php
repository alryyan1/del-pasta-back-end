<?php

namespace App\Http\Controllers;

use App\Models\BuffetPackage;
use App\Models\BuffetPersonOption;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BuffetPersonOptionAdminController extends Controller
{
    /**
     * Store a new person/price option for a specific buffet package.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BuffetPackage  $package
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, BuffetPackage $package)
    {
        $validated = $request->validate([
            'label_ar' => 'required|string|max:255',
            'label_en' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $option = $package->personOptions()->create($validated);

        return response()->json($option, 201);
    }

    /**
     * Update the specified person/price option.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BuffetPersonOption  $personOption
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, BuffetPersonOption $personOption)
    {
        $validated = $request->validate([
            'label_ar' => 'required|string|max:255',
            'label_en' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $personOption->update($validated);

        return response()->json($personOption);
    }

    /**
     * Remove the specified person/price option from storage.
     *
     * @param  \App\Models\BuffetPersonOption  $personOption
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(BuffetPersonOption $personOption)
    {
        // Optional: You might want to prevent deletion if it's tied to orders.
        // For now, we allow deletion.
        $personOption->delete();
        
        return response()->json(['message' => 'Option deleted successfully.']);
    }
}