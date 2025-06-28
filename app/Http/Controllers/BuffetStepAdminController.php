<?php

namespace App\Http\Controllers;

use App\Models\BuffetPackage;
use App\Models\BuffetStep;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BuffetStepAdminController extends Controller
{
    /**
     * Store a new buffet step for a specific package.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BuffetPackage  $package
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, BuffetPackage $buffet_package)
    {
        $validated = $request->validate([
            'step_number' => [
                'required',
                'integer',
                'min:1',
                // Ensure the step number is unique for this specific package
                Rule::unique('buffet_steps')->where(function ($query) use ($buffet_package) {
                    return $query->where('buffet_package_id', $buffet_package->id);
                }),
            ],
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'instructions_ar' => 'nullable|string',
            'instructions_en' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'min_selections' => 'required|integer|min:0',
            'max_selections' => 'required|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);
        
        // Ensure min selections is not greater than max selections
        if ($validated['min_selections'] > $validated['max_selections']) {
            throw ValidationException::withMessages([
                'min_selections' => 'The minimum selections cannot be greater than the maximum selections.',
            ]);
        }

        $step = $buffet_package->steps()->create($validated);

        // Return with the category loaded for immediate use in the UI
        return response()->json($step->load('category'), 201);
    }

    /**
     * Update the specified buffet step.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BuffetStep  $step
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, BuffetStep $buffet_step)
    {
        $validated = $request->validate([
            'step_number' => [
                'required',
                'integer',
                'min:1',
                // Ensure the step number is unique for this package, ignoring the current step itself
                Rule::unique('buffet_steps')->where(function ($query) use ($buffet_step) {
                    return $query->where('buffet_package_id', $buffet_step->buffet_package_id);
                })->ignore($buffet_step->id),
            ],
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'instructions_ar' => 'nullable|string',
            'instructions_en' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'min_selections' => 'required|integer|min:0',
            'max_selections' => 'required|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);
        
        // Ensure min selections is not greater than max selections
        if ($validated['min_selections'] > $validated['max_selections']) {
            throw ValidationException::withMessages([
                'min_selections' => 'The minimum selections cannot be greater than the maximum selections.',
            ]);
        }

        $buffet_step->update($validated);
        
        // Return with the category loaded for immediate use in the UI
        return response()->json($buffet_step->load('category'));
    }
    
    /**
     * Remove the specified buffet step from storage.
     *
     * @param  \App\Models\BuffetStep  $buffet_step
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(BuffetStep $buffet_step)
    {
        $buffet_step->delete();
        return response()->json(['message' => 'Step deleted successfully.']);
    }
}