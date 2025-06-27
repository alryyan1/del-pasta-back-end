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
    public function show(BuffetPackage $package)
    {
        return $package;
    }

    // Update a package
    public function update(Request $request, BuffetPackage $package)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $package->update($validated);
        return response()->json($package);
    }

    // Delete a package
    public function destroy(BuffetPackage $package)
    {
        $package->delete();
        return response()->json(['message' => 'Package deleted successfully'], 200);
    }
}