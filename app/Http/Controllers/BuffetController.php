<?php

namespace App\Http\Controllers;

use App\Models\BuffetPackage;
use App\Models\BuffetPersonOption;
use Illuminate\Http\Request;

class BuffetController extends Controller
{
    /**
     * Get all active buffet packages.
     */
    public function getPackages()
    {
        return BuffetPackage::where('is_active', true)->get();
    }

    /**
     * Get the person/price options for a specific package.
     */
    public function getPersonOptions(BuffetPackage $package)
    {
        return $package->personOptions()->where('is_active', true)->get();
    }

    /**
     * Get the configurable steps for a specific package.
     * Eager loads the category and its meals for each step.
     */
    public function getSteps(BuffetPackage $package)
    {
        // The BuffetStep model has 'with' => ['category.meals'], so this is efficient.
        return $package->steps()->where('is_active', true)->get();
    }

    /**
     * Get the juice rule/description for a specific person option.
     */
    public function getJuiceInfo(BuffetPersonOption $personOption)
    {
        // Use the relationship defined in the BuffetPersonOption model
        return $personOption->juiceRule;
    }
}