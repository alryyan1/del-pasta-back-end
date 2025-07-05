<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Get the current settings.
     */
    public function index(Request $request)
    {
        // Use firstOrCreate to ensure a settings record always exists.
        return Settings::firstOrCreate([]);
    }

    /**
     * Update all settings at once.
     * This replaces the old method that updated one column at a time.
     */
    public function update(Request $request)
    {
        // Find the single settings record or create it if it doesn't exist.
        $settings = Settings::firstOrCreate([]);

        // Validate only the fields present in the request
        $validatedData = $request->validate([
            'kitchen_name' => 'nullable|string|max:255',
            'inventory_notification_number' => 'nullable|string|max:255',
            'header_content' => 'nullable|string',
            'footer_content' => 'nullable|string',
            'is_header' => 'boolean',
            'is_footer' => 'boolean',
            'is_logo' => 'boolean',
            'header_base64' => 'nullable|string',
            'footer_base64' => 'nullable|string',
            // Add any other settings fields here
        ]);
        
        $settings->update($validatedData);

        return response()->json(['status' => true, 'message' => 'Settings updated successfully.']);
    }
}