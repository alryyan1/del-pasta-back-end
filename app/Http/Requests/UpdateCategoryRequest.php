<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:categories,name,' . $this->route('category'),
            'image' => 'nullable|file|image|mimes:jpeg,jpg,png,gif,webp|max:2048', // For file uploads
            'image_url' => 'nullable|string|max:255', // For selecting existing images
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.unique' => 'A category with this name already exists.',
            'name.max' => 'Category name must not exceed 255 characters.',
            'image.file' => 'Image must be a valid file.',
            'image.image' => 'File must be an image.',
            'image.mimes' => 'Image must be a JPEG, PNG, GIF, or WebP file.',
            'image.max' => 'Image size must not exceed 2MB.',
            'image_url.string' => 'Image URL must be a valid string.',
            'image_url.max' => 'Image URL must not exceed 255 characters.',
        ];
    }
}
