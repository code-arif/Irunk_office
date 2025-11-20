<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FestiveUpdateAlbumsRequest extends FormRequest
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
             'festival_name' => 'nullable|string',
            // artist table
            'name' => 'nullable|string|max:255',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',

            // festive_experiences table
            'favourite_set' => 'nullable|string|max:255',
            'favourite_day' => 'nullable|string|max:255',
            'camp_experience' => 'nullable|string|max:255',
            'festive_story' => 'nullable|string',
            'festive_date' => 'nullable|date',
            'status' => 'nullable|in:public,private',
            'fest_type' => 'nullable|string',
            'locations' => 'nullable|string|max:255',

            // festive_documents table (multiple files)
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:50000',
        ];
    }

    public function messages(): array
    {
        return [
            'image.nullable' => 'Artist image is required.',
            'favourite_set.nullable' => 'The favourite set field is required.',
            'favourite_day.nullable' => 'The favourite day field is required.',
            'camp_experience.nullable' => 'The camp experience field is required.',
            'festive_story.nullable' => 'The festive story field is required.',
            'festive_date.nullable' => 'The festive date field is required.',
        ];
    }
}
