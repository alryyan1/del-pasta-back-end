<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index()
    {
        try {
            $categories = Category::orderBy('name')->get();
            return response()->json($categories);
        } catch (\Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch categories'], 500);
        }
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name',
                'image' => 'nullable|file|image|mimes:jpeg,jpg,png,gif,webp|max:2048'
            ]);

            // Create category
            $category = Category::create([
                'name' => $validated['name'],
                'image_url' => null
            ]);

            // Handle image upload if provided
            if ($request->hasFile('image')) {
                $imageUrl = $this->handleImageUpload($request->file('image'));
                $category->update(['image_url' => $imageUrl]);
            }

            return response()->json([
                'message' => 'Category created successfully',
                'data' => $category->fresh()
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating category: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create category'], 500);
        }
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category)
    {
        return response()->json($category->load('meals'));
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
                'image_url' => 'nullable|string|max:500'
            ]);

            $category->update([
                'name' => $validated['name']
            ]);

            // Handle direct image_url assignment (for selecting existing images)
            if (isset($validated['image_url'])) {
                $category->update(['image_url' => $validated['image_url']]);
            }

            return response()->json([
                'message' => 'Category updated successfully',
                'data' => $category->fresh()
            ]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error updating category: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update category'], 500);
        }
    }

    /**
     * Upload image for category (dedicated endpoint).
     */
    public function uploadImage(Request $request, Category $category)
    {
        try {
            $request->validate([
                'image' => 'required|file|image|mimes:jpeg,jpg,png,gif,webp|max:2048'
            ]);

            // Delete old image if exists
            $this->deleteImage($category->image_url);

            // Upload new image
            $imageUrl = $this->handleImageUpload($request->file('image'));
            
            // Update category with new image
            $category->update(['image_url' => $imageUrl]);

            return response()->json([
                'message' => 'Image uploaded successfully',
                'data' => $category->fresh()
            ]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error uploading image: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to upload image'], 500);
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category)
    {
        try {
            // Delete associated image
            $this->deleteImage($category->image_url);
            
            // Delete category
            $category->delete();

            return response()->json(['message' => 'Category deleted successfully']);

        } catch (\Exception $e) {
            Log::error('Error deleting category: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete category'], 500);
        }
    }

    /**
     * Get available image files.
     */
    public function getImages()
    {
        try {
            $imagePath = public_path('images');
            
            if (!file_exists($imagePath)) {
                return response()->json(['images' => []]);
            }

            $files = scandir($imagePath);
            $images = [];

            foreach ($files as $file) {
                if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $images[] = $file;
                }
            }

            return response()->json(['images' => $images]);

        } catch (\Exception $e) {
            Log::error('Error fetching images: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch images'], 500);
        }
    }

    /**
     * Handle image upload and return filename.
     */
    private function handleImageUpload($file): string
    {
        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::random(10) . '_' . time() . '.' . $extension;

        // Ensure images directory exists
        $imagePath = public_path('images');
        if (!file_exists($imagePath)) {
            mkdir($imagePath, 0755, true);
        }

        // Move file to public/images
        $file->move($imagePath, $filename);

        Log::info("Image uploaded: {$filename}");
        
        return $filename;
    }

    /**
     * Delete image file from filesystem.
     */
    private function deleteImage(?string $imageUrl): void
    {
        if ($imageUrl && file_exists(public_path('images/' . $imageUrl))) {
            unlink(public_path('images/' . $imageUrl));
            Log::info("Image deleted: {$imageUrl}");
        }
    }
}
