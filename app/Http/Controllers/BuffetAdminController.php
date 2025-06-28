<?php

namespace App\Http\Controllers;

use App\Models\BuffetPackage;
use Illuminate\Http\Request;

class BuffetAdminController extends Controller
{
    // Get all packages for the admin list
    public function index()
    {
        return BuffetPackage::orderBy('id', 'desc')->get();
    }

    // Create a new package
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'is_active' => 'boolean',
            // 'image_url' will be handled if you have an upload mechanism
        ]);

        $package = BuffetPackage::create($validated);
        return response()->json($package, 201);
    }

    // Get a single package for editing
    public function show(BuffetPackage $buffet_package)
    {
        return $buffet_package->load('personOptions', 'steps','juiceRules');
    }

    // Update a package
    public function update(Request $request, BuffetPackage $buffet_package)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $buffet_package->update($validated);
        return response()->json($buffet_package);
    }

    // Delete a package
    public function destroy(BuffetPackage $buffet_package)
    {
        $buffet_package->delete();
        return response()->json(['message' => 'Package deleted successfully'], 200);
    }
}