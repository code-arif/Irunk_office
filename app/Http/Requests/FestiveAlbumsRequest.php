<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FestiveAlbumsRequest extends FormRequest
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
             'festival_name' => 'required|string',
            // artist table
            'name' => 'nullable|string|max:255',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',

            // festive_experiences table
            'favourite_set' => 'required|string|max:255',
            'favourite_day' => 'nullable|string|max:255',
            'day_type'      => 'nullable|in:single-day,none',
            'camp_experience' => 'required|string|max:255',
            'festive_story' => 'required|string',
            'festive_date' => 'nullable|date',
            'status' => 'nullable|in:public,private',
            'fest_type' => 'nullable|string',

            // festive_documents table (multiple files)
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:50000',
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Artist image is required.',
            'favourite_set.required' => 'The favourite set field is required.',
            'favourite_day.required' => 'The favourite day field is required.',
            'camp_experience.required' => 'The camp experience field is required.',
            'festive_story.required' => 'The festive story field is required.',
            'festive_date.required' => 'The festive date field is required.',
        ];
    }
}
